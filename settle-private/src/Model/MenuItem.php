<?php
declare(strict_types=1);

namespace Settle\Model;

use Settle\Database;
use PDO;

/**
 * Menu item record (one row in the menu_items table).
 *
 * Schema reference: settle-private/sql/schema.sql table #13.
 * Architectural design: PROJECT_HANDOFF.md §14.5.
 *
 * Pattern mirrors Settle\Model\Staff — the most recent admin-CRUD-with-
 * reorder model. Reorder is transactional. All access is via prepared
 * statements with distinct named placeholders (PDO emulation is off;
 * reusing a placeholder name throws).
 *
 * This model exposes the raw table state. Public-facing filtering
 * (hide inactive items, hide children of inactive parents) lives in
 * \Settle\Menu, not here.
 */
final class MenuItem
{
    /**
     * Return every row in display order: parents first by sort_order,
     * children grouped under each parent by sort_order.
     *
     * Ordering choice: NULL parent_id sorts first via the
     * `parent_id IS NOT NULL` trick (FALSE < TRUE in MySQL boolean
     * context), then by parent_id, then by sort_order. This gives
     * top-level items first, followed by each parent's children
     * grouped together — which is what buildTree() expects.
     *
     * @return array<int, array<string,mixed>>
     */
    public static function findAll(): array
    {
        $sql = "SELECT id, label, url, parent_id, sort_order, target, is_active,
                       updated_by, created_at, updated_at
                FROM menu_items
                ORDER BY (parent_id IS NOT NULL), parent_id, sort_order, id";
        $stmt = Database::query($sql);
        /** @var array<int, array<string,mixed>> $rows */
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $rows;
    }

    /**
     * Find one row by ID.
     *
     * @return array<string,mixed>|null
     */
    public static function findById(int $id): ?array
    {
        $stmt = Database::query(
            "SELECT id, label, url, parent_id, sort_order, target, is_active,
                    updated_by, created_at, updated_at
             FROM menu_items
             WHERE id = :id",
            [':id' => $id]
        );
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row === false ? null : $row;
    }

    /**
     * Create a new menu item. Returns the new row's ID.
     *
     * Sort order is computed as MAX(sort_order)+1 within the same
     * parent scope so newly created items appear at the end of their
     * sibling group, matching the Staff and Slideshow patterns.
     */
    public static function create(array $data, int $userId): int
    {
        $parentId = isset($data['parent_id']) && $data['parent_id'] !== ''
            ? (int) $data['parent_id']
            : null;

        // Compute next sort_order in this parent scope. Distinct from
        // the INSERT placeholders below.
        if ($parentId === null) {
            $stmt = Database::query(
                "SELECT COALESCE(MAX(sort_order), -1) + 1 AS next_order
                 FROM menu_items
                 WHERE parent_id IS NULL"
            );
        } else {
            $stmt = Database::query(
                "SELECT COALESCE(MAX(sort_order), -1) + 1 AS next_order
                 FROM menu_items
                 WHERE parent_id = :parent_for_next",
                [':parent_for_next' => $parentId]
            );
        }
        $nextOrder = (int) $stmt->fetchColumn();

        Database::query(
            "INSERT INTO menu_items
                (label, url, parent_id, sort_order, target, is_active, updated_by)
             VALUES
                (:label, :url, :parent_id, :sort_order, :target, :is_active, :updated_by)",
            [
                ':label'      => (string) ($data['label'] ?? ''),
                ':url'        => (string) ($data['url'] ?? ''),
                ':parent_id'  => $parentId,
                ':sort_order' => $nextOrder,
                ':target'     => self::normalizeTarget($data['target'] ?? null),
                ':is_active'  => !empty($data['is_active']) ? 1 : 0,
                ':updated_by' => $userId,
            ]
        );

        return (int) Database::pdo()->lastInsertId();
    }

    /**
     * Update an existing menu item by ID.
     *
     * Does NOT change sort_order — that is reorder()'s job. Does NOT
     * change parent_id from this call to avoid accidentally creating
     * a cycle or stranding children; reparenting goes through a
     * dedicated path in the controller that runs a cycle check first.
     */
    public static function update(int $id, array $data, int $userId): void
    {
        Database::query(
            "UPDATE menu_items SET
                label       = :label,
                url         = :url,
                target      = :target,
                is_active   = :is_active,
                updated_by  = :updated_by
             WHERE id = :id",
            [
                ':label'      => (string) ($data['label'] ?? ''),
                ':url'        => (string) ($data['url'] ?? ''),
                ':target'     => self::normalizeTarget($data['target'] ?? null),
                ':is_active'  => !empty($data['is_active']) ? 1 : 0,
                ':updated_by' => $userId,
                ':id'         => $id,
            ]
        );
    }

    /**
     * Reparent an item. Caller must have already verified no cycle
     * would be created (i.e. $newParentId is not a descendant of $id).
     * Passing null moves the item to top level.
     */
    public static function reparent(int $id, ?int $newParentId, int $userId): void
    {
        Database::query(
            "UPDATE menu_items SET
                parent_id  = :new_parent,
                updated_by = :updated_by
             WHERE id = :target_id",
            [
                ':new_parent' => $newParentId,
                ':updated_by' => $userId,
                ':target_id'  => $id,
            ]
        );
    }

    /**
     * Toggle is_active on an item.
     */
    public static function setActive(int $id, bool $isActive, int $userId): void
    {
        Database::query(
            "UPDATE menu_items SET
                is_active  = :is_active,
                updated_by = :updated_by
             WHERE id = :id",
            [
                ':is_active'  => $isActive ? 1 : 0,
                ':updated_by' => $userId,
                ':id'         => $id,
            ]
        );
    }

    /**
     * Delete an item. Children cascade-delete via the FK.
     */
    public static function delete(int $id): void
    {
        Database::query(
            "DELETE FROM menu_items WHERE id = :id",
            [':id' => $id]
        );
    }

    /**
     * Apply a new ordering to a set of menu items.
     *
     * Accepts an array of [id, parent_id, sort_order] triples (the
     * shape SortableJS-driven JS endpoints typically post). All
     * updates run inside one transaction so a partial failure can't
     * leave the menu half-reordered.
     *
     * @param array<int, array{id:int, parent_id:?int, sort_order:int}> $items
     */
    public static function reorder(array $items, int $userId): void
    {
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare(
                "UPDATE menu_items SET
                    parent_id  = :new_parent,
                    sort_order = :new_order,
                    updated_by = :updated_by
                 WHERE id = :target_id"
            );
            foreach ($items as $item) {
                $stmt->execute([
                    ':new_parent' => $item['parent_id'] !== null ? (int) $item['parent_id'] : null,
                    ':new_order'  => (int) $item['sort_order'],
                    ':updated_by' => $userId,
                    ':target_id'  => (int) $item['id'],
                ]);
            }
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Return the set of descendant IDs of a given node, inclusive of
     * the node itself. Used by the controller to verify a proposed
     * reparent operation does not create a cycle.
     *
     * One query for direct children at each level; two or three loop
     * iterations are typical for a real menu (nesting is capped at three
     * tiers by the UI, but this function does not assume that).
     *
     * @return array<int, int>
     */
    public static function descendantIds(int $id): array
    {
        $found = [$id];
        $frontier = [$id];

        while ($frontier !== []) {
            // Build a comma list of placeholders for this iteration.
            $placeholders = [];
            $params = [];
            foreach ($frontier as $i => $pid) {
                $name = ':p' . $i;
                $placeholders[] = $name;
                $params[$name] = $pid;
            }
            $sql = "SELECT id FROM menu_items
                    WHERE parent_id IN (" . implode(',', $placeholders) . ")";
            $stmt = Database::query($sql, $params);
            $next = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN, 0));
            if ($next === []) {
                break;
            }
            $found = array_merge($found, $next);
            $frontier = $next;
        }

        return $found;
    }

    /**
     * 1-based tier of an item within the menu tree: a top-level item is
     * tier 1, its child tier 2, a grandchild tier 3. Returns 0 if the
     * item is not found. Guards against accidental parent cycles.
     */
    public static function tierOf(int $id): int
    {
        $byId = [];
        foreach (self::findAll() as $row) {
            $byId[(int) $row['id']] = $row;
        }
        if (!isset($byId[$id])) {
            return 0;
        }

        $tier = 1;
        $seen = [];
        $pid  = $byId[$id]['parent_id'] !== null ? (int) $byId[$id]['parent_id'] : null;
        while ($pid !== null) {
            if (isset($seen[$pid]) || !isset($byId[$pid])) {
                break; // cycle or dangling parent — stop counting
            }
            $seen[$pid] = true;
            $tier++;
            $pid = $byId[$pid]['parent_id'] !== null ? (int) $byId[$pid]['parent_id'] : null;
        }
        return $tier;
    }

    /**
     * Height of the subtree rooted at $id, measured in extra tiers below
     * it: 0 for a leaf, 1 if it has children, 2 if it has grandchildren.
     * Used to check whether reparenting a node would exceed the tier cap.
     */
    public static function subtreeHeight(int $id): int
    {
        $byParent = [];
        foreach (self::findAll() as $row) {
            $pid = $row['parent_id'] !== null ? (int) $row['parent_id'] : 0;
            $byParent[$pid][] = (int) $row['id'];
        }

        $height = static function (int $node, int $guard) use (&$height, $byParent): int {
            if ($guard > 50 || empty($byParent[$node])) {
                return 0;
            }
            $max = 0;
            foreach ($byParent[$node] as $child) {
                $h = 1 + $height($child, $guard + 1);
                if ($h > $max) {
                    $max = $h;
                }
            }
            return $max;
        };

        return $height($id, 0);
    }

    /**
     * Build a nested tree from a flat list of rows.
     *
     * Pure function — does not query the database. Pass in the result
     * of findAll() (or a filtered subset). Each returned node carries
     * its original fields plus a 'children' array (always present,
     * possibly empty) so templates can iterate without isset() checks.
     *
     * @param array<int, array<string,mixed>> $rows
     * @return array<int, array<string,mixed>>
     */
    public static function buildTree(array $rows): array
    {
        // First pass: index by id, attach an empty children array.
        $byId = [];
        foreach ($rows as $row) {
            $row['children'] = [];
            $byId[(int) $row['id']] = $row;
        }

        // Second pass: attach each child to its parent, collect roots.
        $roots = [];
        foreach ($byId as $id => &$node) {
            $parent = $node['parent_id'];
            if ($parent !== null && isset($byId[(int) $parent])) {
                $byId[(int) $parent]['children'][] = &$node;
            } else {
                $roots[] = &$node;
            }
        }
        unset($node);

        return $roots;
    }

    /**
     * Coerce a user-supplied target value to the ENUM domain.
     * Anything other than '_blank' becomes '_self'.
     */
    private static function normalizeTarget(mixed $target): string
    {
        return $target === '_blank' ? '_blank' : '_self';
    }
}
