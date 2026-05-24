<?php
/** @var array $page */
/** @var bool $isNew */
/** @var array $errors */
$errors = $errors ?? [];
$action = $isNew ? '/admin/pages' : '/admin/pages/' . (int)$page['id'];
?>
<div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:1em;">
  <h1 style="margin:0;"><?= $isNew ? 'New Page' : 'Editing: ' . htmlspecialchars($page['title'], ENT_QUOTES) ?></h1>
  <a href="/admin/pages">← Back to Pages</a>
</div>

<form method="post" action="<?= htmlspecialchars($action, ENT_QUOTES) ?>" data-warn-unsaved>
  <?= \Settle\Csrf::field() ?>

  <label>Title
    <input type="text" name="title" required
           value="<?= htmlspecialchars($page['title'], ENT_QUOTES) ?>">
    <?php if (!empty($errors['title'])): ?>
      <small style="color:var(--error);"><?= htmlspecialchars($errors['title'], ENT_QUOTES) ?></small>
    <?php endif; ?>
  </label>

  <label>Web address <span class="muted">(the part after settleumc.com/)</span>
    <input type="text" name="slug" required
           value="<?= htmlspecialchars($page['slug'], ENT_QUOTES) ?>"
           placeholder="about, sundays, give, etc.">
    <?php if (!empty($errors['slug'])): ?>
      <small style="color:var(--error);"><?= htmlspecialchars($errors['slug'], ENT_QUOTES) ?></small>
    <?php endif; ?>
  </label>

  <label>Page content
    <textarea name="body_html" rows="20"><?= htmlspecialchars($page['body_html'], ENT_QUOTES) ?></textarea>
    <small class="muted">HTML is allowed. A WYSIWYG editor will be added in a later phase.</small>
  </label>

  <label>Search engine summary <span class="muted">(optional, up to 300 characters)</span>
    <textarea name="meta_description" rows="2" maxlength="300"><?= htmlspecialchars($page['meta_description'] ?? '', ENT_QUOTES) ?></textarea>
    <?php if (!empty($errors['meta_description'])): ?>
      <small style="color:var(--error);"><?= htmlspecialchars($errors['meta_description'], ENT_QUOTES) ?></small>
    <?php endif; ?>
  </label>

  <label class="checkbox">
    <input type="checkbox" name="show_in_nav" value="1" <?= !empty($page['show_in_nav']) ? 'checked' : '' ?>>
    Show this page in the main navigation menu
  </label>

  <label class="checkbox">
    <input type="checkbox" name="is_published" value="1" <?= !empty($page['is_published']) ? 'checked' : '' ?>>
    Page is visible on the website
  </label>

  <div style="margin-top:1.5em;">
    <button type="submit" class="btn-primary"><?= $isNew ? 'Create Page' : 'Save Changes' ?></button>
    <?php if (!$isNew): ?>
      <a href="/page/<?= htmlspecialchars($page['slug'], ENT_QUOTES) ?>" target="_blank" style="margin-left:1em;">Preview ↗</a>
    <?php endif; ?>
  </div>
</form>