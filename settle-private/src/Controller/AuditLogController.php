<?php
declare(strict_types=1);

namespace Settle\Controller;

use Settle\Auth;
use Settle\AuditLog;
use Settle\Model\User;

/**
 * Admin Audit-Log viewer (roadmap #7).
 *
 * A read-only window onto the audit_log table: a paginated, reverse-
 * chronological list with optional filters (action exact/prefix, entity
 * type, actor, and a date range). There is NO write path here — the only
 * writer remains \Settle\AuditLog::record(), called from the controllers
 * that perform audited actions. Viewing the log is deliberately not itself
 * audited (avoids self-referential noise; mirrors the "routine syncs aren't
 * audited" stance in PROJECT_HANDOFF.md §9).
 *
 * Access: ADMIN-ONLY, like Settings (#4) and Users (#5). Core admin, not a
 * Features flag — enforced by the route-level role gate AND a defense-in-
 * depth Auth::hasRole('admin') check here.
 *
 * Security notes:
 *   - Every filter is normalised here and composed into a fully
 *     parameterized WHERE in AuditLog::query()/count(); no user input is
 *     concatenated into SQL.
 *   - The action-prefix value is whitelisted against the set of prefixes
 *     actually present in the log (the segment before the first dot), so an
 *     arbitrary ?action_prefix= can't reach the query as a wildcard.
 *   - details is a JSON column; the template json_decodes it and escapes
 *     every key/value via e() — it is never rendered as HTML.
 *   - LIMIT/OFFSET are integers cast and inlined in the model (§9).
 *
 * See §3.4 (roles), §3.5 (security baseline), §9 (conventions),
 * §13.9 (View::render key-collision — the 'values'/safe-key rule).
 */
final class AuditLogController extends BaseController
{
    private const PER_PAGE = 50;

    public function index(): void
    {
        if (!Auth::hasRole('admin')) {
            http_response_code(403);
            echo 'Forbidden.';
            return;
        }

        // ---- Available filter option sets (from the log + the user list) ----
        $actions      = AuditLog::distinctActions();
        $entityTypes  = AuditLog::distinctEntityTypes();
        $actors       = User::all(); // id, username, email, display_name, role, ...

        // Prefixes = the segment before the first dot of each known action,
        // e.g. 'user', 'prayer', 'calendar'. These back the "all X actions"
        // dropdown options and are the ONLY values accepted for action_prefix.
        $prefixes = [];
        foreach ($actions as $a) {
            $dot = strpos($a, '.');
            if ($dot !== false && $dot > 0) {
                $prefixes[substr($a, 0, $dot)] = true;
            }
        }
        $prefixes = array_keys($prefixes);
        sort($prefixes);

        // ---- Read + normalise the requested filters ----
        // The single "Action" dropdown carries both modes in one value:
        //   'user.*'      -> a group/prefix match (segment before the dot)
        //   'user.create' -> an exact action
        // Either way the chosen string is echoed back verbatim to re-select
        // the option; the model receives the clean action / action_prefix key.
        $rawAction = trim((string)$this->input('action', ''));
        $rawEntity = trim((string)$this->input('entity_type', ''));
        $rawUser   = trim((string)$this->input('user_id', ''));
        $rawFrom   = trim((string)$this->input('date_from', '')); // expect 'YYYY-MM-DD'
        $rawTo     = trim((string)$this->input('date_to', ''));   // expect 'YYYY-MM-DD'

        $filters = [];          // -> passed to the model (parameterized)
        $selected = [           // -> echoed back into the form (sticky filters)
            'action'      => '',
            'entity_type' => '',
            'user_id'     => '',
            'date_from'   => '',
            'date_to'     => '',
        ];

        if ($rawAction !== '') {
            if (str_ends_with($rawAction, '.*')) {
                $prefix = substr($rawAction, 0, -2);
                if (in_array($prefix, $prefixes, true)) {
                    $filters['action_prefix'] = $prefix . '.';
                    $selected['action']       = $rawAction;
                }
            } elseif (in_array($rawAction, $actions, true)) {
                $filters['action']  = $rawAction;
                $selected['action'] = $rawAction;
            }
        }

        if ($rawEntity !== '' && in_array($rawEntity, $entityTypes, true)) {
            $filters['entity_type']  = $rawEntity;
            $selected['entity_type'] = $rawEntity;
        }

        if ($rawUser !== '' && ctype_digit($rawUser)) {
            $uid = (int)$rawUser;
            // Only accept an actor id that exists in the user list.
            foreach ($actors as $u) {
                if ((int)$u['id'] === $uid) {
                    $filters['user_id']  = $uid;
                    $selected['user_id'] = (string)$uid;
                    break;
                }
            }
        }

        // Dates: accept a calendar day (YYYY-MM-DD), expand to inclusive
        // datetime bounds. created_at is on the DB clock (record() uses NOW());
        // we filter against that same stored value verbatim (no TZ conversion).
        $from = self::normaliseDay($rawFrom);
        if ($from !== null) {
            $filters['date_from']  = $from . ' 00:00:00';
            $selected['date_from'] = $from;
        }
        $to = self::normaliseDay($rawTo);
        if ($to !== null) {
            $filters['date_to']  = $to . ' 23:59:59';
            $selected['date_to'] = $to;
        }

        // ---- Pagination ----
        $total      = AuditLog::count($filters);
        $perPage    = self::PER_PAGE;
        $totalPages = max(1, (int)ceil($total / $perPage));

        $page = (int)$this->input('page', 1);
        if ($page < 1)            { $page = 1; }
        if ($page > $totalPages)  { $page = $totalPages; }
        $offset = ($page - 1) * $perPage;

        $rows = $total > 0 ? AuditLog::query($filters, $perPage, $offset) : [];

        // Build the query string for pager links, carrying the active filters
        // (but not the page number — that's appended per-link in the template).
        $baseQuery = array_filter([
            'action'      => $selected['action'],
            'entity_type' => $selected['entity_type'],
            'user_id'     => $selected['user_id'],
            'date_from'   => $selected['date_from'],
            'date_to'     => $selected['date_to'],
        ], static fn($v) => $v !== '');

        $this->render('admin/audit/index', [
            'rows'        => $rows,
            'actions'     => $actions,
            'prefixes'    => $prefixes,
            'entityTypes' => $entityTypes,
            'actors'      => $actors,
            'selected'    => $selected,
            'baseQuery'   => $baseQuery,
            'pagination'  => [
                'page'        => $page,
                'per_page'    => $perPage,
                'total'       => $total,
                'total_pages' => $totalPages,
                'from'        => $total === 0 ? 0 : $offset + 1,
                'to'          => min($offset + $perPage, $total),
            ],
        ]);
    }

    /**
     * Validate a 'YYYY-MM-DD' calendar day and return it normalised, or null
     * if absent/malformed (a real date that round-trips through DateTime).
     */
    private static function normaliseDay(string $value): ?string
    {
        if ($value === '') {
            return null;
        }
        $d = \DateTime::createFromFormat('!Y-m-d', $value);
        $errors = \DateTime::getLastErrors();
        if ($d === false
            || ($errors !== false && (($errors['warning_count'] ?? 0) > 0 || ($errors['error_count'] ?? 0) > 0))) {
            return null;
        }
        return $d->format('Y-m-d');
    }
}
