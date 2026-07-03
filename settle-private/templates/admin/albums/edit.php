<?php
/**
 * @var array $album         Album row (Album::blank() merged on new/error)
 * @var bool  $isNew
 * @var array $errors
 * @var array $photos        {items, total} from Album::photos()
 * @var int   $page
 * @var int   $totalPages
 * @var array $coverPreview  {url, alt} for the cover-image picker preview
 */
$errors = $errors ?? [];
$action = $isNew ? '/admin/albums' : '/admin/albums/' . (int) $album['id'];
?>
<div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:1em;">
  <h1 style="margin:0;"><?= $isNew ? 'New Album' : 'Editing: ' . htmlspecialchars($album['name'], ENT_QUOTES) ?></h1>
  <a href="/admin/albums">← Back to Albums</a>
</div>

<form method="post" action="<?= htmlspecialchars($action, ENT_QUOTES) ?>" data-warn-unsaved>
  <?= \Settle\Csrf::field() ?>
  <input type="hidden" name="cover_media_id" id="cover-id-input"
         value="<?= (int) ($album['cover_media_id'] ?? 0) ?>">

  <label>Name
    <input type="text" name="name" required maxlength="150"
           value="<?= htmlspecialchars($album['name'], ENT_QUOTES) ?>"
           placeholder="e.g. VBS True North 2026">
    <?php if (!empty($errors['name'])): ?>
      <small style="color:var(--error);"><?= htmlspecialchars($errors['name'], ENT_QUOTES) ?></small>
    <?php endif; ?>
  </label>

  <label>Web address <span class="muted">(leave blank to fill in automatically from the name)</span>
    <input type="text" name="slug" maxlength="150"
           value="<?= htmlspecialchars($album['slug'], ENT_QUOTES) ?>"
           placeholder="auto-generated-from-name">
    <small class="muted">Album will live at <code>/photos/&lt;web-address&gt;</code>.</small>
    <?php if (!empty($errors['slug'])): ?>
      <small style="color:var(--error);"><?= htmlspecialchars($errors['slug'], ENT_QUOTES) ?></small>
    <?php endif; ?>
  </label>

  <label>Description <span class="muted">(optional)</span>
    <textarea name="description" rows="2" maxlength="500"><?=
        htmlspecialchars($album['description'] ?? '', ENT_QUOTES)
    ?></textarea>
    <?php if (!empty($errors['description'])): ?>
      <small style="color:var(--error);"><?= htmlspecialchars($errors['description'], ENT_QUOTES) ?></small>
    <?php endif; ?>
  </label>

  <label>Event date <span class="muted">(shown on the album grid, e.g. "Aug 2026"; also sets sort order — newest first)</span>
    <input type="date" name="event_date" style="max-width:12em;"
           value="<?= htmlspecialchars($album['event_date'] ?? '', ENT_QUOTES) ?>">
    <?php if (!empty($errors['event_date'])): ?>
      <small style="color:var(--error);"><?= htmlspecialchars($errors['event_date'], ENT_QUOTES) ?></small>
    <?php endif; ?>
  </label>

  <label>Cover image <span class="muted">(optional — defaults to the oldest photo in the album)</span>
    <div style="margin-top:0.4em;">
      <div id="cover-preview" style="background:#f0f0f0; border-radius:4px; padding:1em;
                  text-align:center; min-height:120px; display:flex;
                  align-items:center; justify-content:center;">
        <?php if ($coverPreview['url'] !== ''): ?>
          <img src="<?= htmlspecialchars($coverPreview['url'], ENT_QUOTES) ?>"
               alt="<?= htmlspecialchars($coverPreview['alt'], ENT_QUOTES) ?>"
               style="max-width:100%; max-height:220px;">
        <?php else: ?>
          <span class="muted">No cover chosen — will use the oldest photo automatically.</span>
        <?php endif; ?>
      </div>
      <button type="button" id="pick-cover-btn" class="btn-primary" style="margin-top:0.6em;">
        <?= $coverPreview['url'] !== '' ? 'Change Cover Image' : 'Choose Cover Image' ?>
      </button>
      <button type="button" id="clear-cover-btn" class="linklike" style="margin-left:0.6em;
              <?= $coverPreview['url'] === '' ? 'display:none;' : '' ?>">Use automatic cover</button>
    </div>
    <?php if (!empty($errors['cover_media_id'])): ?>
      <small style="color:var(--error);"><?= htmlspecialchars($errors['cover_media_id'], ENT_QUOTES) ?></small>
    <?php endif; ?>
  </label>

  <label class="checkbox">
    <input type="checkbox" name="is_published" value="1" <?= !empty($album['is_published']) ? 'checked' : '' ?>>
    Published (visible on the public <code>/photos</code> gallery)
  </label>

  <label>Display order <span class="muted">(lower numbers appear first when event dates tie)</span>
    <input type="number" name="sort_order" min="0" max="9999"
           value="<?= (int) ($album['sort_order'] ?? 0) ?>" style="max-width:8em;">
  </label>

  <div style="margin-top:1.5em;">
    <button type="submit" class="btn-primary"><?= $isNew ? 'Create Album' : 'Save Changes' ?></button>
  </div>
</form>

<?php if (!$isNew): ?>
  <hr style="margin:2em 0; border:none; border-top:1px solid var(--gray-200);">

  <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:0.75em;">
    <h2 style="margin:0;">Photos in this album (<?= (int) $photos['total'] ?>)</h2>
    <a href="/admin/media?album=<?= (int) $album['id'] ?>" class="btn-primary" style="text-decoration:none;">
      + Add Photos from Library
    </a>
  </div>

  <?php if (empty($photos['items'])): ?>
    <div style="background:#fff; padding:2em; text-align:center; border-radius:4px;">
      <p class="muted">No photos yet. Click "Add Photos from Library" to select some, or upload new ones there first.</p>
    </div>
  <?php else: ?>
    <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(150px, 1fr)); gap:0.9em;">
      <?php foreach ($photos['items'] as $p): ?>
        <?php
          $thumbRel = !empty($p['thumbnail_filename']) ? $p['thumbnail_filename'] : $p['filename'];
          $thumbUrl = '/uploads/' . htmlspecialchars(ltrim((string) $thumbRel, '/'), ENT_QUOTES);
        ?>
        <div style="background:#fff; border-radius:4px; overflow:hidden; box-shadow:0 1px 3px rgba(0,0,0,0.05);">
          <div style="aspect-ratio:1/1; background:#f0f0f0; overflow:hidden;">
            <img src="<?= $thumbUrl ?>" alt="<?= htmlspecialchars($p['alt_text'] ?? '', ENT_QUOTES) ?>"
                 loading="lazy" style="width:100%; height:100%; object-fit:cover;">
          </div>
          <form method="post" action="/admin/albums/<?= (int) $album['id'] ?>/photos/<?= (int) $p['id'] ?>/remove"
                style="padding:0.4em;">
            <?= \Settle\Csrf::field() ?>
            <button type="submit" class="linklike" style="color:var(--error,#b00); font-size:0.85em;"
                    data-confirm="Remove this photo from the album? It stays in the Media Library.">
              Remove from album
            </button>
          </form>
        </div>
      <?php endforeach; ?>
    </div>

    <?php if ($totalPages > 1): ?>
      <div style="margin-top:1.5em; text-align:center;">
        <?php if ($page > 1): ?>
          <a href="/admin/albums/<?= (int) $album['id'] ?>/edit?p=<?= $page - 1 ?>">&laquo; Previous</a>
        <?php endif; ?>
        <span class="muted" style="margin:0 1em;">Page <?= $page ?> of <?= $totalPages ?></span>
        <?php if ($page < $totalPages): ?>
          <a href="/admin/albums/<?= (int) $album['id'] ?>/edit?p=<?= $page + 1 ?>">Next &raquo;</a>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  <?php endif; ?>

  <form method="post" action="/admin/albums/<?= (int) $album['id'] ?>/delete"
        style="margin-top:2em; padding-top:1em; border-top:1px solid var(--gray-200);">
    <?= \Settle\Csrf::field() ?>
    <button type="submit" class="linklike" style="color:var(--error,#b00);"
            data-confirm="Delete this album? The photos themselves remain in the Media Library.">
      Delete this album
    </button>
  </form>
<?php endif; ?>

<!-- Cover image picker modal — same postMessage protocol as
     /admin/media/picker (see templates/admin/slideshow/edit.php). -->
<div id="picker-modal" style="display:none; position:fixed; inset:0;
            background:rgba(0,0,0,0.6); z-index:1000;
            align-items:center; justify-content:center;">
  <div style="background:#fff; width:90vw; max-width:900px; height:80vh; max-height:600px;
              border-radius:6px; display:flex; flex-direction:column; overflow:hidden;">
    <div style="padding:0.7em 1em; border-bottom:1px solid var(--gray-200);
                display:flex; align-items:center; justify-content:space-between;">
      <strong>Choose a cover image</strong>
      <button type="button" id="picker-close" class="linklike" style="font-size:1.4em; line-height:1;">×</button>
    </div>
    <iframe id="picker-frame" style="border:0; flex-grow:1; width:100%;"></iframe>
  </div>
</div>

<script>
(function () {
  'use strict';
  var modal      = document.getElementById('picker-modal');
  var frame      = document.getElementById('picker-frame');
  var openBtn    = document.getElementById('pick-cover-btn');
  var clearBtn   = document.getElementById('clear-cover-btn');
  var closeBtn   = document.getElementById('picker-close');
  var coverInput = document.getElementById('cover-id-input');
  var preview    = document.getElementById('cover-preview');

  if (!modal || !openBtn) return;

  function openPicker() {
    frame.src = '/admin/media/picker';
    modal.style.display = 'flex';
  }
  function closePicker() {
    modal.style.display = 'none';
    frame.src = 'about:blank';
  }

  openBtn.addEventListener('click', openPicker);
  closeBtn.addEventListener('click', closePicker);
  modal.addEventListener('click', function (e) { if (e.target === modal) closePicker(); });

  if (clearBtn) {
    clearBtn.addEventListener('click', function () {
      coverInput.value = '0';
      preview.innerHTML = '<span class="muted">No cover chosen — will use the oldest photo automatically.</span>';
      openBtn.textContent = 'Choose Cover Image';
      clearBtn.style.display = 'none';
    });
  }

  window.addEventListener('message', function (ev) {
    var d = ev.data;
    if (!d || d.mceAction !== 'insertImage' || !d.url) return;

    preview.innerHTML = '';
    var img = document.createElement('img');
    img.src = d.url;
    img.alt = d.alt || '';
    img.style.maxWidth = '100%';
    img.style.maxHeight = '220px';
    preview.appendChild(img);

    if (d.mediaId) { coverInput.value = String(d.mediaId); }
    openBtn.textContent = 'Change Cover Image';
    if (clearBtn) clearBtn.style.display = '';
    closePicker();
  });
})();
</script>
