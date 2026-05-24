<?php
/** @var array $media */
/** @var array $errors */
$errors = $errors ?? [];
$isImage = strpos((string)$media['mime_type'], 'image/') === 0;
$isPdf   = $media['mime_type'] === 'application/pdf';
$url     = '/uploads/' . htmlspecialchars($media['filename'], ENT_QUOTES);
?>
<div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:1em;">
    <h1 style="margin:0;">Edit File</h1>
    <a href="/admin/media">&larr; Back to Photos &amp; Files</a>
</div>

<div style="display:grid; grid-template-columns:1fr 1fr; gap:2em;">
    <div>
        <?php if ($isImage): ?>
            <a href="<?= $url ?>" target="_blank">
                <img src="<?= $url ?>"
                     alt="<?= htmlspecialchars($media['alt_text'] ?? '', ENT_QUOTES) ?>"
                     style="max-width:100%; border-radius:4px;
                            box-shadow:0 1px 3px rgba(0,0,0,0.1);">
            </a>
        <?php elseif ($isPdf): ?>
            <div style="background:#fff; padding:2em; text-align:center; border-radius:4px;
                        box-shadow:0 1px 3px rgba(0,0,0,0.05);">
                <div style="font-size:5em;">📄</div>
                <a href="<?= $url ?>" target="_blank" class="btn-primary"
                   style="text-decoration:none; display:inline-block; margin-top:0.5em;">
                    Open PDF in new tab
                </a>
            </div>
        <?php endif; ?>

        <div style="background:#fff; padding:1em; border-radius:4px;
                    box-shadow:0 1px 3px rgba(0,0,0,0.05); margin-top:1em; font-size:0.9em;">
            <div><strong>Original name:</strong>
                <?= htmlspecialchars($media['original_name'], ENT_QUOTES) ?></div>
            <div style="margin-top:0.3em;"><strong>Type:</strong>
                <?= htmlspecialchars($media['mime_type'], ENT_QUOTES) ?></div>
            <?php if ($isImage && !empty($media['width'])): ?>
                <div style="margin-top:0.3em;"><strong>Dimensions:</strong>
                    <?= (int)$media['width'] ?> &times; <?= (int)$media['height'] ?> px</div>
            <?php endif; ?>
            <div style="margin-top:0.3em;"><strong>File size:</strong>
                <?= number_format((int)$media['file_size'] / 1024, 1) ?> KB</div>
            <div style="margin-top:0.3em;"><strong>Public URL:</strong>
                <code style="font-size:0.85em; word-break:break-all;"><?= $url ?></code></div>
            <div style="margin-top:0.3em;"><strong>Uploaded:</strong>
                <?= htmlspecialchars(date('M j, Y', strtotime($media['uploaded_at'])), ENT_QUOTES) ?></div>
        </div>
    </div>

    <div>
        <form method="post" action="/admin/media/<?= (int)$media['id'] ?>" data-warn-unsaved>
            <?= \Settle\Csrf::field() ?>

            <label>Alt text
                <span class="muted">(short description for screen readers — important for accessibility)</span>
                <input type="text" name="alt_text" maxlength="255"
                       value="<?= htmlspecialchars($media['alt_text'] ?? '', ENT_QUOTES) ?>"
                       placeholder="e.g. Children singing in the sanctuary">
                <?php if (!empty($errors['alt_text'])): ?>
                    <small style="color:var(--error);">
                        <?= htmlspecialchars($errors['alt_text'], ENT_QUOTES) ?>
                    </small>
                <?php endif; ?>
            </label>

            <label>Caption <span class="muted">(optional, shown under the image)</span>
                <textarea name="caption" rows="3" maxlength="500"><?=
                    htmlspecialchars($media['caption'] ?? '', ENT_QUOTES)
                ?></textarea>
                <?php if (!empty($errors['caption'])): ?>
                    <small style="color:var(--error);">
                        <?= htmlspecialchars($errors['caption'], ENT_QUOTES) ?>
                    </small>
                <?php endif; ?>
            </label>

            <div style="margin-top:1.5em;">
                <button type="submit" class="btn-primary">Save Changes</button>
            </div>
        </form>

        <form method="post" action="/admin/media/<?= (int)$media['id'] ?>/delete"
              style="margin-top:2em; padding-top:1em; border-top:1px solid var(--gray-200);">
            <?= \Settle\Csrf::field() ?>
            <button type="submit" class="linklike"
                    style="color:var(--error);"
                    data-confirm="Permanently delete this file? This cannot be undone.">
                Delete this file
            </button>
        </form>
    </div>
</div>
