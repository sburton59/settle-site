<?php
/**
 * Public photo album index (Flickr-replacement gallery).
 *
 * @var array   $albums     Album::allPublished() rows — incl. photo_count,
 *                          cover_filename, cover_thumbnail
 * @var array   $settings   From PublicView
 * @var array   $menu_tree  From PublicView
 * @var Closure $e
 */
?>

<section class="page-intro">
  <div class="container">
    <div class="eyebrow">Gallery</div>
    <h1>Photo Albums</h1>
  </div>
</section>

<section class="section">
  <div class="container">

    <?php if (empty($albums)): ?>
      <p style="text-align:center; color:var(--text-muted); padding:2rem 0;">
        No photo albums yet. Check back soon!
      </p>
    <?php else: ?>
      <div class="album-grid">
        <?php foreach ($albums as $a): ?>
          <?php
            $url  = '/photos/' . rawurlencode((string) $a['slug']);
            $imgRel = !empty($a['cover_thumbnail']) ? $a['cover_thumbnail'] : ($a['cover_filename'] ?? '');
            $img  = !empty($imgRel) ? '/uploads/' . ltrim((string) $imgRel, '/') : '';
            $when = !empty($a['event_date']) ? date('M Y', strtotime((string) $a['event_date'])) : '';
            $count = (int) $a['photo_count'];
          ?>
          <a class="album-card" href="<?= $e($url) ?>">
            <span class="album-card__media"<?= $img !== '' ? " style=\"background-image:url('" . $e($img) . "');\"" : '' ?>>
              <?php if ($img === ''): ?><span class="album-card__placeholder">No photos yet</span><?php endif; ?>
            </span>
            <span class="album-card__body">
              <span class="album-card__title"><?= $e($a['name']) ?></span>
              <span class="album-card__meta">
                <?php if ($when !== ''): ?><?= $e($when) ?> &middot; <?php endif; ?>
                <?= $count ?> photo<?= $count === 1 ? '' : 's' ?>
              </span>
            </span>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

  </div>
</section>
