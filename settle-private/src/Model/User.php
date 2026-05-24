<?php
declare(strict_types=1);
namespace Settle\Model;

use Settle\Database;

final class User
{
    public static function findByUsernameOrEmail(string $value): ?array
    {
        $stmt = Database::query(
            'SELECT * FROM users WHERE username = :u OR email = :e LIMIT 1',
            [':u' => $value, ':e' => $value]
        );
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function updatePasswordHash(int $id, string $hash): void
    {
        Database::query(
            'UPDATE users SET password_hash = :h WHERE id = :id',
            [':h' => $hash, ':id' => $id]
        );
    }

    public static function touchLastLogin(int $id): void
    {
        Database::query(
            'UPDATE users SET last_login_at = NOW() WHERE id = :id',
            [':id' => $id]
        );
    }
}