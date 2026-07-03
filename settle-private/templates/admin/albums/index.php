<?php
/** @var array $albums  Rows from Album::all() (incl. photo_count, cover_filename, cover_thumbnail) */
?>
<div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:1em;">
  <h1 style="margin:0;">Photo Albums</h1>
  <div>
    <a href="/admin/media" style="margin-right:0.8em;">← Back to Photos &amp; Files</a>
    <a href="/admin/albums/new" class="btn-primary" style="text-decoration:none;">+ New Album</a>
  </div>
</div>

<p class="muted" style="margin-bottom:1em;">
  Albums are the public photo gallery at <code>/photos</code>. A photo only shows up in an album once you've
  added it here or on the <a href="/admin/media">Media Library</a> grid — nothing appears automatically.
  Unpublished albums are visible here only, never on the public site.
</p>

<?php if (empty($albums)): ?>
  <div style="background:#fff; padding:2em; text-align:center; border-radius:4px;">
    <p class="muted">No albums yet. Click "+ New Album" to create your first one.</p>
  </div>
<?php else: ?>
  <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(220px, 1fr)); gap:1.25em;">
    <?php foreach ($albums as $a): ?>
      <?php
        $thumbRel = !empty($a['cover_thumbnail']) ? $a['cover_thumbnail'] : $a['cover_filename'];
        $thumbUrl = !empty($thumbRel) ? '/uploads/' . htmlspecialchars(ltrim((string) $thumbRel, '/'), ENT_QUOTES) : '';
        $when = !empty($a['event_date']) ? date('M Y', strtotime((string) $a['event_date'])) : '';
      ?>
      <a href="/admin/albums/<?= (int) $a['id'] ?>/edit"
         style="background:#fff; border-radius:4px; overflow:hidden; text-decoration:none; color:inherit;
                box-shadow:0 1px 3px rgba(0,0,0,0.05); display:flex; flex-direction:column;">
        <div style="aspect-ratio:4/3; background:#f0f0f0; display:flex; align-items:center; justify-content:center; overflow:hidden;">
          <?php if ($thumbUrl !== ''): ?>
            <img src="<?= $thumbUrl ?>" alt="" loading="lazy" style="width:100%; height:100%; object-fit:cover;">
          <?php else: ?>
            <span class="muted" style="font-size:0.85em;">No photos yet</span>
          <?php endif; ?>
        </div>
        <div style="padding:0.6em 0.8em;">
          <div style="font-weight:600; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
            <?= htmlspecialchars($a['name'], ENT_QUOTES) ?>
            <?php if (!$a['is_published']): ?>
              <span class="muted" style="font-weight:400; font-size:0.8em;">(unpublished)</span>
            <?php endif; ?>
          </div>
          <div class="muted" style="font-size:0.85em; margin-top:0.2em;">
            <?php if ($when !== ''): ?><?= htmlspecialchars($when, ENT_QUOTES) ?> &middot; <?php endif; ?>
            <?= (int) $a['photo_count'] ?> photo<?= (int) $a['photo_count'] === 1 ? '' : 's' ?>
          </div>
        </div>
      </a>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
