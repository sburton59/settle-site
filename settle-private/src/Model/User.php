<?php
declare(strict_types=1);
namespace Settle\Model;

use Settle\Database;

/**
 * User model.
 *
 * The login-path methods (findByUsernameOrEmail / updatePasswordHash /
 * touchLastLogin) are unchanged. Everything below the divider was added
 * for the admin User-Management UI (roadmap #5): list/find/create/update,
 * activate/deactivate, delete, uniqueness checks, and the active-admin
 * counters that back the "don't lock the church out" guards in
 * UserController. isActive() backs the per-request recheck in Auth::check()
 * so deactivating a signed-in user revokes access on their next request.
 *
 * The "Self-service password reset" block (roadmap #6b) backs
 * PasswordResetController: an active-only lookup, hashed-token storage,
 * a token-hash + expiry validator, and a single-use clear.
 */
final class User
{
    // -----------------------------------------------------------------
    // Login path (unchanged)
    // -----------------------------------------------------------------

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

    /**
     * Is this account still active? Used by Auth::check() once per request
     * so that an admin deactivating (or deleting) a currently signed-in
     * user revokes their access immediately, not just at next login. A
     * missing row (deleted user) reads as inactive.
     */
    public static function isActive(int $id): bool
    {
        $row = Database::query(
            'SELECT is_active FROM users WHERE id = :id LIMIT 1',
            [':id' => $id]
        )->fetch();
        return $row !== false && (int)$row['is_active'] === 1;
    }

    // -----------------------------------------------------------------
    // Self-service password reset (roadmap #6b)
    // -----------------------------------------------------------------

    /**
     * Look up an ACTIVE account by username or email. Inactive rows never
     * match (mirrors Auth::attempt's is_active gate) so a deactivated user
     * can't initiate a reset. Returns the full row (incl. the existing
     * reset token/expiry, so the caller can apply its "don't re-issue a
     * live token" guard) or null.
     */
    public static function findActiveByUsernameOrEmail(string $value): ?array
    {
        $row = Database::query(
            'SELECT * FROM users
               WHERE (username = :u OR email = :e) AND is_active = 1
               LIMIT 1',
            [':u' => $value, ':e' => $value]
        )->fetch();
        return $row ?: null;
    }

    /**
     * Store the reset token HASH (sha256 hex, CHAR(64)) and its absolute
     * expiry. The raw token is emailed; only its hash is persisted, so a
     * DB leak can't be replayed.
     */
    public static function setResetToken(int $id, string $tokenHash, string $expiresAt): void
    {
        Database::query(
            'UPDATE users
                SET password_reset_token = :t,
                    password_reset_expires = :e
              WHERE id = :id',
            [':t' => $tokenHash, ':e' => $expiresAt, ':id' => $id]
        );
    }

    /**
     * Resolve a token hash to its ACTIVE, UNEXPIRED user row, or null.
     * The expiry is compared against a PHP-bound :now (app timezone), not
     * SQL NOW() — see PROJECT_HANDOFF.md §13.8. The lookup is an indexed
     * equality on the sha256 of a 256-bit random token, so there is no
     * useful timing side-channel to defend against.
     */
    public static function findByValidResetToken(string $tokenHash, string $now): ?array
    {
        $row = Database::query(
            'SELECT * FROM users
               WHERE password_reset_token = :t
                 AND password_reset_expires IS NOT NULL
                 AND password_reset_expires > :now
                 AND is_active = 1
               LIMIT 1',
            [':t' => $tokenHash, ':now' => $now]
        )->fetch();
        return $row ?: null;
    }

    /** Clear the reset token + expiry (single-use: called right after a successful reset). */
    public static function clearResetToken(int $id): void
    {
        Database::query(
            'UPDATE users
                SET password_reset_token = NULL,
                    password_reset_expires = NULL
              WHERE id = :id',
            [':id' => $id]
        );
    }

    // -----------------------------------------------------------------
    // Admin user management (roadmap #5)
    // -----------------------------------------------------------------

    /** Defaults for a new-user form. */
    public static function blank(): array
    {
        return [
            'id'            => 0,
            'username'      => '',
            'email'         => '',
            'display_name'  => '',
            'role'          => 'author',
            'is_active'     => 1,
            'last_login_at' => null,
        ];
    }

    /** All users — admins first, then editors, then authors; A–Z within a role. */
    public static function all(): array
    {
        return Database::query(
            "SELECT id, username, email, display_name, role, is_active, last_login_at, created_at
               FROM users
              ORDER BY CASE role WHEN 'admin' THEN 1 WHEN 'editor' THEN 2 ELSE 3 END,
                       display_name ASC"
        )->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $row = Database::query(
            'SELECT id, username, email, display_name, role, is_active, last_login_at, created_at
               FROM users WHERE id = :id LIMIT 1',
            [':id' => $id]
        )->fetch();
        return $row ?: null;
    }

    /**
     * @param array{username:string,email:string,password_hash:string,display_name:string,role:string,is_active:int} $data
     */
    public static function create(array $data): int
    {
        Database::query(
            'INSERT INTO users (username, email, password_hash, display_name, role, is_active)
             VALUES (:username, :email, :password_hash, :display_name, :role, :is_active)',
            [
                ':username'      => $data['username'],
                ':email'         => $data['email'],
                ':password_hash' => $data['password_hash'],
                ':display_name'  => $data['display_name'],
                ':role'          => $data['role'],
                ':is_active'     => (int)$data['is_active'],
            ]
        );
        return (int)Database::pdo()->lastInsertId();
    }

    /**
     * Update identity fields only. The password is changed separately via
     * updatePasswordHash() so it is never read back or carried in this path.
     *
     * @param array{username:string,email:string,display_name:string,role:string,is_active:int} $data
     */
    public static function update(int $id, array $data): void
    {
        Database::query(
            'UPDATE users
                SET username = :username,
                    email = :email,
                    display_name = :display_name,
                    role = :role,
                    is_active = :is_active
              WHERE id = :id',
            [
                ':username'     => $data['username'],
                ':email'        => $data['email'],
                ':display_name' => $data['display_name'],
                ':role'         => $data['role'],
                ':is_active'    => (int)$data['is_active'],
                ':id'           => $id,
            ]
        );
    }

    public static function setActive(int $id, bool $active): void
    {
        Database::query(
            'UPDATE users SET is_active = :a WHERE id = :id',
            [':a' => $active ? 1 : 0, ':id' => $id]
        );
    }

    /**
     * Hard delete. MAY throw \PDOException: several tables reference
     * users(id) with ON DELETE RESTRICT (posts.author_id, pages.updated_by,
     * media.uploaded_by, calendar_event_overrides.updated_by), so a user
     * who has authored any of that content cannot be deleted. The caller
     * catches this and tells the admin to deactivate instead.
     */
    public static function delete(int $id): void
    {
        Database::query('DELETE FROM users WHERE id = :id', [':id' => $id]);
    }

    /** How many admins are currently active. */
    public static function countActiveAdmins(): int
    {
        $row = Database::query(
            "SELECT COUNT(*) AS c FROM users WHERE role = 'admin' AND is_active = 1"
        )->fetch();
        return (int)($row['c'] ?? 0);
    }

    public static function usernameExists(string $username, ?int $exceptId = null): bool
    {
        $sql    = 'SELECT 1 FROM users WHERE username = :u';
        $params = [':u' => $username];
        if ($exceptId !== null) { $sql .= ' AND id <> :id'; $params[':id'] = $exceptId; }
        $sql .= ' LIMIT 1';
        return (bool) Database::query($sql, $params)->fetch();
    }

    public static function emailExists(string $email, ?int $exceptId = null): bool
    {
        $sql    = 'SELECT 1 FROM users WHERE email = :e';
        $params = [':e' => $email];
        if ($exceptId !== null) { $sql .= ' AND id <> :id'; $params[':id'] = $exceptId; }
        $sql .= ' LIMIT 1';
        return (bool) Database::query($sql, $params)->fetch();
    }
}
