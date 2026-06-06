<?php
declare(strict_types=1);
namespace Settle;

/**
 * Windowed attempt limiter (roadmap #8).
 *
 * Backs the admin login throttle and the password-reset request cap. It
 * counts timestamped "attempt" rows under an opaque key inside a rolling
 * time window and reports when that count has reached a threshold.
 *
 * Design notes:
 *  - DEPENDENCY-FREE and FAIL-OPEN. A limiter problem (DB hiccup, missing
 *    table, etc.) must NEVER lock out a legitimate user or crash a sign-in:
 *    every method swallows + error_log()s its own failures, and tooMany()
 *    returns false on error so the worst case is "no throttling", never
 *    "no login". This mirrors the best-effort philosophy of \Settle\Mailer
 *    and \Settle\AuditLog.
 *  - The KEY is opaque: sha256(ip . '|' . lower(identifier)) (see key()).
 *    Hashing gives a fixed-width CHAR(64) index and keeps attacker-supplied
 *    identifiers / email PII out of the login_attempts table. Keying on
 *    BOTH ip and identifier means one attacker IP can't lock every account,
 *    and one targeted account can't be locked out from everywhere.
 *  - TIME DISCIPLINE (PROJECT_HANDOFF.md §13.8): the window is computed in
 *    PHP (app timezone) and bound as :since / :now — never SQL NOW() — so
 *    the stored timestamp and the comparison share one clock even when the
 *    DB server runs a different timezone.
 *  - The table only grows, so hit() opportunistically prunes rows older than
 *    RETENTION_SECONDS (no cron needed). RETENTION is independent of the
 *    count window so a short-window caller can never delete rows a
 *    longer-window caller still needs.
 *
 * Storage: the login_attempts table (id, attempt_key CHAR(64), created_at),
 * no foreign key — attempts are pre-auth and may name a user that doesn't
 * exist. See sql/migrations/0004_add_login_attempts.sql.
 */
final class RateLimiter
{
    /** Default failure threshold for the admin login (matches the §7 note). */
    public const MAX_ATTEMPTS = 5;

    /** Default rolling window, in seconds (15 minutes). */
    public const WINDOW_SECONDS = 900;

    /** How long any attempt row is kept before opportunistic pruning (24h). */
    private const RETENTION_SECONDS = 86400;

    /**
     * Build the opaque limiter key from a client IP and an identifier.
     * The identifier is the typed username/email for login, or a fixed
     * literal (e.g. 'forgot') for an IP-only request cap.
     */
    public static function key(string $ip, string $identifier): string
    {
        $normalized = $ip . '|' . mb_strtolower(trim($identifier));
        return hash('sha256', $normalized);
    }

    /**
     * Has this key reached the threshold within the window? Fail-open:
     * any error returns false (so a limiter problem never bars a valid
     * sign-in).
     */
    public static function tooMany(
        string $key,
        int $max = self::MAX_ATTEMPTS,
        int $windowSeconds = self::WINDOW_SECONDS
    ): bool {
        try {
            $since = date('Y-m-d H:i:s', time() - $windowSeconds); // PHP-bound, §13.8
            $stmt  = Database::query(
                'SELECT COUNT(*) AS c
                   FROM login_attempts
                  WHERE attempt_key = :k
                    AND created_at > :since',
                [':k' => $key, ':since' => $since]
            );
            $row = $stmt->fetch();
            return ((int)($row['c'] ?? 0)) >= $max;
        } catch (\Throwable $e) {
            error_log('RateLimiter::tooMany failed (failing open): ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Record one attempt for this key and return the resulting in-window
     * count. The caller can compare the return value against its threshold
     * to detect the exact moment a key crosses into "locked" (so a lockout
     * can be audited once, not on every subsequent blocked request — blocked
     * requests short-circuit before hit(), so the count doesn't run away).
     *
     * Fail-open: returns 0 on error, which reads as "no crossing", so a
     * limiter failure neither audits a phantom lockout nor blocks anyone.
     */
    public static function hit(string $key, int $windowSeconds = self::WINDOW_SECONDS): int
    {
        try {
            $now = time();

            // Opportunistic prune — independent of $windowSeconds so a short
            // window can't delete rows a longer-window caller still needs.
            $cutoff = date('Y-m-d H:i:s', $now - self::RETENTION_SECONDS);
            Database::query(
                'DELETE FROM login_attempts WHERE created_at < :cutoff',
                [':cutoff' => $cutoff]
            );

            $nowStr = date('Y-m-d H:i:s', $now); // PHP-bound, §13.8
            Database::query(
                'INSERT INTO login_attempts (attempt_key, created_at) VALUES (:k, :now)',
                [':k' => $key, ':now' => $nowStr]
            );

            $since = date('Y-m-d H:i:s', $now - $windowSeconds);
            $stmt  = Database::query(
                'SELECT COUNT(*) AS c
                   FROM login_attempts
                  WHERE attempt_key = :k
                    AND created_at > :since',
                [':k' => $key, ':since' => $since]
            );
            $row = $stmt->fetch();
            return (int)($row['c'] ?? 0);
        } catch (\Throwable $e) {
            error_log('RateLimiter::hit failed: ' . $e->getMessage());
            return 0;
        }
    }

    /** Clear a key's attempts (call on a successful sign-in). Best-effort. */
    public static function clear(string $key): void
    {
        try {
            Database::query(
                'DELETE FROM login_attempts WHERE attempt_key = :k',
                [':k' => $key]
            );
        } catch (\Throwable $e) {
            error_log('RateLimiter::clear failed: ' . $e->getMessage());
        }
    }

    /**
     * Liveness probe for the limiter's storage. UNLIKE the hot-path methods
     * — which fail OPEN and stay silent so a DB problem can never bar a valid
     * sign-in — this method is allowed to REPORT a problem: it returns false
     * when the login_attempts table is missing or unreadable. The admin
     * dashboard uses it to surface a "login protection is not active" warning,
     * so a broken throttle doesn't go unnoticed (the silent-failure tradeoff
     * of fail-open). Cheap: a single trivial probe query, no writes.
     */
    public static function healthy(): bool
    {
        try {
            Database::query('SELECT 1 FROM login_attempts LIMIT 1');
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }
}
