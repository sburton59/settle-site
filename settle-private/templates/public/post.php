<?php
/**
 * Single blog post.
 *
 * @var array   $post       From PublicController::post() — published post with
 *                          author_name, featured_filename, featured_alt, and
 *                          a 'categories' list of {id,name,slug}
 * @var array   $settings   From PublicView
 * @var array   $menu_tree  From PublicView
 * @var Closure $e
 */
$date = !empty($post['published_at']) ? date('F j, Y', strtotime((string) $post['published_at'])) : '';
$img  = !empty($post['featured_filename']) ? '/uploads/' . ltrim((string) $post['featured_filename'], '/') : '';
?>

<section class="page-intro">
  <div class="container">
    <?php if (!empty($post['categories'])): ?>
      <div class="post-cats post-cats--intro">
        <?php foreach ($post['categories'] as $cat): ?>
          <a class="post-cat" href="/blog/category/<?= $e($cat['slug']) ?>"><?= $e($cat['name']) ?></a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
    <h1><?= $e($post['title']) ?></h1>
    <div class="post-byline">
      <?php if (!empty($post['author_name'])): ?>
        <span>By <?= $e($post['author_name']) ?></span>
      <?php endif; ?>
      <?php if ($date !== ''): ?>
        <span><?= $e($date) ?></span>
      <?php endif; ?>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <article class="post-single" style="margin-inline:auto;">
      <?php if ($img !== ''): ?>
        <img class="post-single__image" src="<?= $e($img) ?>"
             alt="<?= $e($post['featured_alt'] ?? '') ?>">
      <?php endif; ?>

      <div class="prose" style="margin-inline:auto;">
        <?php
          // body_html is intentionally trusted (only authenticated staff can
          // write it — see PROJECT_HANDOFF.md §3.5). Every other field on this
          // page is escaped via $e().
          echo $post['body_html'] ?? '';
        ?>
      </div>

      <div class="post-single__footer">
        <a class="btn btn--ghost" href="/blog">← Back to Blog</a>
      </div>
    </article>
  </div>
</section>
