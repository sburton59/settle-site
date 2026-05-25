<?php
/** @var array $slide */
/** @var bool $isNew */
/** @var array $errors */
$errors = $errors ?? [];
$action = $isNew ? '/admin/slideshow' : '/admin/slideshow/' . (int)$slide['id'];

// If we have a selected image (existing slide or coming back from a validation
// error), grab its info so we can show a preview.
$previewUrl = '';
$previewAlt = '';
if (!empty($slide['media_filename'])) {
    $previewUrl = '/uploads/' . $slide['media_filename'];
    $previewAlt = $slide['media_alt'] ?? '';
}
?>
<div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:1em;">
    <h1 style="margin:0;"><?= $isNew ? 'Add Slide' : 'Edit Slide' ?></h1>
    <a href="/admin/slideshow">← Back to Slideshow</a>
</div>

<form method="post" action="<?= htmlspecialchars($action, ENT_QUOTES) ?>" data-warn-unsaved>
    <?= \Settle\Csrf::field() ?>
    <input type="hidden" name="media_id" id="media-id-input"
           value="<?= (int)($slide['media_id'] ?? 0) ?>">

    <label>Image
        <div id="picker-area" style="margin-top:0.4em;">
            <div id="picker-preview" style="background:#f0f0f0; border-radius:4px; padding:1em;
                        text-align:center; min-height:160px; display:flex;
                        align-items:center; justify-content:center;">
                <?php if ($previewUrl): ?>
                    <img src="<?= htmlspecialchars($previewUrl, ENT_QUOTES) ?>"
                         alt="<?= htmlspecialchars($previewAlt, ENT_QUOTES) ?>"
                         style="max-width:100%; max-height:300px;">
                <?php else: ?>
                    <span class="muted">No image selected.</span>
                <?php endif; ?>
            </div>
            <button type="button" id="pick-image-btn" class="btn-primary"
                    style="margin-top:0.6em;">
                <?= $previewUrl ? 'Change Image' : 'Choose Image from Media Library' ?>
            </button>
        </div>
        <?php if (!empty($errors['media_id'])): ?>
            <small style="color:var(--error);"><?= htmlspecialchars($errors['media_id'], ENT_QUOTES) ?></small>
        <?php endif; ?>
    </label>

    <label>Caption <span class="muted">(optional, shown over the slide)</span>
        <input type="text" name="caption" maxlength="255"
               value="<?= htmlspecialchars($slide['caption'] ?? '', ENT_QUOTES) ?>"
               placeholder="e.g. Join us this Sunday">
        <?php if (!empty($errors['caption'])): ?>
            <small style="color:var(--error);"><?= htmlspecialchars($errors['caption'], ENT_QUOTES) ?></small>
        <?php endif; ?>
    </label>

    <label>Link URL <span class="muted">(optional — where clicking the slide takes visitors)</span>
        <input type="text" name="link_url" maxlength="500"
               value="<?= htmlspecialchars($slide['link_url'] ?? '', ENT_QUOTES) ?>"
               placeholder="https://example.com  or  /page/sundays">
        <?php if (!empty($errors['link_url'])): ?>
            <small style="color:var(--error);"><?= htmlspecialchars($errors['link_url'], ENT_QUOTES) ?></small>
        <?php endif; ?>
    </label>

    <label class="checkbox">
        <input type="checkbox" name="is_active" value="1" <?= !empty($slide['is_active']) ? 'checked' : '' ?>>
        Slide is active (shown on the homepage)
    </label>

    <div style="margin-top:1.5em;">
        <button type="submit" class="btn-primary">
            <?= $isNew ? 'Add Slide' : 'Save Changes' ?>
        </button>
    </div>
</form>

<!--
  Custom image picker modal. Reuses /admin/media/picker (which we built for
  TinyMCE) — that page posts a message back via postMessage when an image is
  clicked. Our parent here listens for the same message.
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
                    style="font-size:1.4em; line-height:1;">×</button>
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
    var closeBtn   = document.getElementById('picker-close');
    var mediaInput = document.getElementById('media-id-input');
    var preview    = document.getElementById('picker-preview');

    if (!modal || !openBtn) return;

    function openPicker() {
        // Set src each time so we get a fresh listing.
        frame.src = '/admin/media/picker';
        modal.style.display = 'flex';
    }
    function closePicker() {
        modal.style.display = 'none';
        frame.src = 'about:blank';
    }

    openBtn.addEventListener('click', openPicker);
    closeBtn.addEventListener('click', closePicker);

    // Click outside the inner card also closes.
    modal.addEventListener('click', function (e) {
        if (e.target === modal) closePicker();
    });

    // Listen for the picker iframe's "image selected" message.
    // Same protocol the WYSIWYG editor uses: { mceAction: 'insertImage', url, alt }.
    // We extract the URL and the image's filename to look up its ID via a quick
    // ajax round-trip... but actually we already have the filename in the URL
    // path and don't need the ID directly because the picker can be extended
    // to send the ID too. Simpler: extend picker.php to also pass the media_id
    // (next file). For now we'll parse the URL.
    window.addEventListener('message', function (ev) {
        var d = ev.data;
        if (!d || d.mceAction !== 'insertImage' || !d.url) return;

        // Update preview
        preview.innerHTML = '';
        var img = document.createElement('img');
        img.src = d.url;
        img.alt = d.alt || '';
        img.style.maxWidth = '100%';
        img.style.maxHeight = '300px';
        preview.appendChild(img);

        // Store the media ID. The picker now sends it in d.mediaId (see picker.php).
        if (d.mediaId) {
            mediaInput.value = String(d.mediaId);
        }

        openBtn.textContent = 'Change Image';
        closePicker();
    });
})();
</script>
