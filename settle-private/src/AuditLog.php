<?php
declare(strict_types=1);

namespace Settle;

/**
 * Audit log writer. Records "who did what when" for security-relevant
 * actions across the admin panel. The schema already defines an
 * audit_log table; nothing wrote to it until Prayer Requests landed.
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
