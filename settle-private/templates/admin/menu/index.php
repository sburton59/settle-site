<?php
/**
 * Admin: Menu list (drag-to-reorder).
 *
 * @var array   $tree   Nested tree of all menu items (active + inactive)
 * @var Closure $e
 */

use Settle\Csrf;

// Recursive renderer for menu items as nested sortable lists.
$renderList = static function (array $items, ?int $parentId, Closure $e, int $depth = 0) use (&$renderList): string {
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

        // Nested list — rendered down to a third tier (depth 0,1; the
        // tier-3 sublist at depth 2 is the deepest the design allows).
        // We always render the sublist container so SortableJS has a drop
        // target, even when empty.
        if ($depth < 2) {
            $out .= $renderList($item['children'], $id, $e, $depth + 1);
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
    // Recursively walk the rendered lists (root + nested sublists),
    // building flat [{id, parent_id, sort_order}, ...] triples that
    // capture the current nesting down to the third tier.
    var items = [];

    var root = document.querySelector('.menu-admin__list--root');
    if (!root) return items;

    function walk(ul, parentId) {
      Array.prototype.forEach.call(ul.children, function (li, idx) {
        var id = parseInt(li.getAttribute('data-id'), 10);
        if (isNaN(id)) return;
        items.push({ id: id, parent_id: parentId, sort_order: (idx + 1) * 10 });
        var sub = li.querySelector(':scope > .menu-admin__sublist');
        if (sub) {
          walk(sub, id);
        }
      });
    }
    walk(root, null);
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
  // drags between root and sublists; the `put` rule caps nesting at
  // three tiers (top level + two nested levels).
  Array.prototype.forEach.call(lists, function (list) {
    var isRoot = list.classList.contains('menu-admin__list--root');
    new Sortable(list, {
      group: {
        name: 'menu-items',
        pull: true,
        put: function (to, from, dragged) {
          // Permit the drop only if it keeps the menu within three tiers.
          // List depth: root = 0, first sublist = 1, second sublist = 2.
          // An item dropped into a list of depth d becomes tier (d + 1);
          // its own subtree adds `height` more tiers. Require
          // (d + 1 + height) <= 3.
          var d = 0, el = to.el;
          while (el) {
            if (el.classList && el.classList.contains('menu-admin__sublist')) { d++; }
            el = el.parentElement;
          }
          function height(li) {
            var sub = li.querySelector(':scope > .menu-admin__sublist');
            if (!sub) { return 0; }
            var max = 0;
            Array.prototype.forEach.call(sub.children, function (c) {
              var h = 1 + height(c);
              if (h > max) { max = h; }
            });
            return max;
          }
          return (d + 1 + height(dragged)) <= 3;
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
