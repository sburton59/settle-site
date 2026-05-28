<?php
/**
 * Admin: Menu item edit form (used for both create and update).
 *
 * @var array   $item            Current item data (or blank for new)
 * @var array   $parentChoices   [['id', 'label', 'depth'], ...]
 * @var array   $urlChoices      [['url', 'label', 'group'], ...]
 * @var array   $errors          Field-keyed errors
 * @var bool    $isNew           True if this is the create form
 * @var Closure $e
 */

use Settle\Csrf;

$action = $isNew ? '/admin/menu' : '/admin/menu/' . (int)$item['id'];
$title  = $isNew ? 'Add menu item' : 'Edit menu item';

// Group URL choices by their 'group' value so the <optgroup>s come out clean.
$urlGroups = [];
foreach ($urlChoices as $choice) {
    $urlGroups[$choice['group']][] = $choice;
}

// Determine which radio is currently selected:
// - "picker" if the URL matches one of the registry entries
// - "custom" otherwise (or if URL is empty but a custom entry is being typed)
// - "none" if URL is empty
$currentUrl = (string)($item['url'] ?? '');
$matchedInPicker = false;
foreach ($urlChoices as $choice) {
    if ($choice['url'] === $currentUrl) {
        $matchedInPicker = true;
        break;
    }
}
$urlSource = $currentUrl === ''
    ? 'none'
    : ($matchedInPicker ? 'picker' : 'custom');
?>

<style>
.menu-edit { max-width: 680px; }
.menu-edit__field { margin-bottom: 1.25em; }
.menu-edit__label { display: block; font-weight: 600; margin-bottom: 0.35em; }
.menu-edit__field input[type="text"],
.menu-edit__field select { width: 100%; padding: 0.55em 0.65em; border: 1px solid #ccc; border-radius: 4px; font: inherit; }
.menu-edit__error { color: #b53737; font-size: 0.85em; margin-top: 0.25em; }
.menu-edit__hint  { color: #666; font-size: 0.85em; margin-top: 0.25em; }
.menu-edit__url-source { display: flex; flex-direction: column; gap: 0.5em; margin-bottom: 0.75em; }
.menu-edit__url-source label { font-weight: normal; cursor: pointer; }
.menu-edit__url-input { margin-top: 0.5em; }
.menu-edit__buttons { margin-top: 2em; }
.menu-edit__buttons .btn { margin-right: 0.5em; }
</style>

<div class="menu-edit">
  <h1><?= $e($title) ?></h1>

  <form method="post" action="<?= $e($action) ?>" novalidate>
    <?= Csrf::field() ?>

    <div class="menu-edit__field">
      <label class="menu-edit__label" for="m_label">Label <span style="color:#b53737">*</span></label>
      <input
        type="text"
        id="m_label"
        name="label"
        value="<?= $e($item['label'] ?? '') ?>"
        maxlength="100"
        required
      >
      <div class="menu-edit__hint">What appears in the menu. Keep it short.</div>
      <?php if (!empty($errors['label'])): ?>
        <div class="menu-edit__error"><?= $e($errors['label']) ?></div>
      <?php endif; ?>
    </div>

    <div class="menu-edit__field">
      <label class="menu-edit__label">Where does this link go?</label>

      <div class="menu-edit__url-source">
        <label>
          <input type="radio" name="url_source" value="picker"
                 <?= $urlSource === 'picker' ? 'checked' : '' ?>
                 onchange="toggleUrlSource()">
          Pick from existing site sections or pages
        </label>
        <label>
          <input type="radio" name="url_source" value="custom"
                 <?= $urlSource === 'custom' ? 'checked' : '' ?>
                 onchange="toggleUrlSource()">
          Type a custom URL (external link, anchor, etc.)
        </label>
        <label>
          <input type="radio" name="url_source" value="none"
                 <?= $urlSource === 'none' ? 'checked' : '' ?>
                 onchange="toggleUrlSource()">
          No link &mdash; this item is a parent only
        </label>
      </div>

      <select id="url_picker" name="url_picker" style="<?= $urlSource === 'picker' ? '' : 'display:none' ?>">
        <option value="">— Choose a destination —</option>
        <?php foreach ($urlGroups as $groupName => $entries): ?>
          <optgroup label="<?= $e($groupName) ?>">
            <?php foreach ($entries as $entry): ?>
              <option value="<?= $e($entry['url']) ?>" <?= $entry['url'] === $currentUrl ? 'selected' : '' ?>>
                <?= $e($entry['label']) ?> &nbsp; <span style="color:#999"><?= $e($entry['url']) ?></span>
              </option>
            <?php endforeach; ?>
          </optgroup>
        <?php endforeach; ?>
      </select>

      <input
        type="text"
        id="url_custom"
        name="url_custom"
        class="menu-edit__url-input"
        placeholder="https://example.com or /some/path"
        value="<?= $urlSource === 'custom' ? $e($currentUrl) : '' ?>"
        maxlength="500"
        style="<?= $urlSource === 'custom' ? '' : 'display:none' ?>"
      >

      <!-- Hidden field that the controller actually reads. Updated by JS. -->
      <input type="hidden" name="url" id="url_hidden" value="<?= $e($currentUrl) ?>">

      <?php if (!empty($errors['url'])): ?>
        <div class="menu-edit__error"><?= $e($errors['url']) ?></div>
      <?php endif; ?>
    </div>

    <?php if (!empty($parentChoices)): ?>
      <div class="menu-edit__field">
        <label class="menu-edit__label" for="m_parent">Parent</label>
        <select id="m_parent" name="parent_id">
          <option value="">— Top level (no parent) —</option>
          <?php foreach ($parentChoices as $choice): ?>
            <option value="<?= (int)$choice['id'] ?>"
                    <?= ((int)($item['parent_id'] ?? 0)) === (int)$choice['id'] ? 'selected' : '' ?>>
              <?= str_repeat('&nbsp;&nbsp;', (int)$choice['depth']) ?><?= $e($choice['label']) ?>
            </option>
          <?php endforeach; ?>
        </select>
        <div class="menu-edit__hint">
          Group this item under another menu item. Only one level of nesting is supported.
        </div>
        <?php if (!empty($errors['parent_id'])): ?>
          <div class="menu-edit__error"><?= $e($errors['parent_id']) ?></div>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <div class="menu-edit__field">
      <label class="menu-edit__label" for="m_target">Link target</label>
      <select id="m_target" name="target">
        <option value="_self"  <?= ($item['target'] ?? '_self') === '_self'  ? 'selected' : '' ?>>Same window (default)</option>
        <option value="_blank" <?= ($item['target'] ?? '_self') === '_blank' ? 'selected' : '' ?>>New tab/window</option>
      </select>
      <div class="menu-edit__hint">
        Use "New tab/window" for links to external sites.
      </div>
      <?php if (!empty($errors['target'])): ?>
        <div class="menu-edit__error"><?= $e($errors['target']) ?></div>
      <?php endif; ?>
    </div>

    <div class="menu-edit__field">
      <label style="font-weight: normal; cursor: pointer;">
        <input type="checkbox" name="is_active" value="1"
               <?= !empty($item['is_active']) ? 'checked' : '' ?>>
        Show this item in the public menu
      </label>
      <div class="menu-edit__hint">
        Uncheck to hide without deleting. Hiding a parent also hides its children.
      </div>
    </div>

    <div class="menu-edit__buttons">
      <button type="submit" class="btn"><?= $isNew ? 'Add item' : 'Save changes' ?></button>
      <a class="btn btn--ghost" href="/admin/menu">Cancel</a>
    </div>
  </form>
</div>

<script>
function toggleUrlSource() {
  var source  = document.querySelector('input[name="url_source"]:checked').value;
  var picker  = document.getElementById('url_picker');
  var custom  = document.getElementById('url_custom');
  var hidden  = document.getElementById('url_hidden');

  if (source === 'picker') {
    picker.style.display = '';
    custom.style.display = 'none';
    hidden.value = picker.value || '';
  } else if (source === 'custom') {
    picker.style.display = 'none';
    custom.style.display = '';
    hidden.value = custom.value || '';
  } else {
    picker.style.display = 'none';
    custom.style.display = 'none';
    hidden.value = '';
  }
}

// Keep the hidden field in sync with whichever input is visible.
document.getElementById('url_picker').addEventListener('change', function () {
  if (document.querySelector('input[name="url_source"]:checked').value === 'picker') {
    document.getElementById('url_hidden').value = this.value || '';
  }
});
document.getElementById('url_custom').addEventListener('input', function () {
  if (document.querySelector('input[name="url_source"]:checked').value === 'custom') {
    document.getElementById('url_hidden').value = this.value || '';
  }
});
</script>
