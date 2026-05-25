<?php
/** @var array $slides */
?>
<div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:1em;">
    <h1 style="margin:0;">Homepage Slideshow</h1>
    <a href="/admin/slideshow/new" class="btn-primary" style="text-decoration:none;">+ Add Slide</a>
</div>

<?php if (empty($slides)): ?>
    <div style="background:#fff; padding:2em; text-align:center; border-radius:4px;">
        <p class="muted">No slides yet. Click "+ Add Slide" to choose your first image from the Media Library.</p>
    </div>
<?php else: ?>
    <p class="muted" style="margin-bottom:1em;">
        Drag slides to reorder. Inactive slides won't appear on the public homepage.
    </p>

    <ul id="slide-list" data-csrf="<?= htmlspecialchars(\Settle\Csrf::token(), ENT_QUOTES) ?>"
        style="list-style:none; padding:0; margin:0; display:grid; gap:0.75em;">
        <?php foreach ($slides as $s): ?>
            <li data-slide-id="<?= (int)$s['id'] ?>"
                style="background:#fff; border-radius:4px; box-shadow:0 1px 3px rgba(0,0,0,0.05);
                       padding:0.6em; display:flex; align-items:center; gap:1em;
                       cursor:move; <?= $s['is_active'] ? '' : 'opacity:0.5;' ?>">

                <!-- Drag handle -->
                <span class="muted" title="Drag to reorder"
                      style="font-size:1.5em; line-height:1; user-select:none; cursor:move;">
                    ⋮⋮
                </span>

                <!-- Thumbnail -->
                <div style="width:120px; height:80px; flex-shrink:0; background:#f0f0f0;
                            border-radius:3px; overflow:hidden;">
                    <img src="/uploads/<?= htmlspecialchars($s['media_filename'], ENT_QUOTES) ?>"
                         alt="<?= htmlspecialchars($s['media_alt'] ?? '', ENT_QUOTES) ?>"
                         loading="lazy"
                         style="width:100%; height:100%; object-fit:cover;">
                </div>

                <!-- Caption / link / status -->
                <div style="flex-grow:1; min-width:0;">
                    <div style="font-weight:500;
                                overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                        <?php if (!empty($s['caption'])): ?>
                            <?= htmlspecialchars($s['caption'], ENT_QUOTES) ?>
                        <?php else: ?>
                            <span class="muted">(no caption)</span>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($s['link_url'])): ?>
                        <div class="muted" style="font-size:0.85em;
                                  overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                            links to: <?= htmlspecialchars($s['link_url'], ENT_QUOTES) ?>
                        </div>
                    <?php endif; ?>
                    <div class="muted" style="font-size:0.85em;">
                        <?= $s['is_active'] ? '✓ Active' : '— Hidden' ?>
                    </div>
                </div>

                <!-- Actions -->
                <div style="display:flex; gap:0.4em; align-items:center;">
                    <a href="/admin/slideshow/<?= (int)$s['id'] ?>/edit"
                       style="text-decoration:none; padding:0.3em 0.6em;">Edit</a>
                    <form method="post" action="/admin/slideshow/<?= (int)$s['id'] ?>/toggle"
                          style="display:inline; margin:0;">
                        <?= \Settle\Csrf::field() ?>
                        <button type="submit" class="linklike" style="padding:0.3em 0.6em;">
                            <?= $s['is_active'] ? 'Hide' : 'Show' ?>
                        </button>
                    </form>
                    <form method="post" action="/admin/slideshow/<?= (int)$s['id'] ?>/delete"
                          style="display:inline; margin:0;">
                        <?= \Settle\Csrf::field() ?>
                        <button type="submit" class="linklike"
                                style="color:var(--error); padding:0.3em 0.6em;"
                                data-confirm="Remove this slide? The underlying image stays in the Media Library.">
                            Delete
                        </button>
                    </form>
                </div>
            </li>
        <?php endforeach; ?>
    </ul>

    <!-- SortableJS from jsdelivr — small, no jQuery, no API key. -->
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
    <script>
    (function () {
        'use strict';

        var list = document.getElementById('slide-list');
        if (!list || typeof Sortable === 'undefined') return;

        var csrfToken = list.getAttribute('data-csrf') || '';

        Sortable.create(list, {
            animation: 150,
            ghostClass: 'sortable-ghost',
            onEnd: function () {
                var ids = Array.prototype.map.call(
                    list.querySelectorAll('li[data-slide-id]'),
                    function (li) { return parseInt(li.getAttribute('data-slide-id'), 10); }
                );

                fetch('/admin/slideshow/reorder', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token':  csrfToken,
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
