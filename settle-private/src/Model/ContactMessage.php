<?php
declare(strict_types=1);

namespace Settle\Model;

use Settle\Database;

/**
 * Contact Message — public-intake submissions from the website's
 * Contact form, reviewed through the admin inbox.
 *
 * Simpler than PrayerRequest: there is no privacy flag and no
 * status workflow. State is a single boolean (is_read). The admin
 * inbox uses three filter tabs: unread / read / all.
 */
final class ContactMessage
{
    /** Allowed reply_method values. Used to validate user input before SQL. */
    public const REPLY_METHODS = ['email', 'phone', 'either'];

    /**
     * Fetch messages, optionally filtered by read state. Most-recent first.
     *
     * @param bool|null $unreadOnly  null = all rows; true = unread only;
     *                               false = read only.
     */
    public static function all(?bool $unreadOnly = null, int $limit = 200, int $offset = 0): array
    {
        $limit  = max(1, min(500, $limit));
        $offset = max(0, $offset);

        if ($unreadOnly === null) {
            return Database::query(
                'SELECT id, sender_name, sender_email, sender_phone,
                        reply_method, message_text, ip_address,
                        is_read, submitted_at
                   FROM contact_messages
                  ORDER BY submitted_at DESC, id DESC
                  LIMIT ' . $limit . ' OFFSET ' . $offset
            )->fetchAll();
        }

        return Database::query(
            'SELECT id, sender_name, sender_email, sender_phone,
                    reply_method, message_text, ip_address,
                    is_read, submitted_at
               FROM contact_messages
              WHERE is_read = :flag
              ORDER BY submitted_at DESC, id DESC
              LIMIT ' . $limit . ' OFFSET ' . $offset,
            [':flag' => $unreadOnly ? 0 : 1]
        )->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $row = Database::query(
            'SELECT id, sender_name, sender_email, sender_phone,
                    reply_method, message_text, ip_address,
                    is_read, submitted_at
               FROM contact_messages
              WHERE id = :id',
            [':id' => $id]
        )->fetch();

        return $row ?: null;
    }

    /**
     * Insert a new submission. Returns the new id.
     *
     * Expected $data keys: sender_name (required), sender_email,
     * sender_phone, reply_method, message_text, ip_address.
     */
    public static function create(array $data): int
    {
        $name   = isset($data['sender_name']) ? trim((string)$data['sender_name']) : '';
        $email  = isset($data['sender_email']) ? trim((string)$data['sender_email']) : '';
        $phone  = isset($data['sender_phone']) ? trim((string)$data['sender_phone']) : '';
        $reply  = isset($data['reply_method']) ? (string)$data['reply_method'] : 'email';
        $text   = isset($data['message_text']) ? trim((string)$data['message_text']) : '';
        $ip     = isset($data['ip_address']) ? (string)$data['ip_address'] : null;

        // Defense-in-depth: never let an unexpected reply_method reach the ENUM column.
        if (!in_array($reply, self::REPLY_METHODS, true)) {
            $reply = 'email';
        }

        Database::query(
            'INSERT INTO contact_messages
                (sender_name, sender_email, sender_phone, reply_method,
                 message_text, ip_address, is_read, submitted_at)
             VALUES
                (:name, :email, :phone, :reply,
                 :body, :ip, 0, NOW())',
            [
                ':name'  => $name,
                ':email' => $email === '' ? null : $email,
                ':phone' => $phone === '' ? null : $phone,
                ':reply' => $reply,
                ':body'  => $text,
                ':ip'    => $ip !== null && $ip !== '' ? $ip : null,
            ]
        );

        self::clearUnreadCache();
        return (int)Database::pdo()->lastInsertId();
    }

    public static function markRead(int $id): bool
    {
        $stmt = Database::query(
            'UPDATE contact_messages
                SET is_read = 1
              WHERE id = :id
                AND is_read = 0',
            [':id' => $id]
        );
        if ($stmt->rowCount() > 0) {
            self::clearUnreadCache();
            return true;
        }
        return false;
    }

    public static function markUnread(int $id): bool
    {
        $stmt = Database::query(
            'UPDATE contact_messages
                SET is_read = 0
              WHERE id = :id
                AND is_read = 1',
            [':id' => $id]
        );
        if ($stmt->rowCount() > 0) {
            self::clearUnreadCache();
            return true;
        }
        return false;
    }

    public static function delete(int $id): bool
    {
        $stmt = Database::query(
            'DELETE FROM contact_messages WHERE id = :id',
            [':id' => $id]
        );
        if ($stmt->rowCount() > 0) {
            self::clearUnreadCache();
            return true;
        }
        return false;
    }

    /**
     * Number of unread messages. Memoized per request for the
     * sidebar badge, which reads on every admin page render.
     */
    public static function countUnread(): int
    {
        static $cache = null;
        if ($cache !== null) {
            return $cache;
        }

        $row = Database::query(
            'SELECT COUNT(*) AS n
               FROM contact_messages
              WHERE is_read = 0'
        )->fetch();

        $cache = (int)($row['n'] ?? 0);
        return $cache;
    }

    /**
     * Reset the countUnread memoization. Called by write paths so the
     * sidebar badge reflects the change on the next render within the
     * same request (rare — most writes redirect, which is a new request).
     *
     * Implemented as a no-op for now because the static $cache in
     * countUnread() lives for the whole request and write paths are
     * almost always followed by a redirect. This method exists so
     * future code has a documented hook if the memoization changes.
     */
    public static function clearUnreadCache(): void
    {
        // Intentionally empty — see docblock.
    }
}
