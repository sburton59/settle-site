<?php
/**
 * Single blog post.
 *
 * @var array   $post       From PublicController::post()
 * @var bool    $is_preview  True when shown to signed-in staff before it is
 *                           publicly live (scheduled/draft/archived).
 * @var array   $settings
 * @var array   $menu_tree
 * @var Closure $e
 */
$is_preview = $is_preview ?? false;

$when = $post['published_at'] ?? '';
if ($when === '' || $when === null) { $when = $post['created_at'] ?? ''; }
$date = $when ? date('F j, Y', strtotime((string) $when)) : '';
$img  = !empty($post['featured_filename']) ? '/uploads/' . ltrim((string) $post['featured_filename'], '/') : '';

// Preview banner wording.
$previewMsg = '';
if ($is_preview) {
    $nowStr = date('Y-m-d H:i:s');
    $status = (string) ($post['status'] ?? '');
    if ($status === 'published' && !empty($post['published_at']) && $post['published_at'] > $nowStr) {
        $previewMsg = 'Scheduled — goes live ' . date('F j, Y \a\t g:i a', strtotime((string) $post['published_at']));
    } elseif ($status === 'draft') {
        $previewMsg = 'Draft — not published yet';
    } elseif ($status === 'archived') {
        $previewMsg = 'Archived — hidden from the website';
    } else {
        $previewMsg = 'Not visible to the public yet';
    }
}
?>

<?php if ($is_preview): ?>
  <div style="background:var(--brand-primary); color:var(--text-on-dark);
              text-align:center; padding:0.6rem 1rem; font-size:0.9rem;">
    <strong>Preview</strong> · <?= $e($previewMsg) ?> — only signed-in staff can see this.
  </div>
<?php endif; ?>

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
