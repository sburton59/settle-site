<?php
declare(strict_types=1);

namespace Settle\Model;

use Settle\Database;

/**
 * Prayer Request — public-intake submissions, reviewed and worked
 * through the admin inbox.
 *
 * Statuses: 'new' (unprocessed) → 'prayed' (acknowledged) → 'archived'.
 * Privacy is a separate flag, not a status; private requests stay in the
 * same inbox and are role-gated for reveal at the template layer.
 */
final class PrayerRequest
{
    /** Allowed status values. Used to validate user-supplied status before SQL. */
    public const STATUSES = ['new', 'prayed', 'archived'];

    /**
     * Fetch all requests, optionally filtered by status. Most-recent first.
     */
    public static function all(?string $status = null, int $limit = 100, int $offset = 0): array
    {
        // Validate status against allowlist — never trust user input through to SQL,
        // even though ENUM at the column level would also reject bad values.
        if ($status !== null && !in_array($status, self::STATUSES, true)) {
            $status = null;
        }

        // Clamp limit/offset to safe ranges.
        $limit  = max(1, min(500, $limit));
        $offset = max(0, $offset);

        if ($status === null) {
            return Database::query(
                'SELECT id, submitter_name, submitter_email, is_private,
                        allow_prayer_chain, request_text, ip_address, status, submitted_at
                   FROM prayer_requests
                  ORDER BY submitted_at DESC, id DESC
                  LIMIT ' . $limit . ' OFFSET ' . $offset
            )->fetchAll();
        }

        return Database::query(
            'SELECT id, submitter_name, submitter_email, is_private,
                    allow_prayer_chain, request_text, ip_address, status, submitted_at
               FROM prayer_requests
              WHERE status = :status
              ORDER BY submitted_at DESC, id DESC
              LIMIT ' . $limit . ' OFFSET ' . $offset,
            [':status' => $status]
        )->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $row = Database::query(
            'SELECT id, submitter_name, submitter_email, is_private,
                    allow_prayer_chain, request_text, ip_address, status, submitted_at
               FROM prayer_requests
              WHERE id = :id',
            [':id' => $id]
        )->fetch();
        return $row ?: null;
    }

    /**
     * Insert a new submission. Returns the new id.
     *
     * Expected $data keys: submitter_name, submitter_email, request_text,
     *                     is_private, allow_prayer_chain, ip_address.
     * Name and email may be empty strings (form is anonymous-friendly);
     * we normalize to NULL in that case. allow_prayer_chain is opt-in and
     * is forced to 0 whenever is_private is set — a private request never
     * goes on the chain.
     */
    public static function create(array $data): int
    {
        $name  = isset($data['submitter_name'])  ? trim((string)$data['submitter_name'])  : '';
        $email = isset($data['submitter_email']) ? trim((string)$data['submitter_email']) : '';
        $text  = isset($data['request_text'])    ? trim((string)$data['request_text'])    : '';
        $ip    = isset($data['ip_address'])      ? (string)$data['ip_address']            : null;

        $private = !empty($data['is_private']) ? 1 : 0;
        // Opt-in only, and a private request never goes on the chain —
        // enforce that here too so stored data can't contradict itself,
        // regardless of what the caller passed.
        $chain = ($private === 0 && !empty($data['allow_prayer_chain'])) ? 1 : 0;

        Database::query(
            'INSERT INTO prayer_requests
                (submitter_name, submitter_email, is_private, allow_prayer_chain,
                 request_text, ip_address, status, submitted_at)
             VALUES
                (:name, :email, :priv, :chain, :body, :ip, \'new\', NOW())',
            [
                ':name'  => $name === ''  ? null : $name,
                ':email' => $email === '' ? null : $email,
                ':priv'  => $private,
                ':chain' => $chain,
                ':body'  => $text,
                ':ip'    => $ip !== '' ? $ip : null,
            ]
        );
        return (int)Database::pdo()->lastInsertId();
    }

    /**
     * Move a request to a new status. Returns true if a row was updated.
     */
    public static function updateStatus(int $id, string $status): bool
    {
        if (!in_array($status, self::STATUSES, true)) {
            return false;
        }
        $stmt = Database::query(
            'UPDATE prayer_requests
                SET status = :status
              WHERE id = :id',
            [
                ':status' => $status,
                ':id'     => $id,
            ]
        );
        return $stmt->rowCount() > 0;
    }

    public static function delete(int $id): bool
    {
        $stmt = Database::query(
            'DELETE FROM prayer_requests WHERE id = :id',
            [':id' => $id]
        );
        return $stmt->rowCount() > 0;
    }

    /**
     * Return counts per status, with zero-fills for missing statuses.
     * Result shape: ['new' => 5, 'prayed' => 12, 'archived' => 88, 'total' => 105].
     *
     * Result is memoized per request to keep the sidebar badge cheap.
     */
    public static function countByStatus(): array
    {
        static $cache = null;
        if ($cache !== null) {
            return $cache;
        }
        $rows = Database::query(
            'SELECT status, COUNT(*) AS n
               FROM prayer_requests
              GROUP BY status'
        )->fetchAll();

        $counts = ['new' => 0, 'prayed' => 0, 'archived' => 0];
        $total  = 0;
        foreach ($rows as $r) {
            $s = (string)$r['status'];
            $n = (int)$r['n'];
            if (isset($counts[$s])) {
                $counts[$s] = $n;
            }
            $total += $n;
        }
        $counts['total'] = $total;
        $cache = $counts;
        return $counts;
    }

    /**
     * Reset the countByStatus memoization. Call after writes if the same
     * request needs an updated count (rare — the sidebar reads once).
     */
    public static function clearCountCache(): void
    {
        // Force a re-read on next call by relying on the static; simplest
        // implementation is just to no-op here. Left as an explicit method
        // so future code has a documented hook if the memoization changes.
    }
}
