<?php
declare(strict_types=1);

namespace Settle;

use Settle\Model\MenuItem;

/**
 * Public navigation facade.
 *
 * Templates consume this, NOT \Settle\Model\MenuItem directly. The
 * model exposes the raw table state (including hidden items); this
 * class returns the filtered, ready-for-render tree.
 *
 * See PROJECT_HANDOFF.md §14.5. Core provides the data; per-site
 * templates render the HTML. The shape of each returned node:
 *
 *   [
 *     'id'         => int,
 *     'label'      => string,
 *     'url'        => string,       // empty string = no link (parent only)
 *     'parent_id'  => ?int,
 *     'sort_order' => int,
 *     'target'     => '_self'|'_blank',
 *     'is_active'  => int (1),      // always 1 in this output
 *     'children'   => array<int, array<...>>,   // always present, possibly empty
 *   ]
 *
 * Inactive items are stripped recursively: a deactivated parent hides
 * its entire subtree regardless of child active flags.
 */
final class Menu
{
    /**
     * Return the active public navigation as a nested array.
     *
     * @return array<int, array<string,mixed>>
     */
    public static function renderTree(): array
    {
        $rows = MenuItem::findAll();

        // Filter to active rows whose ancestors are also all active.
        // Cheap because the row set is small (~40 max in practice).
        $byId = [];
        foreach ($rows as $row) {
            $byId[(int) $row['id']] = $row;
        }

        $active = [];
        foreach ($rows as $row) {
            if ((int) $row['is_active'] !== 1) {
                continue;
            }
            if (!self::ancestorsAllActive($row, $byId)) {
                continue;
            }
            $active[] = $row;
        }

        return MenuItem::buildTree($active);
    }

    /**
     * Walk up the parent chain. If any ancestor is inactive, return
     * false. Used to suppress orphaned children of a hidden parent.
     *
     * @param array<string,mixed>             $row
     * @param array<int, array<string,mixed>> $byId
     */
    private static function ancestorsAllActive(array $row, array $byId): bool
    {
        $parentId = $row['parent_id'];
        // Guard against accidental cycles even though the schema and
        // the controller's reparent path both prevent them.
        $seen = [];
        while ($parentId !== null) {
            $pid = (int) $parentId;
            if (isset($seen[$pid])) {
                return false;
            }
            $seen[$pid] = true;

            if (!isset($byId[$pid])) {
                // Dangling parent reference (should not happen because
                // of the FK with ON DELETE CASCADE) — fail closed.
                return false;
            }
            if ((int) $byId[$pid]['is_active'] !== 1) {
                return false;
            }
            $parentId = $byId[$pid]['parent_id'];
        }
        return true;
    }
}
