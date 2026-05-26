<?php
/** @var array $staff */
?>
<div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:1em;">
  <h1 style="margin:0;">Staff Directory</h1>
  <a href="/admin/staff/new" class="btn-primary" style="text-decoration:none;">+ Add Staff Member</a>
</div>

<?php if (empty($staff)): ?>
  <div style="background:#fff; padding:2em; text-align:center; border-radius:4px;">
    <p class="muted">No staff members yet. Click "+ Add Staff Member" to add the first one.</p>
  </div>
<?php else: ?>

  <p class="muted" style="margin-bottom:1em;">
    Drag staff members to reorder. Hidden entries won't appear on the public Staff page.
  </p>

  <ul id="staff-list" data-csrf="<?= htmlspecialchars(\Settle\Csrf::token(), ENT_QUOTES) ?>"
      style="list-style:none; padding:0; margin:0; display:grid; gap:0.75em;">
    <?php foreach ($staff as $p): ?>
      <li data-staff-id="<?= (int)$p['id'] ?>"
          style="background:#fff; border-radius:4px; box-shadow:0 1px 3px rgba(0,0,0,0.05);
                 padding:0.6em; display:flex; align-items:center; gap:1em;
                 cursor:move; <?= $p['is_visible'] ? '' : 'opacity:0.5;' ?>">

        <!-- Drag handle -->
        <span class="muted" title="Drag to reorder"
              style="font-size:1.5em; line-height:1; user-select:none; cursor:move;">
          &#8942;&#8942;
        </span>

        <!-- Thumbnail (or silhouette fallback) -->
        <div style="width:60px; height:60px; flex-shrink:0; background:#f0f0f0;
                    border-radius:50%; overflow:hidden;">
          <?php if (!empty($p['photo_filename'])): ?>
            <img src="/uploads/<?= htmlspecialchars($p['photo_filename'], ENT_QUOTES) ?>"
                 alt="<?= htmlspecialchars($p['photo_alt'] ?? '', ENT_QUOTES) ?>"
                 loading="lazy"
                 style="width:100%; height:100%; object-fit:cover;">
          <?php else: ?>
            <img src="/assets/img/silhouette.svg" alt=""
                 style="width:100%; height:100%; object-fit:cover;">
          <?php endif; ?>
        </div>

        <!-- Name / title / status -->
        <div style="flex-grow:1; min-width:0;">
          <div style="font-weight:500;
                      overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
            <?= htmlspecialchars($p['full_name'], ENT_QUOTES) ?>
          </div>
          <?php if (!empty($p['title'])): ?>
            <div class="muted" style="font-size:0.85em;
                                      overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
              <?= htmlspecialchars($p['title'], ENT_QUOTES) ?>
            </div>
          <?php endif; ?>
          <div class="muted" style="font-size:0.85em;">
            <?= $p['is_visible'] ? '&#10003; Visible' : '&mdash; Hidden' ?>
          </div>
        </div>

        <!-- Actions -->
        <div style="display:flex; gap:0.4em; align-items:center;">
          <a href="/admin/staff/<?= (int)$p['id'] ?>/edit"
             style="text-decoration:none; padding:0.3em 0.6em;">Edit</a>

          <form method="post" action="/admin/staff/<?= (int)$p['id'] ?>/toggle"
                style="display:inline; margin:0;">
            <?= \Settle\Csrf::field() ?>
            <button type="submit" class="linklike" style="padding:0.3em 0.6em;">
              <?= $p['is_visible'] ? 'Hide' : 'Show' ?>
            </button>
          </form>

          <form method="post" action="/admin/staff/<?= (int)$p['id'] ?>/delete"
                style="display:inline; margin:0;">
            <?= \Settle\Csrf::field() ?>
            <button type="submit" class="linklike"
                    style="color:var(--error); padding:0.3em 0.6em;"
                    data-confirm="Remove this staff member? Their photo stays in the Media Library.">
              Delete
            </button>
          </form>
        </div>
      </li>
    <?php endforeach; ?>
  </ul>

  <!-- SortableJS from jsdelivr -->
  <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
  <script>
    (function () {
      'use strict';
      var list = document.getElementById('staff-list');
      if (!list || typeof Sortable === 'undefined') return;

      var csrfToken = list.getAttribute('data-csrf') || '';

      Sortable.create(list, {
        animation: 150,
        ghostClass: 'sortable-ghost',
        onEnd: function () {
          var ids = Array.prototype.map.call(
            list.querySelectorAll('li[data-staff-id]'),
            function (li) { return parseInt(li.getAttribute('data-staff-id'), 10); }
          );

          fetch('/admin/staff/reorder', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-Token': csrfToken,
            },
            body: JSON.stringify({ ids: ids }),
            credentials: 'same-origin',
          }).then(function (r) {
            if (!r.ok) {
              alert('Could not save the new order. Refresh the page and try again.');
            }
          }).catch(function () {
            alert('Network error while saving order. Refresh and try again.');
          });
        },
      });
    })();
  </script>
  <style>
    .sortable-ghost { opacity: 0.3; }
  </style>
  
<?php endif; ?>