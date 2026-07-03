<?php
/**
 * Public photo album detail — one album's photos, paginated, with a
 * lightweight click-to-enlarge lightbox (progressive enhancement; the
 * base grid works with JS disabled — thumbnails just link out).
 *
 * @var array   $album         photo_albums row
 * @var array   $photos        media rows in this album, current page
 * @var int     $total
 * @var int     $current_page
 * @var int     $total_pages
 * @var array   $settings      From PublicView
 * @var array   $menu_tree     From PublicView
 * @var Closure $e
 */
$when = !empty($album['event_date']) ? date('F Y', strtotime((string) $album['event_date'])) : '';
?>

<section class="page-intro">
  <div class="container">
    <div class="eyebrow"><a href="/photos" style="color:inherit;">Photo Albums</a></div>
    <h1><?= $e($album['name']) ?></h1>
    <?php if ($when !== '' || $total > 0): ?>
      <p class="album-detail__meta">
        <?php if ($when !== ''): ?><?= $e($when) ?> &middot; <?php endif; ?>
        <?= (int) $total ?> photo<?= (int) $total === 1 ? '' : 's' ?>
      </p>
    <?php endif; ?>
    <?php if (!empty($album['description'])): ?>
      <p class="album-detail__desc"><?= $e($album['description']) ?></p>
    <?php endif; ?>
  </div>
</section>

<section class="section">
  <div class="container">

    <?php if (empty($photos)): ?>
      <p style="text-align:center; color:var(--text-muted); padding:2rem 0;">No photos in this album yet.</p>
    <?php else: ?>
      <div class="photo-grid">
        <?php foreach ($photos as $p): ?>
          <?php
            $thumbRel = !empty($p['thumbnail_filename']) ? $p['thumbnail_filename'] : $p['filename'];
            $thumb    = '/uploads/' . ltrim((string) $thumbRel, '/');
            $full     = '/uploads/' . ltrim((string) $p['filename'], '/');
            $alt      = (string) ($p['alt_text'] ?? '');
            $caption  = (string) ($p['caption'] ?? '');
          ?>
          <a class="photo-tile" href="<?= $e($full) ?>"
             data-lightbox-full="<?= $e($full) ?>"
             data-lightbox-caption="<?= $e($caption) ?>"
             aria-label="<?= $e($alt !== '' ? $alt : 'View photo') ?>">
            <img src="<?= $e($thumb) ?>" alt="<?= $e($alt) ?>" loading="lazy">
          </a>
        <?php endforeach; ?>
      </div>

      <?php if ($total_pages > 1): ?>
        <nav class="blog-pagination" aria-label="Album pages">
          <?php if ($current_page > 1): ?>
            <a class="btn btn--ghost" href="/photos/<?= $e($album['slug']) ?>?page=<?= (int) ($current_page - 1) ?>">← Newer</a>
          <?php else: ?>
            <span class="btn btn--ghost is-disabled" aria-disabled="true">← Newer</span>
          <?php endif; ?>

          <span class="blog-pagination__status">Page <?= (int) $current_page ?> of <?= (int) $total_pages ?></span>

          <?php if ($current_page < $total_pages): ?>
            <a class="btn btn--ghost" href="/photos/<?= $e($album['slug']) ?>?page=<?= (int) ($current_page + 1) ?>">Older →</a>
          <?php else: ?>
            <span class="btn btn--ghost is-disabled" aria-disabled="true">Older →</span>
          <?php endif; ?>
        </nav>
      <?php endif; ?>
    <?php endif; ?>

  </div>
</section>

<!-- Lightbox overlay (progressive enhancement; hidden until JS opens it). -->
<div id="lightbox" class="lightbox" hidden>
  <button type="button" class="lightbox__close" aria-label="Close">&times;</button>
  <img class="lightbox__img" src="" alt="">
  <p class="lightbox__caption"></p>
</div>

<script>
(function () {
  var tiles = document.querySelectorAll('.photo-tile');
  var box   = document.getElementById('lightbox');
  if (!tiles.length || !box) return;

  var img     = box.querySelector('.lightbox__img');
  var caption = box.querySelector('.lightbox__caption');
  var closeBtn = box.querySelector('.lightbox__close');

  function open(tile) {
    img.src = tile.getAttribute('data-lightbox-full');
    img.alt = tile.querySelector('img') ? tile.querySelector('img').alt : '';
    var cap = tile.getAttribute('data-lightbox-caption') || '';
    caption.textContent = cap;
    caption.hidden = cap === '';
    box.hidden = false;
    document.body.style.overflow = 'hidden';
  }
  function close() {
    box.hidden = true;
    img.src = '';
    document.body.style.overflow = '';
  }

  tiles.forEach(function (tile) {
    tile.addEventListener('click', function (e) {
      e.preventDefault();
      open(tile);
    });
  });
  closeBtn.addEventListener('click', close);
  box.addEventListener('click', function (e) { if (e.target === box) close(); });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !box.hidden) close(); });
})();
</script>
