<?php
/** @var array $categories  Rows from Category::all() (incl. post_count) */
?>
<div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:1em;">
  <h1 style="margin:0;">Blog Categories</h1>
  <div>
    <a href="/admin/posts" style="margin-right:0.8em;">← Back to Posts</a>
    <a href="/admin/categories/new" class="btn-primary" style="text-decoration:none;">+ New Category</a>
  </div>
</div>

<p class="muted" style="margin-bottom:1em;">
  Categories group posts by ministry area (Music, Youth, Children's Programs, …). Authors pick from this
  list when writing. Deleting a category never deletes posts — they simply lose that one tag.
</p>

<table class="list">
  <thead>
    <tr>
      <th>Name</th>
      <th>Web address</th>
      <th>Order</th>
      <th>Published posts</th>
      <th></th>
    </tr>
  </thead>
  <tbody>
    <?php if (empty($categories)): ?>
      <tr><td colspan="5" style="text-align:center; padding:2em;">
        No categories yet. Click "+ New Category" to add one.
      </td></tr>
    <?php endif; ?>
    <?php foreach ($categories as $c): ?>
      <tr>
        <td><?= htmlspecialchars($c['name'], ENT_QUOTES) ?></td>
        <td><code>/blog/category/<?= htmlspecialchars($c['slug'], ENT_QUOTES) ?></code></td>
        <td><?= (int) $c['sort_order'] ?></td>
        <td><?= (int) ($c['post_count'] ?? 0) ?></td>
        <td class="row-actions">
          <a href="/admin/categories/<?= (int) $c['id'] ?>/edit">Edit</a>
          &nbsp;
          <form method="post" action="/admin/categories/<?= (int) $c['id'] ?>/delete" style="display:inline">
            <?= \Settle\Csrf::field() ?>
            <button type="submit" class="linklike" style="color:var(--error,#b00);"
                    data-confirm="Delete this category? Posts keep their content but lose this tag.">Delete</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
