<?php
/**
 * @var array      $post          Post row (Post::blank() merged on new/error)
 * @var bool       $isNew
 * @var array      $errors
 * @var array      $categories    [{id,name,slug}] — the full curated list
 * @var int[]      $selectedCats  category ids currently assigned
 * @var array|null $featured      ['url'=>..., 'alt'=>...] or null
 */
$errors  = $errors ?? [];
$action  = $isNew ? '/admin/posts' : '/admin/posts/' . (int) $post['id'];
$selected = array_flip(array_map('intval', $selectedCats ?? []));
$autoPreview = !empty($_GET['preview']);
$status = $post['status'] ?? 'draft';

// Prefill the publish date/time field: prefer a raw re-render value
// (post['publish_at'], set on a validation failure), else format the stored
// published_at into the datetime-local shape, else leave blank.
$pubAtInput = '';
if (isset($post['publish_at']) && $post['publish_at'] !== '') {
    $pubAtInput = (string) $post['publish_at'];
} elseif (!empty($post['published_at'])) {
    $pubAtInput = date('Y-m-d\TH:i', strtotime((string) $post['published_at']));
}
?>
<div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:1em;">
  <h1 style="margin:0;"><?= $isNew ? 'New Post' : 'Editing: ' . htmlspecialchars($post['title'], ENT_QUOTES) ?></h1>
  <a href="/admin/posts">← Back to Posts</a>
</div>

<form method="post" action="<?= htmlspecialchars($action, ENT_QUOTES) ?>" data-warn-unsaved id="post-form">
  <?= \Settle\Csrf::field() ?>
  <input type="hidden" name="preview" id="preview-flag" value="">
  <input type="hidden" name="featured_media_id" id="featured-media-id"
         value="<?= (int) ($post['featured_media_id'] ?? 0) ?>">

  <label>Title
    <input type="text" name="title" required
           value="<?= htmlspecialchars($post['title'], ENT_QUOTES) ?>">
    <?php if (!empty($errors['title'])): ?>
      <small style="color:var(--error);"><?= htmlspecialchars($errors['title'], ENT_QUOTES) ?></small>
    <?php endif; ?>
  </label>

  <label>Web address <span class="muted">(leave blank to fill in automatically from the title)</span>
    <input type="text" name="slug"
           value="<?= htmlspecialchars($post['slug'], ENT_QUOTES) ?>"
           placeholder="auto-generated-from-title">
    <small class="muted">The post will live at <code>/blog/&lt;web-address&gt;</code>.</small>
    <?php if (!empty($errors['slug'])): ?>
      <small style="color:var(--error);"><?= htmlspecialchars($errors['slug'], ENT_QUOTES) ?></small>
    <?php endif; ?>
  </label>

  <label>Summary <span class="muted">(optional — shown on the blog listing; auto-generated from the post if left blank)</span>
    <textarea name="excerpt" rows="2" maxlength="500"><?= htmlspecialchars($post['excerpt'] ?? '', ENT_QUOTES) ?></textarea>
    <?php if (!empty($errors['excerpt'])): ?>
      <small style="color:var(--error);"><?= htmlspecialchars($errors['excerpt'], ENT_QUOTES) ?></small>
    <?php endif; ?>
  </label>

  <label>Featured image <span class="muted">(optional — shown at the top of the post and on the listing)</span>
    <div id="picker-area" style="margin-top:0.4em;">
      <div id="picker-preview" style="background:#f0f0f0; border-radius:4px; padding:1em;
                  text-align:center; min-height:140px; display:flex;
                  align-items:center; justify-content:center;">
        <?php if (!empty($featured['url'])): ?>
          <img src="<?= htmlspecialchars($featured['url'], ENT_QUOTES) ?>"
               alt="<?= htmlspecialchars($featured['alt'] ?? '', ENT_QUOTES) ?>"
               style="max-width:100%; max-height:260px;">
        <?php else: ?>
          <span class="muted">No image selected.</span>
        <?php endif; ?>
      </div>
      <button type="button" id="pick-featured-btn" class="btn-primary" style="margin-top:0.6em;">
        <?= !empty($featured['url']) ? 'Change Featured Image' : 'Choose Featured Image' ?>
      </button>
      <?php if (!empty($featured['url'])): ?>
        <button type="button" id="clear-featured-btn" class="linklike" style="margin-left:0.6em;">Remove</button>
      <?php endif; ?>
    </div>
  </label>

  <label>Post content
    <textarea name="body_html" id="post-body" rows="20"><?= htmlspecialchars($post['body_html'] ?? '', ENT_QUOTES) ?></textarea>
  </label>

  <fieldset style="border:1px solid var(--gray-200,#ddd); border-radius:4px; padding:0.8em 1em; margin-top:1em;">
    <legend style="padding:0 0.4em;">Categories</legend>
    <?php if (empty($categories)): ?>
      <p class="muted" style="margin:0.3em 0;">
        No categories yet. <a href="/admin/categories">Create some</a> to group posts by ministry area
        (an editor can do this).
      </p>
    <?php else: ?>
      <div style="display:flex; flex-wrap:wrap; gap:0.4em 1.4em;">
        <?php foreach ($categories as $c): ?>
          <label class="checkbox" style="display:inline-flex; align-items:center; gap:0.35em; margin:0;">
            <input type="checkbox" name="category_ids[]" value="<?= (int) $c['id'] ?>"
                   <?= isset($selected[(int) $c['id']]) ? 'checked' : '' ?>>
            <?= htmlspecialchars($c['name'], ENT_QUOTES) ?>
          </label>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </fieldset>

  <label style="margin-top:1em;">Status
    <select name="status">
      <option value="draft"     <?= $status === 'draft'     ? 'selected' : '' ?>>Draft — only you/editors can see it</option>
      <option value="published" <?= $status === 'published' ? 'selected' : '' ?>>Published — live on the website</option>
      <option value="archived"  <?= $status === 'archived'  ? 'selected' : '' ?>>Archived — hidden from the website</option>
    </select>
    <?php if (!empty($errors['status'])): ?>
      <small style="color:var(--error);"><?= htmlspecialchars($errors['status'], ENT_QUOTES) ?></small>
    <?php endif; ?>
  </label>

  <label>Publish date &amp; time <span class="muted">(optional)</span>
    <input type="datetime-local" name="publish_at"
           value="<?= htmlspecialchars($pubAtInput, ENT_QUOTES) ?>">
    <small class="muted">
      Applies when Status is &ldquo;Published.&rdquo; Leave blank to publish immediately;
      set a future date &amp; time to schedule it. Signed-in staff can preview a scheduled
      post before it goes live.
    </small>
    <?php if (!empty($errors['publish_at'])): ?>
      <small style="color:var(--error);"><?= htmlspecialchars($errors['publish_at'], ENT_QUOTES) ?></small>
    <?php endif; ?>
  </label>

  <div style="margin-top:1.5em;">
    <button type="submit" class="btn-primary"><?= $isNew ? 'Create Post' : 'Save Changes' ?></button>
    <?php if (!$isNew): ?>
      <button type="submit" id="save-and-preview" class="btn-primary"
              style="margin-left:0.5em; background:var(--gray-700,#444);">
        Save &amp; Preview ↗
      </button>
      <a href="/blog/<?= htmlspecialchars($post['slug'], ENT_QUOTES) ?>" target="_blank"
         style="margin-left:1em;" class="muted">View published post ↗</a>
    <?php endif; ?>
  </div>
</form>

<!-- Featured-image picker modal (reuses /admin/media/picker, same as the slideshow editor) -->
<div id="picker-modal" style="display:none; position:fixed; inset:0;
            background:rgba(0,0,0,0.6); z-index:1000; align-items:center; justify-content:center;">
  <div style="background:#fff; width:90vw; max-width:900px; height:80vh; max-height:600px;
              border-radius:6px; display:flex; flex-direction:column; overflow:hidden;">
    <div style="padding:0.7em 1em; border-bottom:1px solid var(--gray-200,#ddd);
                display:flex; align-items:center; justify-content:space-between;">
      <strong>Choose a featured image</strong>
      <button type="button" id="picker-close" class="linklike" style="font-size:1.4em; line-height:1;">×</button>
    </div>
    <iframe id="picker-frame" style="border:0; flex-grow:1; width:100%;"></iframe>
  </div>
</div>

<!-- TinyMCE — GPL build from jsdelivr, no API key. Same setup as the Pages editor. -->
<script src="https://cdn.jsdelivr.net/npm/tinymce@7/tinymce.min.js" referrerpolicy="origin"></script>
<script>
(function () {
    'use strict';

    // ----- Save & Preview wiring (same as Pages) -----------------------
    var previewBtn = document.getElementById('save-and-preview');
    if (previewBtn) {
        previewBtn.addEventListener('click', function () {
            document.getElementById('preview-flag').value = '1';
        });
    }
    <?php if ($autoPreview && !$isNew): ?>
    setTimeout(function () {
        window.open('/blog/<?= addslashes($post['slug']) ?>', '_blank');
    }, 200);
    <?php endif; ?>

    var csrfToken = (function () {
        var input = document.querySelector('input[name="_csrf"]');
        return input ? input.value : '';
    })();

    // ----- Featured-image picker --------------------------------------
    // IMPORTANT: the inline-image picker (TinyMCE's "Insert from Media
    // Library" button) and this featured-image picker both post the same
    // { mceAction:'insertImage', ... } message from /admin/media/picker.
    // To stop an inline-image insert from also changing the featured image,
    // this global listener only acts while the featured modal is OPEN.
    var modal         = document.getElementById('picker-modal');
    var frame         = document.getElementById('picker-frame');
    var openBtn       = document.getElementById('pick-featured-btn');
    var closeBtn      = document.getElementById('picker-close');
    var clearBtn      = document.getElementById('clear-featured-btn');
    var mediaInput    = document.getElementById('featured-media-id');
    var preview       = document.getElementById('picker-preview');
    var featuredOpen  = false;

    function openPicker() {
        featuredOpen = true;
        frame.src = '/admin/media/picker';
        modal.style.display = 'flex';
    }
    function closePicker() {
        featuredOpen = false;
        modal.style.display = 'none';
        frame.src = 'about:blank';
    }
    if (openBtn) openBtn.addEventListener('click', openPicker);
    if (closeBtn) closeBtn.addEventListener('click', closePicker);
    if (modal) modal.addEventListener('click', function (e) { if (e.target === modal) closePicker(); });
    if (clearBtn) {
        clearBtn.addEventListener('click', function () {
            mediaInput.value = '0';
            preview.innerHTML = '<span class="muted">No image selected.</span>';
            if (openBtn) openBtn.textContent = 'Choose Featured Image';
            clearBtn.style.display = 'none';
        });
    }

    window.addEventListener('message', function (ev) {
        if (!featuredOpen) return;            // ignore inline-image inserts
        var d = ev.data;
        if (!d || d.mceAction !== 'insertImage' || !d.url) return;

        preview.innerHTML = '';
        var img = document.createElement('img');
        img.src = d.url;
        img.alt = d.alt || '';
        img.style.maxWidth = '100%';
        img.style.maxHeight = '260px';
        preview.appendChild(img);

        if (d.mediaId) mediaInput.value = String(d.mediaId);
        if (openBtn) openBtn.textContent = 'Change Featured Image';
        if (clearBtn) clearBtn.style.display = '';
        closePicker();
    });

    // ----- TinyMCE (inline body editor; identical config to Pages) -----
    tinymce.init({
        selector: '#post-body',
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
