<?php
/** @var array $items */
/** @var int $total */
/** @var int $page */
/** @var int $totalPages */
/** @var array $albums    Album::allForPicker() rows, [] if photo_albums feature is off */
/** @var int|null $albumId  Current filter, if any */
/** @var string $search */
$albums  = $albums ?? [];
$albumId = $albumId ?? null;
$search  = $search ?? '';

if (!function_exists('self_format_bytes')) {
    /**
     * Format bytes for friendly display. Locally scoped helper —
     * if more templates need this we'd promote it somewhere shared.
     * Guarded with function_exists() so a harness/test process that
     * renders this template more than once doesn't fatal on redeclare
     * (a normal request only ever includes this file once).
     */
    function self_format_bytes(int $bytes): string
    {
        if ($bytes < 1024)             return $bytes . ' B';
        if ($bytes < 1024 * 1024)      return round($bytes / 1024, 1) . ' KB';
        if ($bytes < 1024 * 1024 * 10) return round($bytes / 1024 / 1024, 1) . ' MB';
        return round($bytes / 1024 / 1024) . ' MB';
    }
}
?>
<div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:1em;">
    <h1 style="margin:0;">Photos &amp; Files</h1>
    <span class="muted"><?= (int)$total ?> file<?= $total === 1 ? '' : 's' ?></span>
</div>

<?php if (!empty($albums)): ?>
  <div style="background:#fff; padding:0.9em 1.2em; border-radius:4px;
             box-shadow:0 1px 3px rgba(0,0,0,0.05); margin-bottom:1em;
             display:flex; flex-wrap:wrap; gap:0.8em; align-items:flex-end;">
    <form method="get" action="/admin/media" style="display:flex; gap:0.6em; flex-wrap:wrap; align-items:flex-end;">
      <label style="margin:0;">Search
        <input type="text" name="q" value="<?= htmlspecialchars($search, ENT_QUOTES) ?>"
               placeholder="filename, caption, alt text…" style="max-width:16em;">
      </label>
      <label style="margin:0;">Album
        <select name="album" style="max-width:14em;">
          <option value="">All files</option>
          <?php foreach ($albums as $a): ?>
            <option value="<?= (int) $a['id'] ?>" <?= $albumId === (int) $a['id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($a['name'], ENT_QUOTES) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </label>
      <button type="submit" class="btn-primary">Filter</button>
      <?php if ($albumId !== null || $search !== ''): ?>
        <a href="/admin/media" style="align-self:center;">Clear</a>
      <?php endif; ?>
    </form>
    <a href="/admin/albums" style="margin-left:auto; align-self:center;">Manage Albums →</a>
  </div>
<?php endif; ?>

<?php
/*
 * Two upload surfaces, progressively enhanced:
 *   - #media-simple-form : a plain single-file POST to /admin/media. Always
 *     present; the only thing that works with JavaScript disabled.
 *   - #media-uploader    : a drag-and-drop / multi-file uploader, hidden by
 *     default and revealed by admin.js, which posts one file at a time to
 *     /admin/media/upload-ajax. When admin.js initializes it hides the simple
 *     form and shows this. The CSRF token is read from the simple form's
 *     hidden _csrf field and sent as an X-CSRF-Token header per request.
 */
?>
<form id="media-simple-form" method="post" action="/admin/media" enctype="multipart/form-data"
      style="background:#fff; padding:1em 1.2em; border-radius:4px;
             box-shadow:0 1px 3px rgba(0,0,0,0.05); margin-bottom:1.5em;">
    <?= \Settle\Csrf::field() ?>
    <label style="margin:0;">
        Upload a new file
        <input type="file" name="file" required
               accept="image/jpeg,image/png,image/gif,image/webp,application/pdf">
    </label>
    <div style="margin-top:0.6em;">
        <button type="submit" class="btn-primary">Upload</button>
        <span class="muted" style="margin-left:1em; font-size:0.9em;">
            JPEG, PNG, GIF, WebP, or PDF &middot; up to 10&nbsp;MB
        </span>
    </div>
</form>

<div id="media-uploader" class="uploader" data-upload-url="/admin/media/upload-ajax"
     data-accept="image/jpeg,image/png,image/gif,image/webp,application/pdf" hidden>
    <label class="uploader__drop" for="media-uploader__input">
        <strong>Drag &amp; drop files here</strong>
        <span class="muted">or click to choose &mdash; you can select several at once</span>
        <span class="muted" style="font-size:0.9em;">JPEG, PNG, GIF, WebP, or PDF &middot; up to 10&nbsp;MB each</span>
        <input id="media-uploader__input" type="file" multiple
               accept="image/jpeg,image/png,image/gif,image/webp,application/pdf"
               style="position:absolute; width:1px; height:1px; opacity:0; overflow:hidden;">
    </label>
    <ul class="uploader__list" id="media-uploader__list" aria-live="polite"></ul>
</div>

<?php if (!empty($albums)): ?>
  <form id="bulk-assign-form" method="post" action="/admin/media/bulk-assign"
        style="background:#fff; padding:0.8em 1.2em; border-radius:4px;
               box-shadow:0 1px 3px rgba(0,0,0,0.05); margin-bottom:1em;
               display:flex; flex-wrap:wrap; gap:0.7em; align-items:center;">
    <?= \Settle\Csrf::field() ?>
    <span class="muted" id="bulk-select-count">0 selected</span>
    <select name="album_id" required style="max-width:14em;">
      <option value="">Add to album…</option>
      <?php foreach ($albums as $a): ?>
        <option value="<?= (int) $a['id'] ?>"><?= htmlspecialchars($a['name'], ENT_QUOTES) ?></option>
      <?php endforeach; ?>
    </select>
    <button type="submit" class="btn-primary" id="bulk-assign-submit" disabled>Add Selected</button>
    <span class="muted" style="font-size:0.85em;">Select photos below with the checkboxes on each tile. PDFs and non-image files are skipped.</span>
  </form>
<?php endif; ?>

<?php if (empty($items)): ?>
    <div style="background:#fff; padding:2em; text-align:center; border-radius:4px;">
        <p class="muted"><?= ($albumId !== null || $search !== '') ? 'No files match this filter.' : 'No files yet. Upload your first one above.' ?></p>
    </div>
<?php else: ?>
    <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(180px, 1fr)); gap:1em;">
        <?php foreach ($items as $m): ?>
            <?php
                $isImage = strpos((string)$m['mime_type'], 'image/') === 0;
                $isPdf   = $m['mime_type'] === 'application/pdf';
                // Prefer the thumbnail variant for the grid; fall back to the
                // full-size file when there isn't one (PDFs, legacy rows).
                $thumbRel = !empty($m['thumbnail_filename']) ? $m['thumbnail_filename'] : $m['filename'];
                $thumbUrl = '/uploads/' . htmlspecialchars(ltrim((string)$thumbRel, '/'), ENT_QUOTES);
            ?>
            <div style="background:#fff; border-radius:4px; overflow:hidden; position:relative;
                        box-shadow:0 1px 3px rgba(0,0,0,0.05); display:flex; flex-direction:column;">
                <?php if (!empty($albums) && $isImage): ?>
                  <label style="position:absolute; top:0.4em; left:0.4em; z-index:2; background:rgba(255,255,255,0.9);
                                border-radius:3px; padding:0.15em 0.3em; cursor:pointer;">
                    <input type="checkbox" form="bulk-assign-form" name="media_ids[]" value="<?= (int) $m['id'] ?>"
                           class="bulk-select-checkbox">
                  </label>
                <?php endif; ?>
                <a href="/admin/media/<?= (int)$m['id'] ?>/edit"
                   style="display:block; aspect-ratio:1/1; background:#f0f0f0;
                          display:flex; align-items:center; justify-content:center;
                          overflow:hidden;">
                    <?php if ($isImage): ?>
                        <img src="<?= $thumbUrl ?>"
                             alt="<?= htmlspecialchars($m['alt_text'] ?? '', ENT_QUOTES) ?>"
                             loading="lazy"
                             style="width:100%; height:100%; object-fit:cover;">
                    <?php elseif ($isPdf): ?>
                        <div style="text-align:center; color:var(--gray-400);">
                            <div style="font-size:3em;">📄</div>
                            <div style="font-size:0.85em;">PDF</div>
                        </div>
                    <?php else: ?>
                        <div style="color:var(--gray-400);">file</div>
                    <?php endif; ?>
                </a>
                <div style="padding:0.5em 0.7em; font-size:0.85em;">
                    <div style="overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"
                         title="<?= htmlspecialchars($m['original_name'], ENT_QUOTES) ?>">
                        <?= htmlspecialchars($m['original_name'], ENT_QUOTES) ?>
                    </div>
                    <div class="muted" style="font-size:0.85em; margin-top:0.2em;">
                        <?php if ($isImage && !empty($m['width'])): ?>
                            <?= (int)$m['width'] ?>&times;<?= (int)$m['height'] ?> &middot;
                        <?php endif; ?>
                        <?= self_format_bytes((int)$m['file_size']) ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <?php
        $qs = [];
        if ($albumId !== null) { $qs['album'] = $albumId; }
        if ($search !== '')    { $qs['q'] = $search; }
        $qsStr = $qs ? ('&' . http_build_query($qs)) : '';
    ?>
    <?php if ($totalPages > 1): ?>
        <div style="margin-top:1.5em; text-align:center;">
            <?php if ($page > 1): ?>
                <a href="/admin/media?p=<?= $page - 1 ?><?= $qsStr ?>">&laquo; Previous</a>
            <?php endif; ?>
            <span class="muted" style="margin:0 1em;">
                Page <?= $page ?> of <?= $totalPages ?>
            </span>
            <?php if ($page < $totalPages): ?>
                <a href="/admin/media?p=<?= $page + 1 ?><?= $qsStr ?>">Next &raquo;</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
<?php endif; ?>

<?php if (!empty($albums)): ?>
<script>
(function () {
    var boxes = document.querySelectorAll('.bulk-select-checkbox');
    var count = document.getElementById('bulk-select-count');
    var submit = document.getElementById('bulk-assign-submit');
    if (!boxes.length || !count || !submit) return;

    function refresh() {
        var n = 0;
        boxes.forEach(function (b) { if (b.checked) n++; });
        count.textContent = n + ' selected';
        submit.disabled = n === 0;
    }
    boxes.forEach(function (b) { b.addEventListener('change', refresh); });
    refresh();
})();
</script>
<?php endif; ?>
