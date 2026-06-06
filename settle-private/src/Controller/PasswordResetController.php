<?php
declare(strict_types=1);
namespace Settle\Controller;

use Settle\Auth;
use Settle\AuditLog;
use Settle\Mailer;
use Settle\Settings;
use Settle\Model\User;

/**
 * Self-service password reset (roadmap #6b).
 *
 * Public, always-on flow (mirrors the login routes, which are not
 * feature-gated):
 *
 *   GET  /admin/forgot  showForgot  — request form (username/email)
 *   POST /admin/forgot  doForgot    — issue + email a token
 *   GET  /admin/reset   showReset   — new-password form (or expired state)
 *   POST /admin/reset   doReset     — set the new password, single-use token
 *
 * Security model:
 *  - Token: 32 random bytes -> 64 lowercase hex chars (the RAW token,
 *    emailed in the link). Only sha256(raw) is stored, in
 *    users.password_reset_token (CHAR(64)); a DB leak therefore can't be
 *    replayed to reset anyone. The lookup is an indexed equality on that
 *    hash — since the raw token is 256 bits of CSPRNG output there is no
 *    useful timing side-channel, so no separate hash_equals step is
 *    needed (and a malformed token is rejected before any DB hit).
 *  - TTL: 15 minutes (TOKEN_TTL_SECONDS), checked with a PHP-bound :now
 *    (app timezone) rather than SQL NOW() — see PROJECT_HANDOFF.md §13.8.
 *  - Single-use: the token+expiry are cleared the instant a reset
 *    succeeds.
 *  - No account enumeration: /admin/forgot ALWAYS reports the same
 *    "if that account exists..." outcome whether or not the user exists.
 *  - Inactive accounts can't reset (mirrors Auth::attempt's is_active
 *    gate): the lookup is active-only on both legs.
 *  - Minimal anti-abuse only (real throttling is roadmap #8): a fresh
 *    token is not issued while an unexpired one already exists, which
 *    also caps inbox spam to one live link per account per TTL window.
 *  - The reset link's origin is the CONFIGURED app.base_url, never the
 *    request Host header (which an attacker can forge to poison the
 *    emailed link); the request host is only a last-resort fallback if
 *    base_url is unset.
 *
 * Audited as user.password_reset_request / user.password_reset_complete
 * (the admin-initiated reset from #5 is the distinct user.password_reset).
 * The actor is anonymous, so these rows carry a null user_id by design.
 * The token and password are NEVER logged.
 */
final class PasswordResetController extends BaseController
{
    /** Reset-link validity window, in seconds (15 minutes). */
    private const TOKEN_TTL_SECONDS = 900;

    /** Minimum new-password length — mirrors the rule set in #5. */
    private const MIN_PASSWORD_LENGTH = 12;

    /** Identical outcome message regardless of whether the account exists. */
    private const GENERIC_SENT_MESSAGE =
        'If that account exists, a password-reset link is on its way. '
        . 'The link expires in 15 minutes.';

    // -----------------------------------------------------------------
    // Request a reset
    // -----------------------------------------------------------------

    /** GET /admin/forgot */
    public function showForgot(): void
    {
        if (Auth::check()) { $this->redirect('/admin'); return; }

        $notice = $_SESSION['_flash']['reset_notice'] ?? null;
        $error  = $_SESSION['_flash']['reset_error'] ?? null;
        unset($_SESSION['_flash']['reset_notice'], $_SESSION['_flash']['reset_error']);

        $this->render('auth/forgot', [
            'notice' => $notice,
            'error'  => $error,
        ], 'auth');
    }

    /** POST /admin/forgot */
    public function doForgot(): void
    {
        if (Auth::check()) { $this->redirect('/admin'); return; }

        $identifier = trim((string)$this->input('identifier', ''));

        if ($identifier === '') {
            $this->flash('reset_error', 'Please enter your username or email.');
            $this->redirect('/admin/forgot');
            return;
        }

        $user = User::findActiveByUsernameOrEmail($identifier);
        if ($user !== null) {
            $this->maybeIssueToken($user);
        }

        // Always the same result — never reveal whether the account exists.
        $this->flash('reset_notice', self::GENERIC_SENT_MESSAGE);
        $this->redirect('/admin/forgot');
    }

    /**
     * Issue + email a reset token for a known-active user, unless one is
     * already live (minimal anti-abuse; #8 owns real throttling).
     *
     * @param array<string,mixed> $user
     */
    private function maybeIssueToken(array $user): void
    {
        $now = time();

        if (!empty($user['password_reset_expires'])
            && strtotime((string)$user['password_reset_expires']) > $now) {
            return; // an unexpired link already exists; don't re-issue
        }

        $rawToken  = bin2hex(random_bytes(32));                 // 64 hex chars
        $tokenHash = hash('sha256', $rawToken);                  // 64 hex -> CHAR(64)
        $expiresAt = date('Y-m-d H:i:s', $now + self::TOKEN_TTL_SECONDS);

        User::setResetToken((int)$user['id'], $tokenHash, $expiresAt);

        $this->sendResetEmail(
            (string)$user['email'],
            (string)($user['display_name'] ?? ''),
            $rawToken
        );

        AuditLog::record('user.password_reset_request', 'user', (int)$user['id']);
    }

    /**
     * Send the plain-text reset email. Best-effort: Mailer swallows and
     * logs failures (mirrors AuditLog), so a mail problem never breaks
     * the request flow or leaks whether the address exists.
     */
    private function sendResetEmail(string $toEmail, string $displayName, string $rawToken): void
    {
        $church = (string)Settings::get('church_name', 'the church');
        $link   = $this->baseUrl() . '/admin/reset?token=' . $rawToken;
        $name   = $displayName !== '' ? $displayName : 'there';

        $subject = 'Password reset · ' . $church;
        $body =
            "Hello {$name},\n\n"
          . "We received a request to reset the password for your {$church} "
          . "website account.\n\n"
          . "To choose a new password, open this link (it expires in 15 minutes):\n\n"
          . "{$link}\n\n"
          . "If you did not request this, you can safely ignore this email — "
          . "your password will not change.\n";

        Mailer::send($toEmail, $subject, $body);
    }

    // -----------------------------------------------------------------
    // Complete a reset
    // -----------------------------------------------------------------

    /** GET /admin/reset?token=... */
    public function showReset(): void
    {
        if (Auth::check()) { $this->redirect('/admin'); return; }

        $rawToken = (string)($_GET['token'] ?? '');

        $this->render('auth/reset', [
            'valid' => $this->userForToken($rawToken) !== null,
            'done'  => false,
            'token' => $rawToken,
            'error' => null,
        ], 'auth');
    }

    /** POST /admin/reset */
    public function doReset(): void
    {
        if (Auth::check()) { $this->redirect('/admin'); return; }

        $rawToken  = (string)$this->input('token', '');
        $password  = (string)$this->input('password', '');
        $password2 = (string)$this->input('password_confirm', '');

        $user = $this->userForToken($rawToken);
        if ($user === null) {
            // Token missing/expired/already used between page load and submit.
            $this->renderReset(false, false, '', null);
            return;
        }

        if (mb_strlen($password) < self::MIN_PASSWORD_LENGTH) {
            $this->renderReset(true, false, $rawToken,
                'Password must be at least ' . self::MIN_PASSWORD_LENGTH . ' characters.');
            return;
        }
        if ($password !== $password2) {
            $this->renderReset(true, false, $rawToken,
                'The two passwords did not match.');
            return;
        }

        // Commit: set the new hash, then single-use clear the token.
        User::updatePasswordHash((int)$user['id'], password_hash($password, PASSWORD_ARGON2ID));
        User::clearResetToken((int)$user['id']);

        AuditLog::record('user.password_reset_complete', 'user', (int)$user['id']);

        // Self-contained success screen (with a Sign in link) rather than a
        // flash on the login page, which has no success channel.
        $this->renderReset(true, true, '', null);
    }

    // -----------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------

    private function renderReset(bool $valid, bool $done, string $token, ?string $error): void
    {
        $this->render('auth/reset', [
            'valid' => $valid,
            'done'  => $done,
            'token' => $token,
            'error' => $error,
        ], 'auth');
    }

    /**
     * Resolve a raw token to its active, unexpired user row, or null.
     * A well-formed token is exactly 64 lowercase hex chars; anything
     * else is rejected before the DB is touched.
     *
     * @return array<string,mixed>|null
     */
    private function userForToken(string $rawToken): ?array
    {
        if (!preg_match('/^[0-9a-f]{64}$/', $rawToken)) {
            return null;
        }
        $tokenHash = hash('sha256', $rawToken);
        $now       = date('Y-m-d H:i:s'); // PHP-bound; see §13.8
        return User::findByValidResetToken($tokenHash, $now);
    }

    /**
     * Canonical site origin for the absolute reset link. Read from the
     * configured app.base_url (NOT the request Host header, which an
     * attacker can forge to poison the emailed link). The request host is
     * only a last-resort fallback if base_url is unset.
     *
     * NOTE: app.base_url must reflect the current public host — the site
     * runs at settlemem.org / settleumc.org pre-cutover and settleumc.com
     * after DNS cutover, so this value changes at launch.
     */
    private function baseUrl(): string
    {
        $configured = rtrim((string)($GLOBALS['settle_config']['app']['base_url'] ?? ''), '/');
        if ($configured !== '') {
            return $configured;
        }
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host   = (string)($_SERVER['HTTP_HOST'] ?? 'localhost');
        return $scheme . '://' . $host;
    }
}
