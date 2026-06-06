<?php
declare(strict_types=1);

namespace Settle;

/**
 * Audit log writer + reader. Records "who did what when" for security-relevant
 * actions across the admin panel, and (since roadmap #7) reads them back for
 * the admin-only audit-log viewer.
 *
 * Conventions:
 *   - action: short snake_case verb scoped by entity, e.g. 'prayer.reveal',
 *             'prayer.status_change', 'staff.delete'.
 *   - entity_type: snake_case singular noun, e.g. 'prayer_request', 'staff'.
 *   - entity_id: the affected row id, or null for actions not tied to one.
 *   - details: a small array of context to JSON-encode (e.g. ['from'=>'new','to'=>'prayed']).
 *
 * Errors are swallowed and logged via error_log() so an audit-write
 * failure never breaks the user-visible action. The audit log is a
 * paper trail, not a transaction participant.
 *
 * Reading (roadmap #7): query()/count() back AuditLogController's paginated,
 * filterable table; distinctActions()/distinctEntityTypes() populate its
 * filter dropdowns. The read side is kept here (rather than a separate
 * Model\AuditLogEntry) so the whole audit concern lives in one class.
 * Viewing the log is itself NOT audited (avoids self-referential noise,
 * mirrors the "routine syncs aren't audited" stance — see §9). created_at
 * is stored on the DB clock (record() uses SQL NOW()); the viewer displays
 * it verbatim and labels it as recorded, rather than converting timezones.
 */
final class AuditLog
{
    public static function record(
        string $action,
        string $entityType,
        ?int $entityId = null,
        array $details = []
    ): void {
        try {
            $userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
            $ip     = self::clientIp();

            Database::query(
                'INSERT INTO audit_log
                    (user_id, action, entity_type, entity_id, details, ip_address, created_at)
                 VALUES
                    (:uid, :act, :etype, :eid, :detail, :ip, NOW())',
                [
                    ':uid'    => $userId,
                    ':act'    => $action,
                    ':etype'  => $entityType,
                    ':eid'    => $entityId,
                    ':detail' => $details === []
                        ? null
                        : json_encode($details, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                    ':ip'     => $ip,
                ]
            );
        } catch (\Throwable $e) {
            // Never let an audit failure cascade.
            error_log('AuditLog::record failed: ' . $e->getMessage());
        }
    }

    // -----------------------------------------------------------------
    // Read side (roadmap #7) — paginated, filterable, read-only.
    // -----------------------------------------------------------------

    /**
     * One page of audit rows, newest first, with the actor's display_name
     * resolved via a LEFT JOIN (NULL for anonymous/system actions and for
     * deleted users, since user_id is ON DELETE SET NULL).
     *
     * Each row: id, user_id, actor_name (?string), action, entity_type,
     * entity_id, details (?string — raw JSON, decode at render), ip_address,
     * created_at.
     *
     * Filters (all optional) are validated/normalised by the caller and
     * composed into a fully parameterized WHERE here — no user input is ever
     * concatenated into SQL. Recognised keys:
     *   - action_prefix (string)  e.g. 'user.'  -> action LIKE 'user.%'
     *   - action        (string)  exact match   -> action = ?
     *   - entity_type   (string)  exact match
     *   - user_id       (int)     exact actor
     *   - date_from     (string)  'Y-m-d H:i:s'  -> created_at >= ?
     *   - date_to       (string)  'Y-m-d H:i:s'  -> created_at <= ?
     * (action_prefix and action are mutually exclusive; the caller sends one.)
     *
     * LIMIT/OFFSET cannot be bound as parameters with PDO emulation off, so
     * they are cast to (int) and inlined — see PROJECT_HANDOFF.md §9.
     */
    public static function query(array $filters, int $limit, int $offset): array
    {
        [$where, $params] = self::buildWhere($filters);

        $limit  = max(1, $limit);
        $offset = max(0, $offset);

        $sql = 'SELECT a.id, a.user_id, u.display_name AS actor_name,
                       a.action, a.entity_type, a.entity_id, a.details,
                       a.ip_address, a.created_at
                  FROM audit_log a
             LEFT JOIN users u ON u.id = a.user_id'
             . $where
             . ' ORDER BY a.id DESC'
             . ' LIMIT ' . (int)$limit . ' OFFSET ' . (int)$offset;

        return Database::query($sql, $params)->fetchAll();
    }

    /** Total rows matching the same filters (for the pager). */
    public static function count(array $filters): int
    {
        [$where, $params] = self::buildWhere($filters);

        $row = Database::query(
            'SELECT COUNT(*) AS c FROM audit_log a' . $where,
            $params
        )->fetch();

        return (int)($row['c'] ?? 0);
    }

    /** Distinct action verbs present in the log, A–Z — for the filter dropdown. */
    public static function distinctActions(): array
    {
        $rows = Database::query(
            'SELECT DISTINCT action FROM audit_log ORDER BY action ASC'
        )->fetchAll();
        return array_map(static fn($r) => (string)$r['action'], $rows);
    }

    /** Distinct entity types present in the log, A–Z — for the filter dropdown. */
    public static function distinctEntityTypes(): array
    {
        $rows = Database::query(
            'SELECT DISTINCT entity_type FROM audit_log ORDER BY entity_type ASC'
        )->fetchAll();
        return array_map(static fn($r) => (string)$r['entity_type'], $rows);
    }

    /**
     * Build the parameterized WHERE clause (leading ' WHERE ' or '') and its
     * bound params from a normalised filter array. Distinct, descriptive
     * placeholder names; no value is ever inlined.
     *
     * @return array{0:string,1:array<string,mixed>}
     */
    private static function buildWhere(array $filters): array
    {
        $clauses = [];
        $params  = [];

        if (isset($filters['action_prefix']) && $filters['action_prefix'] !== '') {
            // Strip LIKE metacharacters (%, _, \) so the prefix can't smuggle a
            // wildcard, then anchor with a trailing '%'. No ESCAPE clause is used,
            // which keeps the query identical on MySQL and the SQLite test harness
            // (the two disagree on backslash semantics inside LIKE). Valid prefixes
            // are server-derived and alphanumeric, so stripping is lossless here.
            // If stripping leaves nothing (e.g. a bare '%'), skip the clause rather
            // than emit a match-all LIKE.
            $prefix = self::stripLikeMeta((string)$filters['action_prefix']);
            if ($prefix !== '') {
                $clauses[] = 'a.action LIKE :action_prefix';
                $params[':action_prefix'] = $prefix . '%';
            }
        } elseif (isset($filters['action']) && $filters['action'] !== '') {
            $clauses[] = 'a.action = :action';
            $params[':action'] = (string)$filters['action'];
        }

        if (isset($filters['entity_type']) && $filters['entity_type'] !== '') {
            $clauses[] = 'a.entity_type = :entity_type';
            $params[':entity_type'] = (string)$filters['entity_type'];
        }

        if (isset($filters['user_id']) && $filters['user_id'] !== null) {
            $clauses[] = 'a.user_id = :user_id';
            $params[':user_id'] = (int)$filters['user_id'];
        }

        if (isset($filters['date_from']) && $filters['date_from'] !== '') {
            $clauses[] = 'a.created_at >= :date_from';
            $params[':date_from'] = (string)$filters['date_from'];
        }

        if (isset($filters['date_to']) && $filters['date_to'] !== '') {
            $clauses[] = 'a.created_at <= :date_to';
            $params[':date_to'] = (string)$filters['date_to'];
        }

        $where = $clauses === [] ? '' : ' WHERE ' . implode(' AND ', $clauses);
        return [$where, $params];
    }

    /** Remove LIKE metacharacters (%, _, \) from a prefix — portable across MySQL/SQLite. */
    private static function stripLikeMeta(string $value): string
    {
        return str_replace(['\\', '%', '_'], '', $value);
    }

    /**
     * Best-effort client IP. Stays simple — no proxy-header trust by default
     * since the site is on shared cPanel hosting without a known reverse proxy.
     */
    private static function clientIp(): ?string
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        if (!is_string($ip) || $ip === '') {
            return null;
        }
        // Trim to column width (varchar(45) supports IPv6).
        return substr($ip, 0, 45);
    }
}
