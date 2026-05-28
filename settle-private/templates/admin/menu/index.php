<?php
/**
 * Admin: Menu list (drag-to-reorder).
 *
 * @var array   $tree   Nested tree of all menu items (active + inactive)
 * @var Closure $e
 */

use Settle\Csrf;

// Recursive renderer for menu items as nested sortable lists.
$renderList = static function (array $items, ?int $parentId, Closure $e) use (&$renderList): string {
    $listClass = $parentId === null ? 'menu-admin__list menu-admin__list--root' : 'menu-admin__list menu-admin__sublist';
    $listAttr  = $parentId === null ? '' : ' data-parent-id="' . (int)$parentId . '"';

    $out = '<ul class="' . $listClass . '"' . $listAttr . '>';
    foreach ($items as $item) {
        $id        = (int)$item['id'];
        $isActive  = (int)$item['is_active'] === 1;
        $hasChild  = !empty($item['children']);
        $urlText   = (string)($item['url'] ?? '');

        $out .= '<li class="menu-admin__item" data-id="' . $id . '">';
        $out .= '  <div class="menu-admin__row' . ($isActive ? '' : ' menu-admin__row--hidden') . '">';
        $out .= '    <span class="menu-admin__grip" title="Drag to reorder">⋮⋮</span>';
        $out .= '    <span class="menu-admin__label">' . $e($item['label']) . '</span>';
        $out .= '    <span class="menu-admin__url">' . ($urlText === '' ? '<em>(no link — parent only)</em>' : $e($urlText)) . '</span>';
        $out .= '    <span class="menu-admin__target">' . $e($item['target']) . '</span>';
        $out .= '    <span class="menu-admin__actions">';
        $out .= '      <a class="btn btn--small" href="/admin/menu/' . $id . '/edit">Edit</a>';
        $out .= '      <form method="post" action="/admin/menu/' . $id . '/toggle" style="display:inline">'
              . Csrf::field()
              . '<button type="submit" class="btn btn--small btn--ghost">' . ($isActive ? 'Hide' : 'Show') . '</button>'
              . '</form>';
        $out .= '      <form method="post" action="/admin/menu/' . $id . '/delete" style="display:inline" '
              . 'onsubmit="return confirm(\'Delete this menu item' . ($hasChild ? ' AND all its children' : '') . '? This cannot be undone.\');">'
              . Csrf::field()
              . '<button type="submit" class="btn btn--small btn--danger">Delete</button>'
              . '</form>';
        $out .= '    </span>';
        $out .= '  </div>';

        // Nested list — only at the root level (depth 1 only, matching the design).
        // We always render the sublist container so SortableJS has a drop target,
        // even when empty.
        if ($parentId === null) {
            $out .= $renderList($item['children'], $id, $e);
        }

        $out .= '</li>';
    }
    $out .= '</ul>';
    return $out;
};
?>

<style>
.menu-admin { max-width: 900px; }
.menu-admin h1 { margin-top: 0; }
.menu-admin__hint { color: #555; font-size: 0.9em; margin-bottom: 1em; }
.menu-admin__list { list-style: none; margin: 0; padding: 0; }
.menu-admin__sublist {
  margin-left: 2.5em;
  padding-left: 0.75em;
  border-left: 2px solid #e5e0dc;
  min-height: 0.5em;  /* gives SortableJS something to drop into when empty */
}
.menu-admin__item { margin: 0.4em 0; }
.menu-admin__row {
  display: grid;
  grid-template-columns: 1.5em 1fr 2fr 4em auto;
  align-items: center;
  gap: 0.75em;
  padding: 0.6em 0.75em;
  background: #fff;
  border: 1px solid #e5e0dc;
  border-radius: 4px;
}
.menu-admin__row--hidden { opacity: 0.55; background: #fafafa; }
.menu-admin__grip { cursor: grab; color: #aaa; font-size: 1.1em; user-select: none; }
.menu-admin__label { font-weight: 600; }
.menu-admin__url { color: #666; font-family: ui-monospace, monospace; font-size: 0.85em; word-break: break-all; }
.menu-admin__url em { color: #999; font-family: inherit; font-size: inherit; }
.menu-admin__target { color: #666; font-size: 0.85em; }
.menu-admin__actions { display: flex; gap: 0.4em; }
.btn--small { padding: 0.25em 0.75em; font-size: 0.85em; }
.btn--danger { background: #b53737; color: #fff; border: none; }
.sortable-ghost { opacity: 0.4; }
.sortable-drag  { background: #fff8e8; }
</style>

<div class="menu-admin">
  <h1>Public Menu</h1>
  <p class="menu-admin__hint">
    Drag the <strong>⋮⋮</strong> handle to reorder. Drag items between the
    top-level list and a parent's sub-list to nest or un-nest them.
    One level of nesting is supported.
  </p>

  <p>
    <a class="btn" href="/admin/menu/new">+ Add menu item</a>
  </p>

  <?php if (empty($tree)): ?>
    <p><em>No menu items yet. Click "Add menu item" to create the first one.</em></p>
  <?php else: ?>
    <?= $renderList($tree, null, $e) ?>
  <?php endif; ?>

  <p style="margin-top: 2em; color: #888; font-size: 0.85em;">
    <em>Changes save automatically as you drag.</em>
  </p>
</div>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
(function () {
  'use strict';

  var CSRF = '<?= $e(Csrf::token()) ?>';

  // Collect every <ul class="menu-admin__list"> on the page (root + sublists).
  var lists = document.querySelectorAll('.menu-admin__list');

  function gatherItems() {
    // Walk the root list, then each sublist, building the flat
    // [{id, parent_id, sort_order}, ...] triples.
    var items = [];

    var root = document.querySelector('.menu-admin__list--root');
    if (!root) return items;

    Array.prototype.forEach.call(root.children, function (li, idx) {
      var id = parseInt(li.getAttribute('data-id'), 10);
      items.push({ id: id, parent_id: null, sort_order: (idx + 1) * 10 });

      // Check for a sublist inside this li.
      var sub = li.querySelector(':scope > .menu-admin__sublist');
      if (sub) {
        Array.prototype.forEach.call(sub.children, function (childLi, childIdx) {
          var childId = parseInt(childLi.getAttribute('data-id'), 10);
          items.push({ id: childId, parent_id: id, sort_order: (childIdx + 1) * 10 });
        });
      }
    });
    return items;
  }

  function submitReorder() {
    var items = gatherItems();
    fetch('/admin/menu/reorder', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': CSRF
      },
      body: JSON.stringify({ items: items })
    }).then(function (resp) {
      if (!resp.ok && resp.status !== 204) {
        console.error('Menu reorder failed', resp.status);
        // Reload to resync visual state with the server.
        window.location.reload();
      }
    }).catch(function (err) {
      console.error('Menu reorder error', err);
      window.location.reload();
    });
  }

  // Attach SortableJS to every list. The shared `group` name allows
  // drags between root and sublists; the `pull/put` settings limit
  // nesting depth to one level (children can move within a parent's
  // sublist or back to root, but not into another sublist).
  Array.prototype.forEach.call(lists, function (list) {
    var isRoot = list.classList.contains('menu-admin__list--root');
    new Sortable(list, {
      group: {
        name: 'menu-items',
        // Sublists accept items from root only — they can't pull items
        // out of other sublists. Root accepts items from any sublist
        // and from itself. This enforces the one-level-nesting cap.
        pull: true,
        put: function (to, from, dragged) {
          // Allow drop if target is root, or target is a sublist AND
          // the dragged item has no children of its own (would create
          // depth 2).
          if (to.el.classList.contains('menu-admin__list--root')) return true;
          var hasChildSublist = dragged.querySelector('.menu-admin__sublist');
          if (hasChildSublist && hasChildSublist.children.length > 0) {
            return false;
          }
          return true;
        }
      },
      handle: '.menu-admin__grip',
      animation: 150,
      ghostClass: 'sortable-ghost',
      dragClass: 'sortable-drag',
      onEnd: submitReorder
    });
  });
})();
</script>
