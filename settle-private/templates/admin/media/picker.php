<?php
/** @var array $items */
/**
 * Media Picker — rendered inside an iframe by TinyMCE or any other modal.
 *
 * Clicking an image posts a message back to the parent window with:
 *   { mceAction: 'insertImage', url, alt, mediaId }
 *
 * - TinyMCE's editor uses url+alt to insert <img>.
 * - The slideshow edit form uses mediaId to populate its hidden media_id field.
 *
 * Standalone HTML document (not wrapped in admin layout).
 */
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Media Library</title>
    <style>
        body {
            margin: 0;
            font: 14px/1.4 -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: #f8f8f8;
            color: #1a1a1a;
        }
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
            gap: 0.75em;
            padding: 1em;
        }
        .tile {
            background: #fff;
            border: 2px solid transparent;
            border-radius: 4px;
            cursor: pointer;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            transition: border-color 0.1s;
        }
        .tile:hover { border-color: #9E2A2B; }
        .thumb {
            aspect-ratio: 1 / 1;
            background: #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        .thumb img { width: 100%; height: 100%; object-fit: cover; }
        .label {
            padding: 0.4em 0.6em;
            font-size: 0.8em;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .empty {
            padding: 3em 1em;
            text-align: center;
            color: #999;
        }
        .empty a { color: #9E2A2B; }
    </style>
</head>
<body>
<?php if (empty($items)): ?>
    <div class="empty">
        <p>No images uploaded yet.</p>
        <p><a href="/admin/media" target="_top">Open the Media Library</a> to upload some.</p>
    </div>
<?php else: ?>
    <div class="grid">
        <?php foreach ($items as $m): ?>
            <?php
                $isImage = strpos((string)$m['mime_type'], 'image/') === 0;
                if (!$isImage) continue;
                $url  = '/uploads/' . htmlspecialchars($m['filename'], ENT_QUOTES);
                $alt  = htmlspecialchars($m['alt_text'] ?? '', ENT_QUOTES);
                $name = htmlspecialchars($m['original_name'], ENT_QUOTES);
            ?>
            <div class="tile"
                 data-media-id="<?= (int)$m['id'] ?>"
                 data-url="<?= $url ?>"
                 data-alt="<?= $alt ?>"
                 title="<?= $name ?>">
                <div class="thumb">
                    <img src="<?= $url ?>" alt="<?= $alt ?>" loading="lazy">
                </div>
                <div class="label"><?= $name ?></div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<script>
(function () {
    'use strict';
    document.querySelectorAll('.tile').forEach(function (tile) {
        tile.addEventListener('click', function () {
            window.parent.postMessage({
                mceAction: 'insertImage',
                url:     tile.getAttribute('data-url'),
                alt:     tile.getAttribute('data-alt'),
                mediaId: parseInt(tile.getAttribute('data-media-id'), 10),
            }, '*');
        });
    });
})();
</script>
</body>
</html>
