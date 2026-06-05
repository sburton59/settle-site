<?php
declare(strict_types=1);
namespace Settle;

use Settle\Model\User;

/**
 * Session-based authentication. Uses Argon2id for password hashing.
 */
final class Auth
{
    private const ROLE_RANK = ['author' => 1, 'editor' => 2, 'admin' => 3];

    /**
     * Memo so the per-request active recheck in check() hits the DB at most
     * once per request, even though check()/hasRole()/user() may be called
     * several times. Reset on logout.
     */
    private static bool $activeVerified = false;

    public static function attempt(string $usernameOrEmail, string $password, bool $remember): bool
    {
        $user = User::findByUsernameOrEmail($usernameOrEmail);
        if (!$user || (int)$user['is_active'] !== 1) {
            // Always do a dummy verify to keep timing roughly constant
            password_verify($password, '$argon2id$v=19$m=65536,t=4,p=1$dummy$dummy');
            return false;
        }
        if (!password_verify($password, $user['password_hash'])) {
            return false;
        }

        // Auto-upgrade hash if PHP's preferred params have moved on
        if (password_needs_rehash($user['password_hash'], PASSWORD_ARGON2ID)) {
            User::updatePasswordHash((int)$user['id'], password_hash($password, PASSWORD_ARGON2ID));
        }

        // Defeat session-fixation attacks
        session_regenerate_id(true);
        Csrf::rotate();

        $_SESSION['user_id']      = (int)$user['id'];
        $_SESSION['user_role']    = $user['role'];
        $_SESSION['user_display'] = $user['display_name'];

        $lifetime = $remember
            ? $GLOBALS['settle_config']['session']['lifetime_remember']
            : $GLOBALS['settle_config']['session']['lifetime_default'];
        $_SESSION['expires_at'] = time() + (int)$lifetime;

        // This session has just authenticated against a live is_active=1
        // row, so the per-request recheck below is already satisfied.
        self::$activeVerified = true;

        User::touchLastLogin((int)$user['id']);
        return true;
    }

    public static function check(): bool
    {
        if (empty($_SESSION['user_id'])) return false;
        if (!empty($_SESSION['expires_at']) && (int)$_SESSION['expires_at'] < time()) {
            self::logout();
            return false;
        }

        // Per-request active recheck (#5 / #8). An admin can deactivate or
        // delete an account whose owner is already signed in; without this,
        // that user would keep their access until the session expired. We
        // re-verify the account is still active once per request and tear
        // the session down the moment it isn't (or the row is gone).
        if (!self::$activeVerified) {
            if (!User::isActive((int)$_SESSION['user_id'])) {
                self::logout();
                return false;
            }
            self::$activeVerified = true;
        }

        return true;
    }

    public static function hasRole(string $minimum): bool
    {
        if (!self::check()) return false;
        $userRank = self::ROLE_RANK[$_SESSION['user_role'] ?? ''] ?? 0;
        $needRank = self::ROLE_RANK[$minimum] ?? 99;
        return $userRank >= $needRank;
    }

    public static function user(): ?array
    {
        if (!self::check()) return null;
        return [
            'id'      => (int)$_SESSION['user_id'],
            'role'    => (string)$_SESSION['user_role'],
            'display' => (string)$_SESSION['user_display'],
        ];
    }

    public static function logout(): void
    {
        self::$activeVerified = false;
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                (bool)$params['secure'], (bool)$params['httponly']);
        }
        session_destroy();
    }
}
