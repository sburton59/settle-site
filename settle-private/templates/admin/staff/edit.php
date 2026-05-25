<?php
/** @var array $person */
/** @var bool $isNew */
/** @var array $errors */
$errors = $errors ?? [];
$action = $isNew ? '/admin/staff' : '/admin/staff/' . (int)$person['id'];

// If a photo is already selected (existing record or returning from validation
// error), surface it so we can show the preview.
$previewUrl = '';
$previewAlt = '';
if (!empty($person['photo_filename'])) {
    $previewUrl = '/uploads/' . $person['photo_filename'];
    $previewAlt = $person['photo_alt'] ?? '';
}
?>
<div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:1em;">
  <h1 style="margin:0;">
    <?= $isNew ? 'Add Staff Member' : 'Edit: ' . htmlspecialchars($person['full_name'], ENT_QUOTES) ?>
  </h1>
  <a href="/admin/staff">&larr; Back to Staff</a>
</div>

<form method="post" action="<?= htmlspecialchars($action, ENT_QUOTES) ?>" data-warn-unsaved>
  <?= \Settle\Csrf::field() ?>

  <input type="hidden" name="photo_media_id" id="photo-media-id-input"
         value="<?= (int)($person['photo_media_id'] ?? 0) ?>">

  <label>Photo <span class="muted">(optional &mdash; a silhouette is shown if none is selected)</span>
    <div id="picker-area" style="margin-top:0.4em;">
      <div id="picker-preview" style="background:#f0f0f0; border-radius:4px; padding:1em;
                                       text-align:center; min-height:160px; display:flex;
                                       align-items:center; justify-content:center;">
        <?php if ($previewUrl): ?>
          <img src="<?= htmlspecialchars($previewUrl, ENT_QUOTES) ?>"
               alt="<?= htmlspecialchars($previewAlt, ENT_QUOTES) ?>"
               style="max-width:100%; max-height:240px;">
        <?php else: ?>
          <img src="/assets/img/silhouette.svg" alt=""
               style="max-width:160px; max-height:160px;">
        <?php endif; ?>
      </div>
      <div style="margin-top:0.6em; display:flex; gap:0.6em;">
        <button type="button" id="pick-image-btn" class="btn-primary">
          <?= $previewUrl ? 'Change Photo' : 'Choose Photo from Media Library' ?>
        </button>
        <button type="button" id="remove-image-btn"
                style="background:none; color:var(--error); border:1px solid var(--gray-200);
                       padding:0.6em 1.2em; border-radius:4px; cursor:pointer;
                       <?= $previewUrl ? '' : 'display:none;' ?>">
          Remove Photo
        </button>
      </div>
    </div>
    <?php if (!empty($errors['photo_media_id'])): ?>
      <small style="color:var(--error);"><?= htmlspecialchars($errors['photo_media_id'], ENT_QUOTES) ?></small>
    <?php endif; ?>
  </label>

  <label>Full name
    <input type="text" name="full_name" required maxlength="150"
           value="<?= htmlspecialchars($person['full_name'] ?? '', ENT_QUOTES) ?>">
    <?php if (!empty($errors['full_name'])): ?>
      <small style="color:var(--error);"><?= htmlspecialchars($errors['full_name'], ENT_QUOTES) ?></small>
    <?php endif; ?>
  </label>

  <label>Title <span class="muted">(e.g. Senior Pastor, Church Administrator)</span>
    <input type="text" name="title" maxlength="150"
           value="<?= htmlspecialchars($person['title'] ?? '', ENT_QUOTES) ?>">
    <?php if (!empty($errors['title'])): ?>
      <small style="color:var(--error);"><?= htmlspecialchars($errors['title'], ENT_QUOTES) ?></small>
    <?php endif; ?>
  </label>

  <label>Email <span class="muted">(optional)</span>
    <input type="email" name="email" maxlength="190"
           value="<?= htmlspecialchars($person['email'] ?? '', ENT_QUOTES) ?>">
    <?php if (!empty($errors['email'])): ?>
      <small style="color:var(--error);"><?= htmlspecialchars($errors['email'], ENT_QUOTES) ?></small>
    <?php endif; ?>
  </label>

  <label>Phone <span class="muted">(optional &mdash; only shown on the public page if filled in)</span>
    <input type="text" name="phone" maxlength="50"
           value="<?= htmlspecialchars($person['phone'] ?? '', ENT_QUOTES) ?>"
           placeholder="(270) 684-4226">
    <?php if (!empty($errors['phone'])): ?>
      <small style="color:var(--error);"><?= htmlspecialchars($errors['phone'], ENT_QUOTES) ?></small>
    <?php endif; ?>
  </label>

  <label>Bio <span class="muted">(optional &mdash; HTML is allowed; only shown on the public page if filled in)</span>
    <textarea name="bio_html" rows="6"><?= htmlspecialchars($person['bio_html'] ?? '', ENT_QUOTES) ?></textarea>
  </label>

  <label class="checkbox">
    <input type="checkbox" name="is_visible" value="1" <?= !empty($person['is_visible']) ? 'checked' : '' ?>>
    Visible on the public Staff page
  </label>

  <div style="margin-top:1.5em;">
    <button type="submit" class="btn-primary">
      <?= $isNew ? 'Add Staff Member' : 'Save Changes' ?>
    </button>
  </div>
</form>

<!--
  Photo picker modal — same protocol as the Slideshow editor and TinyMCE
  WYSIWYG: opens /admin/media/picker in an iframe, listens for a postMessage
  with mceAction:'insertImage' + url/alt/mediaId, then updates the preview
  and the hidden field.
-->
<div id="picker-modal" style="display:none; position:fixed; inset:0;
                              background:rgba(0,0,0,0.6); z-index:1000;
                              align-items:center; justify-content:center;">
  <div style="background:#fff; width:90vw; max-width:900px; height:80vh; max-height:600px;
              border-radius:6px; display:flex; flex-direction:column; overflow:hidden;">
    <div style="padding:0.7em 1em; border-bottom:1px solid var(--gray-200);
                display:flex; align-items:center; justify-content:space-between;">
      <strong>Choose a photo</strong>
      <button type="button" id="picker-close" class="linklike"
              style="font-size:1.4em; line-height:1;">&times;</button>
    </div>
    <iframe id="picker-frame" style="border:0; flex-grow:1; width:100%;"></iframe>
  </div>
</div>

<script>
(function () {
  'use strict';
  var modal      = document.getElementById('picker-modal');
  var frame      = document.getElementById('picker-frame');
  var openBtn    = document.getElementById('pick-image-btn');
  var removeBtn  = document.getElementById('remove-image-btn');
  var closeBtn   = document.getElementById('picker-close');
  var photoInput = document.getElementById('photo-media-id-input');
  var preview    = document.getElementById('picker-preview');

  if (!modal || !openBtn) return;

  function openPicker() {
    frame.src = '/admin/media/picker';
    modal.style.display = 'flex';
  }
  function closePicker() {
    modal.style.display = 'none';
    frame.src = 'about:blank';
  }

  openBtn.addEventListener('click', openPicker);
  closeBtn.addEventListener('click', closePicker);
  modal.addEventListener('click', function (e) {
    if (e.target === modal) closePicker();
  });

  // "Remove photo" reverts to silhouette and clears the hidden field.
  removeBtn.addEventListener('click', function () {
    photoInput.value = '0';
    preview.innerHTML = '';
    var img = document.createElement('img');
    img.src = '/assets/img/silhouette.svg';
    img.alt = '';
    img.style.maxWidth = '160px';
    img.style.maxHeight = '160px';
    preview.appendChild(img);
    openBtn.textContent = 'Choose Photo from Media Library';
    removeBtn.style.display = 'none';
  });

  // Listen for the picker iframe's "image selected" message.
  window.addEventListener('message', function (ev) {
    var d = ev.data;
    if (!d || d.mceAction !== 'insertImage' || !d.url) return;

    // Update preview
    preview.innerHTML = '';
    var img = document.createElement('img');
    img.src = d.url;
    img.alt = d.alt || '';
    img.style.maxWidth = '100%';
    img.style.maxHeight = '240px';
    preview.appendChild(img);

    if (d.mediaId) {
      photoInput.value = String(d.mediaId);
    }
    openBtn.textContent = 'Change Photo';
    removeBtn.style.display = 'inline-block';
    closePicker();
  });
})();
</script>
