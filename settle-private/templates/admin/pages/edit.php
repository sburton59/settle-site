<?php
/** @var array $page */
/** @var bool $isNew */
/** @var array $errors */
$errors = $errors ?? [];
$action = $isNew ? '/admin/pages' : '/admin/pages/' . (int)$page['id'];

// If we just came back from a "Save & Preview" submit, the controller
// redirects with ?preview=1 — pick that up here to trigger the JS that
// opens the public page in a new tab.
$autoPreview = !empty($_GET['preview']);
?>
<div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:1em;">
    <h1 style="margin:0;"><?= $isNew ? 'New Page' : 'Editing: ' . htmlspecialchars($page['title'], ENT_QUOTES) ?></h1>
    <a href="/admin/pages">← Back to Pages</a>
</div>

<form method="post" action="<?= htmlspecialchars($action, ENT_QUOTES) ?>" data-warn-unsaved id="page-form">
    <?= \Settle\Csrf::field() ?>

    <!--
      Set by the "Save & Preview" button's JS click handler. The default
      "Save Changes" button leaves it empty, so behavior is unchanged.
    -->
    <input type="hidden" name="preview" id="preview-flag" value="">

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
        <textarea name="body_html" id="page-body" rows="20"><?= htmlspecialchars($page['body_html'], ENT_QUOTES) ?></textarea>
    </label>

    <label>Search engine summary <span class="muted">(optional, up to 300 characters)</span>
        <textarea name="meta_description" rows="2" maxlength="300"><?=
            htmlspecialchars($page['meta_description'] ?? '', ENT_QUOTES)
        ?></textarea>
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
            <button type="submit" id="save-and-preview" class="btn-primary"
                    style="margin-left:0.5em; background:var(--gray-700);">
                Save &amp; Preview ↗
            </button>
            <a href="/page/<?= htmlspecialchars($page['slug'], ENT_QUOTES) ?>" target="_blank"
               style="margin-left:1em;" class="muted">
                View published page ↗
            </a>
        <?php endif; ?>
    </div>
</form>

<!--
  TinyMCE — loaded from jsdelivr CDN, no API key required for the GPL build.
  Locked to a major version so we don't get surprised by a breaking release.
-->
<script src="https://cdn.jsdelivr.net/npm/tinymce@7/tinymce.min.js" referrerpolicy="origin"></script>
<script>
(function () {
    'use strict';

    // ----- Save & Preview wiring ---------------------------------------
    // When the user clicks "Save & Preview", set the hidden flag before
    // the form submits naturally. The controller picks it up and redirects
    // back to this page with ?preview=1, which triggers the popup below.
    var previewBtn = document.getElementById('save-and-preview');
    if (previewBtn) {
        previewBtn.addEventListener('click', function () {
            document.getElementById('preview-flag').value = '1';
        });
    }

    // If we just came back from a successful "Save & Preview", pop the
    // public page in a new tab. Use a slight delay so the browser doesn't
    // block it as an automatic popup.
    <?php if ($autoPreview && !$isNew): ?>
    setTimeout(function () {
        window.open('/page/<?= addslashes($page['slug']) ?>', '_blank');
    }, 200);
    <?php endif; ?>

    // Get the CSRF token from any existing form on the page — we need it
    // for the drag-drop upload handler.
    var csrfToken = (function () {
        var input = document.querySelector('input[name="_csrf"]');
        return input ? input.value : '';
    })();

    tinymce.init({
        selector: '#page-body',
        license_key: 'gpl',
        promotion: false,
        branding: false,

        height: 500,
        menubar: false,
        plugins: 'lists link image code autoresize paste',
        toolbar:
            'undo redo | blocks | bold italic | bullist numlist | ' +
            'link image mediaLibraryButton | blockquote | code',
        block_formats:
            'Paragraph=p; Heading=h2; Subheading=h3; Quote=blockquote',
        relative_urls: false,
        remove_script_host: true,

        paste_as_text: false,
        paste_data_images: false,
        paste_remove_styles_if_webkit: true,
        paste_webkit_styles: 'none',

        images_upload_handler: function (blobInfo, progress) {
            return new Promise(function (resolve, reject) {
                var xhr = new XMLHttpRequest();
                xhr.open('POST', '/admin/media/upload-from-editor');
                xhr.upload.onprogress = function (e) {
                    if (e.lengthComputable) progress(e.loaded / e.total * 100);
                };
                xhr.onload = function () {
                    if (xhr.status < 200 || xhr.status >= 300) {
                        reject({ message: 'Upload failed (HTTP ' + xhr.status + ')', remove: true });
                        return;
                    }
                    try {
                        var data = JSON.parse(xhr.responseText);
                        if (!data || !data.location) {
                            reject({ message: 'Invalid response from server', remove: true });
                            return;
                        }
                        resolve(data.location);
                    } catch (e) {
                        reject({ message: 'Could not parse server response', remove: true });
                    }
                };
                xhr.onerror = function () {
                    reject({ message: 'Network error during upload', remove: true });
                };

                var form = new FormData();
                form.append('file', blobInfo.blob(), blobInfo.filename());
                form.append('_csrf', csrfToken);
                xhr.send(form);
            });
        },

        setup: function (editor) {
            editor.ui.registry.addButton('mediaLibraryButton', {
                icon: 'gallery',
                tooltip: 'Insert from Media Library',
                onAction: function () {
                    editor.windowManager.openUrl({
                        title: 'Media Library',
                        url: '/admin/media/picker',
                        width: 900,
                        height: 600,
                        onMessage: function (api, details) {
                            if (details.mceAction === 'insertImage' && details.url) {
                                editor.insertContent(
                                    '<img src="' + editor.dom.encode(details.url) + '"' +
                                    (details.alt ? ' alt="' + editor.dom.encode(details.alt) + '"' : '') +
                                    '>'
                                );
                                api.close();
                            }
                        },
                    });
                },
            });
        },

        save_enablewhendirty: true,
    });
})();
</script>
