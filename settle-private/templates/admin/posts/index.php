<?php
/**
 * @var array $posts     Rows from Post::allForAdmin()
 * @var bool  $isEditor  True if the viewer is editor or admin
 */
$statusBadge = static function (string $status): string {
    [$label, $bg, $fg] = match ($status) {
        'published' => ['Published', '#1f7a3d', '#fff'],
        'archived'  => ['Archived',  '#6b6b6b', '#fff'],
        default     => ['Draft',     '#e4e4e4', '#333'],
    };
    return '<span style="display:inline-block; font-size:0.78em; padding:0.1em 0.6em;'
         . ' border-radius:1em; background:' . $bg . '; color:' . $fg . ';">'
         . $label . '</span>';
};
?>
<div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:1em;">
  <h1 style="margin:0;">Blog Posts</h1>
  <div>
    <a href="/admin/categories" style="margin-right:0.8em;">Manage Categories</a>
    <a href="/admin/posts/new" class="btn-primary" style="text-decoration:none;">+ New Post</a>
  </div>
</div>

<?php if (!$isEditor): ?>
  <p class="muted" style="margin-bottom:1em;">You're seeing your own posts. Editors can see and manage everyone's.</p>
<?php endif; ?>

<table class="list">
  <thead>
    <tr>
      <th>Title</th>
      <th>Status</th>
      <th>Categories</th>
      <?php if ($isEditor): ?><th>Author</th><?php endif; ?>
      <th>Updated</th>
      <th></th>
    </tr>
  </thead>
  <tbody>
    <?php if (empty($posts)): ?>
      <tr><td colspan="<?= $isEditor ? 6 : 5 ?>" style="text-align:center; padding:2em;">
        No posts yet. Click "+ New Post" to write your first one.
      </td></tr>
    <?php endif; ?>

    <?php foreach ($posts as $p): ?>
      <tr>
        <td>
          <?= htmlspecialchars($p['title'], ENT_QUOTES) ?><br>
          <code class="muted">/blog/<?= htmlspecialchars($p['slug'], ENT_QUOTES) ?></code>
        </td>
        <td><?= $statusBadge((string) $p['status']) ?></td>
        <td><?= htmlspecialchars($p['category_names'] ?? '', ENT_QUOTES) ?: '<span class="muted">—</span>' ?></td>
        <?php if ($isEditor): ?>
          <td><?= htmlspecialchars($p['author_name'] ?? '', ENT_QUOTES) ?></td>
        <?php endif; ?>
        <td><?= htmlspecialchars(date('M j, Y', strtotime((string) $p['updated_at'])), ENT_QUOTES) ?></td>
        <td class="row-actions">
          <a href="/admin/posts/<?= (int) $p['id'] ?>/edit">Edit</a>
          &nbsp;
          <?php if ($p['status'] === 'published'): ?>
            <form method="post" action="/admin/posts/<?= (int) $p['id'] ?>/status" style="display:inline">
              <?= \Settle\Csrf::field() ?>
              <input type="hidden" name="status" value="draft">
              <button type="submit" class="linklike" data-confirm="Unpublish this post (back to draft)?">Unpublish</button>
            </form>
          <?php else: ?>
            <form method="post" action="/admin/posts/<?= (int) $p['id'] ?>/status" style="display:inline">
              <?= \Settle\Csrf::field() ?>
              <input type="hidden" name="status" value="published">
              <button type="submit" class="linklike" data-confirm="Publish this post now?">Publish</button>
            </form>
          <?php endif; ?>
          &nbsp;
          <form method="post" action="/admin/posts/<?= (int) $p['id'] ?>/delete" style="display:inline">
            <?= \Settle\Csrf::field() ?>
            <button type="submit" class="linklike" style="color:var(--error,#b00);"
                    data-confirm="Permanently delete this post? This cannot be undone.">Delete</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
