<?php
/** @var array $event   Row from CalendarOverride::findForAdmin() */
/** @var array $errors  Per-field validation errors */
$errors = $errors ?? [];
$esc = static fn($s): string => htmlspecialchars((string)$s, ENT_QUOTES);

$cacheId  = (int)$event['id'];
$saveUrl  = '/admin/calendar/' . $cacheId . '/override';
$clearUrl = '/admin/calendar/' . $cacheId . '/override/delete';

$imageId = (int)($event['override_image_id'] ?? 0);
$notes   = (string)($event['override_notes'] ?? '');

$previewUrl = '';
$previewAlt = '';
if (!empty($event['override_image_filename'])) {
    $previewUrl = '/uploads/' . $event['override_image_filename'];
    $previewAlt = $event['override_image_alt'] ?? '';
}

// When label for the read-only event context.
try {
    $start    = new \DateTime((string)$event['starts_at']);
    $whenText = !empty($event['is_all_day'])
        ? $start->format('D, M j, Y') . ' (all day)'
        : $start->format('D, M j, Y \a\t g:i A');
} catch (\Throwable $e) {
    $whenText = (string)$event['starts_at'];
}

$hasOverride = !empty($event['override_image_id']) || ($notes !== '');
?>
<div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:1em;">
  <h1 style="margin:0;">Calendar event</h1>
  <a href="/admin/calendar">&larr; Back to Calendar</a>
</div>

<!-- Read-only event context (managed in Google Calendar) -->
<div style="background:#fff; border-radius:4px; padding:1em 1.2em; margin-bottom:1.5em;
            box-shadow:0 1px 3px rgba(0,0,0,0.05);">
  <div style="font-weight:600; font-size:1.1em;"><?= $esc($event['title']) ?></div>
  <div class="muted" style="font-size:0.9em; margin-top:0.2em;"><?= $esc($whenText) ?></div>
  <div style="margin-top:0.6em; display:flex; gap:0.4em; align-items:center;">
    <?php if (!empty($event['is_featured'])): ?>
      <span style="font-size:0.72em; background:var(--brand-primary,#9E2A2B); color:#fff;
                   padding:0.15em 0.6em; border-radius:1em;">Featured</span>
    <?php endif; ?>
    <?php if (!empty($event['is_hidden'])): ?>
      <span style="font-size:0.72em; background:#555; color:#fff;
                   padding:0.15em 0.6em; border-radius:1em;">Hidden</span>
    <?php endif; ?>
    <span class="muted" style="font-size:0.8em;">
      Feature/hide are set with <code>[featured]</code> / <code>[hide]</code> in Google Calendar.
    </span>
  </div>
</div>

<form method="post" action="<?= $esc($saveUrl) ?>" data-warn-unsaved>
  <?= \Settle\Csrf::field() ?>

  <input type="hidden" name="override_image_id" id="override-image-id-input"
         value="<?= $imageId ?>">

  <label>Override image <span class="muted">(optional &mdash; shown with this event on the public calendar)</span>
    <div id="picker-area" style="margin-top:0.4em;">
      <div id="picker-preview" style="background:#f0f0f0; border-radius:4px; padding:1em;
                                       text-align:center; min-height:120px; display:flex;
                                       align-items:center; justify-content:center;">
        <?php if ($previewUrl): ?>
          <img src="<?= $esc($previewUrl) ?>" alt="<?= $esc($previewAlt) ?>"
               style="max-width:100%; max-height:240px;">
        <?php else: ?>
          <span class="muted">No image selected.</span>
        <?php endif; ?>
      </div>
      <div style="margin-top:0.6em; display:flex; gap:0.6em;">
        <button type="button" id="pick-image-btn" class="btn-primary">
          <?= $previewUrl ? 'Change Image' : 'Choose Image from Media Library' ?>
        </button>
        <button type="button" id="remove-image-btn"
                style="background:none; color:var(--error); border:1px solid var(--gray-200);
                       padding:0.6em 1.2em; border-radius:4px; cursor:pointer;
                       <?= $previewUrl ? '' : 'display:none;' ?>">
          Remove Image
        </button>
      </div>
    </div>
    <?php if (!empty($errors['override_image_id'])): ?>
      <small style="color:var(--error);"><?= $esc($errors['override_image_id']) ?></small>
    <?php endif; ?>
  </label>

  <label style="display:block; margin-top:1.2em;">Public note
    <span class="muted">(optional &mdash; appears beneath this event on the public calendar; 500 characters max)</span>
    <textarea name="notes" rows="4" maxlength="500"
              style="width:100%;"><?= $esc($notes) ?></textarea>
    <?php if (!empty($errors['notes'])): ?>
      <small style="color:var(--error);"><?= $esc($errors['notes']) ?></small>
    <?php endif; ?>
  </label>

  <div style="margin-top:1.5em;">
    <button type="submit" class="btn-primary">Save</button>
  </div>
</form>

<?php if ($hasOverride): ?>
  <form method="post" action="<?= $esc($clearUrl) ?>"
        style="margin-top:1em; padding-top:1em; border-top:1px solid var(--gray-200);">
    <?= \Settle\Csrf::field() ?>
    <button type="submit" class="linklike" style="color:var(--error);"
            data-confirm="Remove the image and note for this event? The event itself stays on the calendar.">
      Clear override (remove image &amp; note)
    </button>
  </form>
<?php endif; ?>

<!--
  Image picker modal — same protocol as the Staff and Slideshow editors:
  opens /admin/media/picker in an iframe, listens for a postMessage with
  mceAction:'insertImage' + url/alt/mediaId, then updates the preview and
  the hidden integer field (override_image_id).
-->
<div id="picker-modal" style="display:none; position:fixed; inset:0;
                              background:rgba(0,0,0,0.6); z-index:1000;
                              align-items:center; justify-content:center;">
  <div style="background:#fff; width:90vw; max-width:900px; height:80vh; max-height:600px;
              border-radius:6px; display:flex; flex-direction:column; overflow:hidden;">
    <div style="padding:0.7em 1em; border-bottom:1px solid var(--gray-200);
                display:flex; align-items:center; justify-content:space-between;">
      <strong>Choose an image</strong>
      <button type="button" id="picker-close" class="linklike"
              style="font-size:1.4em; line-height:1;">&times;</button>
    </div>
    <iframe id="picker-frame" title="Media Library" style="border:0; flex-grow:1; width:100%;"></iframe>
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
  var imageInput = document.getElementById('override-image-id-input');
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

  removeBtn.addEventListener('click', function () {
    imageInput.value = '0';
    preview.innerHTML = '<span class="muted">No image selected.</span>';
    openBtn.textContent = 'Choose Image from Media Library';
    removeBtn.style.display = 'none';
  });

  window.addEventListener('message', function (ev) {
    var d = ev.data;
    if (!d || d.mceAction !== 'insertImage' || !d.url) return;

    preview.innerHTML = '';
    var img = document.createElement('img');
    img.src = d.url;
    img.alt = d.alt || '';
    img.style.maxWidth = '100%';
    img.style.maxHeight = '240px';
    preview.appendChild(img);

    if (d.mediaId) {
      imageInput.value = String(d.mediaId);
    }
    openBtn.textContent = 'Change Image';
    removeBtn.style.display = 'inline-block';
    closePicker();
  });
})();
</script>
