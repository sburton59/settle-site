<?php
declare(strict_types=1);
namespace Settle\Controller;

use Settle\Auth;
use Settle\RateLimiter;
use Settle\AuditLog;

final class AuthController extends BaseController
{
    /**
     * Shown (via the existing login_error flash channel) when a key is
     * throttled. Deliberately generic and non-enumerating — it never
     * reveals whether the username exists, only that this ip+username
     * pairing has tried too often.
     */
    private const THROTTLE_MESSAGE =
        'Too many sign-in attempts. Please wait a few minutes and try again.';

    public function showLogin(): void
    {
        if (Auth::check()) { $this->redirect('/admin'); return; }
        $error = $_SESSION['_flash']['login_error'] ?? null;
        unset($_SESSION['_flash']['login_error']);
        $this->render('auth/login', [
            'error'  => $error,
            'return' => $_GET['return'] ?? '/admin',
        ], 'auth');
    }

    public function doLogin(): void
    {
        $username = trim((string)$this->input('username', ''));
        $password = (string)$this->input('password', '');
        $remember = (bool)$this->input('remember', false);
        $return   = (string)$this->input('return', '/admin');

        // Don't follow attacker-controlled absolute URLs
        if (!preg_match('#^/[^/]#', $return)) $return = '/admin';

        // Throttle on (ip + username): one attacker IP can't lock every
        // account, and one targeted account can't be locked from everywhere.
        $ip  = (string)($_SERVER['REMOTE_ADDR'] ?? '');
        $key = RateLimiter::key($ip, $username);

        // Check BEFORE verifying. Blocked requests short-circuit here, so the
        // counter doesn't keep climbing while locked and the lockout is
        // audited exactly once (at the crossing below), not on every attempt.
        if (RateLimiter::tooMany($key)) {
            $this->flash('login_error', self::THROTTLE_MESSAGE);
            $this->redirect('/admin/login');
            return;
        }

        if (Auth::attempt($username, $password, $remember)) {
            RateLimiter::clear($key); // wipe the counter on a clean sign-in
            $this->redirect($return);
            return;
        }

        // Record the failure; audit the moment this key crosses the threshold.
        $count = RateLimiter::hit($key);
        if ($count === RateLimiter::MAX_ATTEMPTS) {
            AuditLog::record('auth.lockout', 'auth', null, ['scope' => 'login']);
        }

        $this->flash('login_error', 'Username or password not recognized.');
        $this->redirect('/admin/login');
    }

    public function logout(): void
    {
        Auth::logout();
        $this->redirect('/admin/login');
    }
}
