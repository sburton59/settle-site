<?php
declare(strict_types=1);

namespace Settle\Controller;

use Settle\AuditLog;
use Settle\Auth;
use Settle\Csrf;
use Settle\Features;
use Settle\Model\MenuItem;
use Settle\Model\Page;

/**
 * Menu admin CRUD.
 *
 * Lets editors and admins manage the public-facing navigation.
 * The data layer is \Settle\Model\MenuItem; the public-facing
 * facade is \Settle\Menu. This controller is admin-only.
 *
 * Patterns mirror StaffController and SlideshowController:
 *   - Single edit screen for both create and update
 *   - JSON reorder endpoint that accepts a flat list of triples
 *   - Hard delete with confirm-on-form (cascading to children)
 *   - All writes audit-logged via \Settle\AuditLog
 */
final class MenuController extends BaseController
{
    /** Maximum menu nesting: top level + two nested tiers. */
    private const MAX_TIERS = 3;

    /**
     * GET /admin/menu — list with drag-to-reorder.
     */
    public function index(): void
    {
        $rows = MenuItem::findAll();
        $tree = MenuItem::buildTree($rows);

        $this->render('admin/menu/index', [
            'tree' => $tree,
        ]);
    }

    /**
     * GET /admin/menu/new — blank edit form.
     */
    public function create(): void
    {
        $this->render('admin/menu/edit', [
            'item'         => $this->blank(),
            'parentChoices'=> $this->parentChoices(null),
            'urlChoices'   => $this->buildUrlRegistry(),
            'errors'       => [],
            'isNew'        => true,
        ]);
    }

    /**
     * POST /admin/menu — store new item.
     */
    public function store(): void
    {
        $data   = $this->readForm();
        $errors = $this->validate($data);

        if (!isset($errors['parent_id'])
            && ($depthErr = $this->depthError($data['parent_id'], 0)) !== null) {
            $errors['parent_id'] = $depthErr;
        }

        if ($errors) {
            $this->render('admin/menu/edit', [
                'item'         => $data,
                'parentChoices'=> $this->parentChoices(null),
                'urlChoices'   => $this->buildUrlRegistry(),
                'errors'       => $errors,
                'isNew'        => true,
            ]);
            return;
        }

        $id = MenuItem::create($data, (int)($_SESSION['user_id'] ?? 0));

        AuditLog::record(
            'menu.create',
            'menu_item',
            $id,
            ['label' => $data['label'], 'parent_id' => $data['parent_id']]
        );

        $this->flash('success', 'Menu item added.');
        $this->redirect('/admin/menu');
    }

    /**
     * GET /admin/menu/{id}/edit — populated edit form.
     */
    public function edit(array $params): void
    {
        $id   = (int)$params['id'];
        $item = MenuItem::findById($id);
        if (!$item) {
            http_response_code(404);
            echo 'Menu item not found.';
            return;
        }

        $this->render('admin/menu/edit', [
            'item'         => $item,
            'parentChoices'=> $this->parentChoices($id),
            'urlChoices'   => $this->buildUrlRegistry(),
            'errors'       => [],
            'isNew'        => false,
        ]);
    }

    /**
     * POST /admin/menu/{id} — update existing item.
     *
     * Parent change goes through reparent() (which does cycle
     * detection); label/url/target/active changes go through
     * update().
     */
    public function update(array $params): void
    {
        $id   = (int)$params['id'];
        $item = MenuItem::findById($id);
        if (!$item) {
            http_response_code(404);
            echo 'Menu item not found.';
            return;
        }

        $data   = $this->readForm();
        $errors = $this->validate($data);

        // Cycle check on reparent. Reparenting to one of the item's own
        // descendants (or itself) would create a cycle.
        $newParent = $data['parent_id'];
        if ($newParent !== null && $newParent !== '') {
            $newParentInt = (int)$newParent;
            if ($newParentInt === $id) {
                $errors['parent_id'] = 'A menu item cannot be its own parent.';
            } else {
                $descendants = MenuItem::descendantIds($id);
                if (in_array($newParentInt, $descendants, true)) {
                    $errors['parent_id'] = 'Cannot move this item under one of its descendants.';
                }
            }
        }

        // Tier-depth guard (after the cycle check, so we don't shadow it).
        if (!isset($errors['parent_id'])
            && ($depthErr = $this->depthError($newParent, $id)) !== null) {
            $errors['parent_id'] = $depthErr;
        }

        if ($errors) {
            // Preserve typed-in data on re-render.
            $form = array_merge($item, $data);
            $this->render('admin/menu/edit', [
                'item'         => $form,
                'parentChoices'=> $this->parentChoices($id),
                'urlChoices'   => $this->buildUrlRegistry(),
                'errors'       => $errors,
                'isNew'        => false,
            ]);
            return;
        }

        $userId = (int)($_SESSION['user_id'] ?? 0);

        MenuItem::update($id, $data, $userId);

        // Apply reparent if changed.
        $oldParent = $item['parent_id'] !== null ? (int)$item['parent_id'] : null;
        $newParentResolved = ($newParent === null || $newParent === '') ? null : (int)$newParent;
        if ($oldParent !== $newParentResolved) {
            MenuItem::reparent($id, $newParentResolved, $userId);
        }

        AuditLog::record(
            'menu.update',
            'menu_item',
            $id,
            [
                'label'      => $data['label'],
                'parent_id'  => $newParentResolved,
                'is_active'  => (int)!empty($data['is_active']),
            ]
        );

        $this->flash('success', 'Menu item updated.');
        $this->redirect('/admin/menu');
    }

    /**
     * POST /admin/menu/{id}/toggle — flip is_active without opening edit.
     */
    public function toggle(array $params): void
    {
        $id   = (int)$params['id'];
        $item = MenuItem::findById($id);
        if (!$item) {
            http_response_code(404);
            echo 'Menu item not found.';
            return;
        }

        $newActive = (int)$item['is_active'] === 1 ? false : true;
        MenuItem::setActive($id, $newActive, (int)($_SESSION['user_id'] ?? 0));

        AuditLog::record(
            'menu.toggle',
            'menu_item',
            $id,
            ['is_active' => $newActive ? 1 : 0]
        );

        $this->flash('success', $newActive ? 'Item shown.' : 'Item hidden.');
        $this->redirect('/admin/menu');
    }

    /**
     * POST /admin/menu/{id}/delete — hard delete (cascades to children).
     */
    public function destroy(array $params): void
    {
        $id   = (int)$params['id'];
        $item = MenuItem::findById($id);
        if (!$item) {
            http_response_code(404);
            echo 'Menu item not found.';
            return;
        }

        MenuItem::delete($id);

        AuditLog::record('menu.delete', 'menu_item', $id, ['label' => $item['label']]);

        $this->flash('success', 'Menu item deleted.');
        $this->redirect('/admin/menu');
    }

    /**
     * POST /admin/menu/reorder — JSON endpoint for SortableJS.
     *
     * Body: { "items": [ {id, parent_id|null, sort_order}, ... ] }
     * CSRF: X-CSRF-Token header (the router accepts both _csrf field
     * and this header — see PROJECT_HANDOFF.md §3.5).
     *
     * Returns: 204 No Content on success, JSON error otherwise.
     */
    public function reorder(): void
    {
        $raw  = file_get_contents('php://input') ?: '';
        $body = json_decode($raw, true);

        if (!is_array($body) || !isset($body['items']) || !is_array($body['items'])) {
            header('Content-Type: application/json', true, 400);
            echo json_encode(['error' => 'Malformed body']);
            return;
        }

        $items = [];
        foreach ($body['items'] as $row) {
            if (!is_array($row) || !isset($row['id'])) {
                header('Content-Type: application/json', true, 400);
                echo json_encode(['error' => 'Malformed item in body']);
                return;
            }
            $items[] = [
                'id'         => (int)$row['id'],
                'parent_id'  => (isset($row['parent_id']) && $row['parent_id'] !== null && $row['parent_id'] !== '')
                                ? (int)$row['parent_id'] : null,
                'sort_order' => (int)($row['sort_order'] ?? 0),
            ];
        }

        $userId = (int)($_SESSION['user_id'] ?? 0);

        // Server-side tier-depth backstop: reject a payload that would
        // nest any item deeper than MAX_TIERS, regardless of what the
        // client JS permitted.
        $parentOf = [];
        foreach ($items as $row) {
            $parentOf[$row['id']] = $row['parent_id'];
        }
        foreach ($items as $row) {
            $tier = 1;
            $pid  = $row['parent_id'];
            $seen = [];
            while ($pid !== null) {
                if (isset($seen[$pid])) {
                    break; // cycle guard
                }
                $seen[$pid] = true;
                $tier++;
                if ($tier > self::MAX_TIERS) {
                    header('Content-Type: application/json', true, 422);
                    echo json_encode(['error' => 'Menus can be at most three levels deep.']);
                    return;
                }
                $pid = $parentOf[$pid] ?? null;
            }
        }

        try {
            MenuItem::reorder($items, $userId);
        } catch (\Throwable $e) {
            error_log('Menu reorder failed: ' . $e->getMessage());
            header('Content-Type: application/json', true, 500);
            echo json_encode(['error' => 'Reorder failed']);
            return;
        }

        AuditLog::record(
            'menu.reorder',
            'menu_item',
            null,
            ['count' => count($items)]
        );

        http_response_code(204);
    }

    // -------------------------------------------------------------------
    // helpers
    // -------------------------------------------------------------------

    /**
     * Blank-form shape for the edit template.
     *
     * @return array<string, mixed>
     */
    private function blank(): array
    {
        return [
            'id'         => null,
            'label'      => '',
            'url'        => '',
            'parent_id'  => null,
            'sort_order' => 0,
            'target'     => '_self',
            'is_active'  => 1,
        ];
    }

    /**
     * Pull form fields off $_POST and resolve the URL.
     *
     * The form has three URL inputs and a radio that selects which one
     * is canonical:
     *   - url_source = 'picker' → take url_picker
     *   - url_source = 'custom' → take url_custom
     *   - url_source = 'none'   → empty url
     *
     * A hidden 'url' field is also kept in sync client-side, so if the
     * JS is functional we just read that. If JS is disabled (or fails)
     * the hidden field stays empty and we fall back to resolving from
     * url_source.
     *
     * @return array<string, mixed>
     */
    private function readForm(): array
    {
        $url       = trim((string)$this->input('url', ''));
        $urlSource = (string)$this->input('url_source', 'custom');

        // Server-side resolution as a fallback in case the JS didn't run.
        if ($url === '') {
            if ($urlSource === 'picker') {
                $url = trim((string)$this->input('url_picker', ''));
            } elseif ($urlSource === 'custom') {
                $url = trim((string)$this->input('url_custom', ''));
            }
            // 'none' → leave $url as ''
        }

        return [
            'label'     => trim((string)$this->input('label', '')),
            'url'       => $url,
            'parent_id' => $this->input('parent_id', null) ?: null,
            'target'    => (string)$this->input('target', '_self'),
            'is_active' => $this->input('is_active') ? 1 : 0,
        ];
    }

    /**
     * Validate a form submission.
     *
     * @param array<string, mixed> $data
     * @return array<string, string>  Field-keyed errors.
     */
    private function validate(array $data): array
    {
        $errors = [];

        if ($data['label'] === '') {
            $errors['label'] = 'Please enter a label.';
        } elseif (mb_strlen($data['label']) > 100) {
            $errors['label'] = 'Label must be 100 characters or fewer.';
        }

        // URL is optional (parent-only items have no URL), but if given
        // it must be reasonable.
        if ($data['url'] !== '' && mb_strlen($data['url']) > 500) {
            $errors['url'] = 'URL must be 500 characters or fewer.';
        }

        if (!in_array($data['target'], ['_self', '_blank'], true)) {
            $errors['target'] = 'Invalid link target.';
        }

        return $errors;
    }

    /**
     * Returns an error message if placing the item identified by
     * $movingId under $newParentId would push the menu past MAX_TIERS
     * tiers, or null if the placement is allowed. For a brand-new item
     * (no descendants yet) pass $movingId = 0.
     *
     * @param int|string|null $newParentId  Raw form value ('' / null = top level).
     */
    private function depthError(int|string|null $newParentId, int $movingId): ?string
    {
        if ($newParentId === null || $newParentId === '') {
            return null; // top level — always tier 1
        }
        $parentTier = MenuItem::tierOf((int) $newParentId);
        if ($parentTier === 0) {
            return null; // unknown parent; existence/cycle handled elsewhere
        }
        $movedTier = $parentTier + 1;
        $height    = $movingId > 0 ? MenuItem::subtreeHeight($movingId) : 0;
        if ($movedTier + $height > self::MAX_TIERS) {
            return 'Menus can be at most three levels deep. Pick a higher-level parent.';
        }
        return null;
    }

    /**
     * Build the list of items that are valid parent choices for a given
     * item being edited. For an existing item, exclude the item itself
     * and all its descendants (those would create cycles).
     *
     * @return array<int, array{id:int, label:string, depth:int}>
     */
    private function parentChoices(?int $editingId): array
    {
        $rows = MenuItem::findAll();
        $exclude = [];
        if ($editingId !== null) {
            $exclude = MenuItem::descendantIds($editingId);
        }

        // Build a depth-aware flat list so the <select> shows nesting.
        $byParent = [];
        foreach ($rows as $row) {
            $pid = $row['parent_id'] !== null ? (int)$row['parent_id'] : 0;
            $byParent[$pid][] = $row;
        }

        $out = [];
        $walk = function (int $pid, int $depth) use (&$walk, $byParent, $exclude, &$out) {
            if (empty($byParent[$pid])) {
                return;
            }
            foreach ($byParent[$pid] as $row) {
                $id = (int)$row['id'];
                if (in_array($id, $exclude, true)) {
                    continue;
                }
                $out[] = [
                    'id'    => $id,
                    'label' => (string)$row['label'],
                    'depth' => $depth,
                ];
                $walk($id, $depth + 1);
            }
        };
        $walk(0, 0);

        return $out;
    }

    /**
     * Build the registry of URL choices for the picker.
     *
     * Sources (in order):
     *   1. Feature-provided URLs (/, /staff, /prayer, /contact)
     *      filtered by \Settle\Features::enabled()
     *   2. Published pages from the `pages` table
     *   3. The form also accepts a free-text URL for externals.
     *
     * Each entry: ['url' => string, 'label' => string, 'group' => string]
     *
     * @return array<int, array{url:string, label:string, group:string}>
     */
    private function buildUrlRegistry(): array
    {
        $entries = [];

        // 1. Feature URLs.
        $featureUrls = [
            ['key' => null,      'url' => '/',         'label' => 'Home (root)'],
            ['key' => 'staff',   'url' => '/staff',    'label' => 'Staff directory'],
            ['key' => 'prayer',  'url' => '/prayer',   'label' => 'Prayer request form'],
            ['key' => 'contact', 'url' => '/contact',  'label' => 'Contact form'],
            ['key' => 'blog',    'url' => '/blog',     'label' => 'Blog index'],
            ['key' => 'calendar','url' => '/calendar', 'label' => 'Calendar'],
            ['key' => 'calendar','url' => '/calendar/list', 'label' => 'Calendar (list view)'],
            ['key' => null,      'url' => '/books/our-church', 'label' => 'History: Our Church (1976)'],
        ];
        foreach ($featureUrls as $fu) {
            if ($fu['key'] !== null && !Features::enabled($fu['key'])) {
                continue;
            }
            $entries[] = [
                'url'   => $fu['url'],
                'label' => $fu['label'],
                'group' => 'Site sections',
            ];
        }

        // 2. Published pages.
        if (Features::enabled('pages')) {
            $pages = Page::allPublished();
            foreach ($pages as $page) {
                $entries[] = [
                    'url'   => '/page/' . $page['slug'],
                    'label' => (string)$page['title'],
                    'group' => 'Pages',
                ];
            }
        }

        return $entries;
    }
}
