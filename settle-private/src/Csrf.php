<?php
declare(strict_types=1);
namespace Settle;

/**
 * CSRF token helpers. Token lives in the session and is rotated on login.
 */
final class Csrf
{
    public static function token(): string
    {
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf'];
    }

    public static function verify(string $token): bool
    {
        if (empty($_SESSION['_csrf']) || $token === '') return false;
        return hash_equals($_SESSION['_csrf'], $token);
    }

    /** Convenience for templates. */
    public static function field(): string
    {
        return '<input type="hidden" name="_csrf" value="'
             . htmlspecialchars(self::token(), ENT_QUOTES, 'UTF-8') . '">';
    }

    /** Call this on login (and any other privilege change) to defeat token-fixation. */
    public static function rotate(): void
    {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
}