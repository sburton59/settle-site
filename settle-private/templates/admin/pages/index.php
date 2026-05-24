<?php /** @var array $pages */ ?>
<div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:1em;">
  <h1 style="margin:0;">Pages</h1>
  <a href="/admin/pages/new" class="btn-primary" style="text-decoration:none;">+ New Page</a>
</div>

<table class="list">
  <thead>
    <tr>
      <th>Title</th>
      <th>Web address</th>
      <th>Last updated</th>
      <th>Visible?</th>
      <th></th>
    </tr>
  </thead>
  <tbody>
    <?php if (empty($pages)): ?>
      <tr><td colspan="5" style="text-align:center; padding:2em;">No pages yet. Create your first one.</td></tr>
    <?php endif; ?>
    <?php foreach ($pages as $p): ?>
      <tr>
        <td><?= htmlspecialchars($p['title'], ENT_QUOTES) ?></td>
        <td><code>/<?= htmlspecialchars($p['slug'], ENT_QUOTES) ?></code></td>
        <td>
          <?= htmlspecialchars(date('M j, Y', strtotime($p['updated_at'])), ENT_QUOTES) ?>
          <?php if (!empty($p['updated_by_name'])): ?>
            <span class="muted">by <?= htmlspecialchars($p['updated_by_name'], ENT_QUOTES) ?></span>
          <?php endif; ?>
        </td>
        <td><?= $p['is_published'] ? '✓ Yes' : '— Hidden' ?></td>
        <td class="row-actions">
          <a href="/admin/pages/<?= (int)$p['id'] ?>/edit">Edit</a>
          &nbsp;
          <form method="post" action="/admin/pages/<?= (int)$p['id'] ?>/hide" style="display:inline">
            <?= \Settle\Csrf::field() ?>
            <button type="submit" class="linklike"
              data-confirm="<?= $p['is_published']
                  ? 'Hide this page from the website?'
                  : 'Show this page on the website?' ?>">
              <?= $p['is_published'] ? 'Hide' : 'Show' ?>
            </button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>