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