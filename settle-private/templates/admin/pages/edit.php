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
            <a href="/page/<?= htmlspecialchars($page['slug'], ENT_QUOTES) ?>" target="_blank" style="margin-left:1em;">Preview ↗</a>
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

    // Get the CSRF token from any existing form on the page — we need it
    // for the drag-drop upload handler.
    var csrfToken = (function () {
        var input = document.querySelector('input[name="_csrf"]');
        return input ? input.value : '';
    })();

    tinymce.init({
        selector: '#page-body',
        license_key: 'gpl',           // we are using the open-source build
        promotion: false,             // hide "upgrade to premium" buttons
        branding: false,              // hide "Powered by TinyMCE" footer

        height: 500,
        menubar: false,               // less clutter
        plugins: 'lists link image code autoresize paste',
        toolbar:
            'undo redo | blocks | bold italic | bullist numlist | ' +
            'link image mediaLibraryButton | blockquote | code',
        block_formats:
            'Paragraph=p; Heading=h2; Subheading=h3; Quote=blockquote',
        relative_urls: false,
        remove_script_host: true,     // for inserted media URLs

        // Smart paste from Word/Google Docs — strip pasted styles aggressively.
        paste_as_text: false,
        paste_data_images: false,
        paste_remove_styles_if_webkit: true,
        paste_webkit_styles: 'none',

        // Drag-and-drop / paste-image upload handler.
        // Posts the file to /admin/media/upload-from-editor and expects
        // a JSON response: { "location": "/uploads/2026/05/xxxx.jpg" }
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

        // Custom toolbar button that opens our Media Library in a modal.
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
                        // The iframe at /admin/media/picker posts a message
                        // back when an image is selected (see picker.php).
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

        // Save TinyMCE state back to the textarea on form submit so the
        // 'data-warn-unsaved' handler in admin.js sees the change.
        save_enablewhendirty: true,
    });
})();
</script>
