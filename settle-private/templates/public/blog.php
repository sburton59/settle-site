<?php
/**
 * Blog listing and category archive (shared).
 *
 * @var array        $posts          published posts for this page; each row
 *                                   carries author_name, featured_filename,
 *                                   featured_alt, published_at, excerpt,
 *                                   body_html, and a 'categories' list
 * @var int          $current_page
 * @var int          $total_pages
 * @var array|null   $category       set on a category archive, else null
 * @var string       $base_path      '/blog' or '/blog/category/{slug}'
 * @var array        $all_categories [{id,name,slug}] for the filter row
 * @var array        $settings       From PublicView
 * @var array        $menu_tree      From PublicView
 * @var Closure      $e
 */

// Excerpt: prefer the author-written summary; otherwise derive one from the
// body (same word-boundary strip used on the homepage welcome lead).
$excerptOf = static function (array $p): string {
    $ex = trim((string) ($p['excerpt'] ?? ''));
    if ($ex !== '') {
        return $ex;
    }
    $plain = trim(strip_tags((string) ($p['body_html'] ?? '')));
    if ($plain === '') {
        return '';
    }
    if (mb_strlen($plain) > 200) {
        $cut = mb_substr($plain, 0, 200);
        $sp  = mb_strrpos($cut, ' ');
        if ($sp !== false) {
            $cut = mb_substr($cut, 0, $sp);
        }
        return $cut . '…';
    }
    return $plain;
};

$activeSlug = $category['slug'] ?? null;
?>

<section class="page-intro">
  <div class="container">
    <?php if ($category !== null): ?>
      <div class="eyebrow">Category</div>
      <h1><?= $e($category['name']) ?></h1>
    <?php else: ?>
      <div class="eyebrow">News &amp; Updates</div>
      <h1>Blog</h1>
    <?php endif; ?>
  </div>
</section>

<section class="section">
  <div class="container">

    <?php if (!empty($all_categories)): ?>
      <nav class="blog-filter" aria-label="Filter posts by category">
        <a class="blog-filter__chip<?= $activeSlug === null ? ' is-active' : '' ?>" href="/blog">All</a>
        <?php foreach ($all_categories as $c): ?>
          <a class="blog-filter__chip<?= $activeSlug === $c['slug'] ? ' is-active' : '' ?>"
             href="/blog/category/<?= $e($c['slug']) ?>"><?= $e($c['name']) ?></a>
        <?php endforeach; ?>
      </nav>
    <?php endif; ?>

    <?php if (empty($posts)): ?>
      <p style="text-align:center; color:var(--text-muted); padding:2rem 0;">
        <?= $category !== null
              ? 'No posts in this category yet.'
              : 'No blog posts yet. Check back soon!' ?>
      </p>
    <?php else: ?>
      <div class="blog-grid">
        <?php foreach ($posts as $p): ?>
          <?php
            $url  = '/blog/' . rawurlencode((string) $p['slug']);
            // Prefer the thumbnail variant for the card background; fall back to
            // the full-size featured image when there isn't one (#9).
            $imgRel = !empty($p['featured_thumbnail']) ? $p['featured_thumbnail']
                    : ($p['featured_filename'] ?? '');
            $img  = !empty($imgRel) ? '/uploads/' . ltrim((string) $imgRel, '/') : '';
            $when = $p['published_at'] ?? '';
            if ($when === '' || $when === null) { $when = $p['created_at'] ?? ''; }
            $date = $when ? date('M j, Y', strtotime((string) $when)) : '';
            $exc  = $excerptOf($p);
          ?>
          <article class="post-card">
            <?php if ($img !== ''): ?>
              <a class="post-card__media" href="<?= $e($url) ?>"
                 style="background-image:url('<?= $e($img) ?>');"
                 aria-label="<?= $e($p['title']) ?>"></a>
            <?php endif; ?>
            <div class="post-card__body">
              <?php if (!empty($p['categories'])): ?>
                <div class="post-cats">
                  <?php foreach ($p['categories'] as $cat): ?>
                    <a class="post-cat" href="/blog/category/<?= $e($cat['slug']) ?>"><?= $e($cat['name']) ?></a>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
              <h2 class="post-card__title">
                <a href="<?= $e($url) ?>"><?= $e($p['title']) ?></a>
              </h2>
              <?php if ($exc !== ''): ?>
                <p class="post-card__excerpt"><?= $e($exc) ?></p>
              <?php endif; ?>
              <div class="post-card__meta">
                <?php if (!empty($p['author_name'])): ?>
                  <span><?= $e($p['author_name']) ?></span>
                <?php endif; ?>
                <?php if ($date !== ''): ?>
                  <span><?= $e($date) ?></span>
                <?php endif; ?>
              </div>
            </div>
          </article>
        <?php endforeach; ?>
      </div>

      <?php if ($total_pages > 1): ?>
        <nav class="blog-pagination" aria-label="Blog pages">
          <?php if ($current_page > 1): ?>
            <a class="btn btn--ghost" href="<?= $e($base_path) ?>?page=<?= (int) ($current_page - 1) ?>">← Newer</a>
          <?php else: ?>
            <span class="btn btn--ghost is-disabled" aria-disabled="true">← Newer</span>
          <?php endif; ?>

          <span class="blog-pagination__status">Page <?= (int) $current_page ?> of <?= (int) $total_pages ?></span>

          <?php if ($current_page < $total_pages): ?>
            <a class="btn btn--ghost" href="<?= $e($base_path) ?>?page=<?= (int) ($current_page + 1) ?>">Older →</a>
          <?php else: ?>
            <span class="btn btn--ghost is-disabled" aria-disabled="true">Older →</span>
          <?php endif; ?>
        </nav>
      <?php endif; ?>
    <?php endif; ?>

  </div>
</section>
