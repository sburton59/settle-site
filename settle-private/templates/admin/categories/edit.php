<?php
/**
 * @var array $category  Category row (Category::blank() merged on new/error)
 * @var bool  $isNew
 * @var array $errors
 */
$errors = $errors ?? [];
$action = $isNew ? '/admin/categories' : '/admin/categories/' . (int) $category['id'];
?>
<div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:1em;">
  <h1 style="margin:0;"><?= $isNew ? 'New Category' : 'Editing: ' . htmlspecialchars($category['name'], ENT_QUOTES) ?></h1>
  <a href="/admin/categories">← Back to Categories</a>
</div>

<form method="post" action="<?= htmlspecialchars($action, ENT_QUOTES) ?>" data-warn-unsaved>
  <?= \Settle\Csrf::field() ?>

  <label>Name
    <input type="text" name="name" required maxlength="100"
           value="<?= htmlspecialchars($category['name'], ENT_QUOTES) ?>"
           placeholder="e.g. Youth, Music, Children's Programs">
    <?php if (!empty($errors['name'])): ?>
      <small style="color:var(--error);"><?= htmlspecialchars($errors['name'], ENT_QUOTES) ?></small>
    <?php endif; ?>
  </label>

  <label>Web address <span class="muted">(leave blank to fill in automatically from the name)</span>
    <input type="text" name="slug" maxlength="150"
           value="<?= htmlspecialchars($category['slug'], ENT_QUOTES) ?>"
           placeholder="auto-generated-from-name">
    <small class="muted">Category page will live at <code>/blog/category/&lt;web-address&gt;</code>.</small>
    <?php if (!empty($errors['slug'])): ?>
      <small style="color:var(--error);"><?= htmlspecialchars($errors['slug'], ENT_QUOTES) ?></small>
    <?php endif; ?>
  </label>

  <label>Display order <span class="muted">(lower numbers appear first)</span>
    <input type="number" name="sort_order" min="0" max="9999"
           value="<?= (int) ($category['sort_order'] ?? 0) ?>" style="max-width:8em;">
  </label>

  <div style="margin-top:1.5em;">
    <button type="submit" class="btn-primary"><?= $isNew ? 'Create Category' : 'Save Changes' ?></button>
  </div>
</form>
