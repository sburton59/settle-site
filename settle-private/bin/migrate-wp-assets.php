<?php
declare(strict_types=1);

/**
 * migrate-wp-assets.php — one-time content-migration helper (roadmap #10).
 *
 * Downloads the images and PDFs that the migrated pages still reference on the
 * OLD WordPress site (settleumc.com/wp-content/uploads/...), stores them in the
 * Media Library exactly the way an admin upload would, and rewires the pages:
 *   - PDFs: the wp-content URL in the page body is swapped for the new local
 *     /uploads/... URL (link text is left alone).
 *   - Images: each page's "<!-- TODO image pass ... -->" comment is replaced
 *     with the imported <img>(s). Employment's hiring banner is prepended
 *     (that page has no image anchor).
 *
 *   php settle-private/bin/migrate-wp-assets.php
 *   php settle-private/bin/migrate-wp-assets.php /home/USER/public_html/Settle/uploads
 *
 * MUST be run BEFORE DNS cutover — it pulls from settleumc.com, which only
 * serves the old WordPress files until launch.
 *
 * Idempotent / safe to re-run:
 *   - An asset whose Media row already exists (matched on original_name) is NOT
 *     re-downloaded; its existing local URL is reused.
 *   - A body rewrite is skipped when the old wp URL is already gone (PDFs) or
 *     the new image URL is already present (images) — so nothing is doubled.
 *
 * Best-effort, mirroring the project's external-call philosophy: a failed
 * download is logged and skipped; it never aborts the run or half-writes a row.
 *
 * NOT wired to any thumbnail/resize code of its own where it can avoid it —
 * thumbnails go through the real \Settle\Upload::makeThumbnail(), and rows go in
 * via \Settle\Model\Media::create(). The only thing reimplemented here is the
 * >2000px down-scale, because \Settle\Upload::handle()/maybeResize() are
 * HTTP-upload-only (is_uploaded_file) and private respectively; the GD calls
 * and quality constants below mirror Upload exactly.
 *
 * Exit codes: 0 = ran (per-asset summary printed), 1 = setup failure.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("This script is CLI-only.\n");
}

// ---- mirrors of \Settle\Upload constants (kept in sync deliberately) --------
const MWP_MAX_DIMENSION = 2000;
const MWP_JPEG_QUALITY  = 85;
const MWP_PNG_COMPRESS  = 6;
const MWP_WEBP_QUALITY  = 85;
const MWP_ALLOWED = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/gif'  => 'gif',
    'image/webp' => 'webp',
    'application/pdf' => 'pdf',
];
const MWP_RASTER = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
const MWP_SRC = 'https://settleumc.com/wp-content/uploads/';

/**
 * The migration map. Three buckets:
 *   pdfs    — swap the old URL in $slug's body for the new local URL.
 *   images  — import + embed at $slug's comment anchor (token) in order.
 *   import  — import to the Media Library only (no page placement).
 */
function mwp_map(): array
{
    return [
        'pdfs' => [
            ['slug' => 'employment',      'file' => 'Untitled-document-5.pdf',              'name' => 'Preschool-Director-Job-Description-2026.pdf'],
            ['slug' => 'employment',      'file' => 'Teacher-Job-Posting.docx.pdf',         'name' => 'Teacher-Job-Posting.pdf'],
            ['slug' => 'employment',      'file' => 'Afterschool-Worker-Job-Posting.docx.pdf','name' => 'Afterschool-Worker-Job-Posting.pdf'],
            ['slug' => 'parents-day-out', 'file' => 'PDOSummerRegistration26.pdf',          'name' => 'PDO-Summer-Registration-2026.pdf'],
            ['slug' => 'parents-day-out', 'file' => 'Settle-PDO.pdf',                        'name' => 'Settle-PDO-Registration.pdf'],
            ['slug' => 'adult-ministries','file' => 'Settle-Memorial-UMC.pdf',              'name' => 'Adult-Sunday-School-Classes.pdf'],
        ],
        'images' => [
            // slug => [anchor token | null=prepend, [ [file, name, alt], ... ] ]
            'directions-parking' => ['anchor' => 'Settle-Memorial-Parking-Map', 'items' => [
                ['file' => 'Settle-Memorial-Parking-Map-1280x961.webp', 'name' => 'Settle-Memorial-Parking-Map.webp', 'alt' => 'Settle Memorial parking map'],
            ]],
            'children' => ['anchor' => 'IMG_6550', 'items' => [
                ['file' => 'IMG_6550-1280x1707.jpg', 'name' => 'children-ministry-1.jpg', 'alt' => "Children's ministry at Settle"],
                ['file' => 'IMG_6542-1280x1707.jpg', 'name' => 'children-ministry-2.jpg', 'alt' => 'Children at Settle Memorial'],
            ]],
            'settle-preschool' => ['anchor' => '26-27-info-sheet', 'items' => [
                ['file' => '26-27-info-sheet-scaled.jpg.webp', 'name' => 'Settle-Preschool-info-sheet.webp', 'alt' => 'Settle Preschool information sheet'],
                ['file' => 'IMG_6565-1280x1707.jpg',           'name' => 'Settle-Preschool.jpg',            'alt' => 'Settle Preschool'],
            ]],
            'parents-day-out' => ['anchor' => 'IMG_6540', 'items' => [
                ['file' => 'IMG_6540-1280x960.jpg', 'name' => 'Parents-Day-Out.jpg', 'alt' => "Parent's Day Out at Settle"],
            ]],
            'youth' => ['anchor' => '52147029939', 'items' => [
                ['file' => '52147029939_5c9f5b306c_o-1280x960.jpg', 'name' => 'Settle-youth.jpg', 'alt' => 'Settle youth group'],
            ]],
            'the-roadrunners' => ['anchor' => 'Roadrunner', 'items' => [
                ['file' => 'Roadrunner-1280x906.jpg', 'name' => 'Settle-Roadrunners.jpg', 'alt' => 'The Settle Roadrunners'],
            ]],
            'missions' => ['anchor' => '52032154230', 'items' => [
                ['file' => '52032154230_e14988c590_o-1-1280x960.jpg', 'name' => 'Settle-missions.jpg', 'alt' => 'Mission work at Settle'],
            ]],
            'employment' => ['anchor' => null, 'items' => [ // null anchor => prepend
                ['file' => 'Settle-Preschool-is-Hiring-1280x720.jpg.webp', 'name' => 'Settle-Preschool-is-Hiring.webp', 'alt' => 'Settle Preschool is hiring'],
            ]],
        ],
        'import' => [
            ['file' => 'Church-Front-Cropped-scaled.jpg', 'name' => 'Church-Front.jpg', 'alt' => 'Settle Memorial United Methodist Church'],
        ],
    ];
}

// ===== PURE HELPERS (unit-tested by the harness) =============================

/** Build the <img> block inserted into a page body. */
function mwp_figure(string $url, string $alt): string
{
    $alt = htmlspecialchars($alt, ENT_QUOTES, 'UTF-8');
    return '<p><img src="' . $url . '" alt="' . $alt . '" loading="lazy" style="max-width:100%;height:auto;"></p>';
}

/** Swap an exact old URL for a new one (PDF links). No-op if absent. */
function mwp_swap_pdf(string $body, string $oldUrl, string $newUrl): string
{
    return str_replace($oldUrl, $newUrl, $body);
}

/**
 * Replace the HTML comment containing $token with $figures.
 * Returns [newBody, didReplace]. Comment-scoped, so body text never matches.
 */
function mwp_embed_at_anchor(string $body, string $token, string $figures): array
{
    $pattern = '/<!--[^>]*' . preg_quote($token, '/') . '[^>]*-->/';
    if (!preg_match($pattern, $body)) {
        return [$body, false];
    }
    return [preg_replace($pattern, $figures, $body, 1), true];
}

/** Prepend $figures to the body (used where there is no comment anchor). */
function mwp_prepend(string $body, string $figures): string
{
    return $figures . "\n" . $body;
}

/**
 * Down-scale an image file in place if its long edge exceeds MWP_MAX_DIMENSION.
 * Mirrors \Settle\Upload::maybeResize (private). Returns [width, height] of the
 * final file, or null on failure.
 */
function mwp_resize_in_place(string $path, string $mime): ?array
{
    $info = @getimagesize($path);
    if (!$info) {
        return null;
    }
    [$origW, $origH] = $info;
    $long = max($origW, $origH);
    if ($long <= MWP_MAX_DIMENSION) {
        return [$origW, $origH];
    }
    $scale = MWP_MAX_DIMENSION / $long;
    $newW = (int)round($origW * $scale);
    $newH = (int)round($origH * $scale);
    $src = mwp_load($path, $mime);
    if (!$src) {
        return null;
    }
    $dst = imagecreatetruecolor($newW, $newH);
    if (in_array($mime, ['image/png', 'image/gif', 'image/webp'], true)) {
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
        imagefilledrectangle($dst, 0, 0, $newW, $newH, $transparent);
    }
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $origW, $origH);
    $saved = mwp_save($dst, $path, $mime);
    imagedestroy($src);
    imagedestroy($dst);
    return $saved ? [$newW, $newH] : null;
}

function mwp_load(string $path, string $mime)
{
    switch ($mime) {
        case 'image/jpeg': return @imagecreatefromjpeg($path);
        case 'image/png':  return @imagecreatefrompng($path);
        case 'image/gif':  return @imagecreatefromgif($path);
        case 'image/webp': return @imagecreatefromwebp($path);
    }
    return null;
}

function mwp_save($image, string $path, string $mime): bool
{
    switch ($mime) {
        case 'image/jpeg': return imagejpeg($image, $path, MWP_JPEG_QUALITY);
        case 'image/png':  return imagepng($image, $path, MWP_PNG_COMPRESS);
        case 'image/gif':  return imagegif($image, $path);
        case 'image/webp': return imagewebp($image, $path, MWP_WEBP_QUALITY);
    }
    return false;
}

// ===== NETWORK ===============================================================

/** Download a URL to a temp file. Returns the temp path, or null on failure. */
function mwp_download(string $url, string $tmpDir): ?string
{
    $tmp = tempnam($tmpDir, 'mwp_');
    if ($tmp === false) {
        return null;
    }
    $bytes = null;
    if (function_exists('curl_init')) {
        $fh = fopen($tmp, 'wb');
        if ($fh === false) { @unlink($tmp); return null; }
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_FILE           => $fh,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 4,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_TIMEOUT        => 60,
            CURLOPT_USERAGENT      => 'SettleMigrate/1.0',
        ]);
        $okExec = curl_exec($ch);
        $code   = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        fclose($fh);
        if ($okExec === false || $code < 200 || $code >= 300) {
            @unlink($tmp);
            return null;
        }
    } else {
        $ctx = stream_context_create(['http' => ['timeout' => 60, 'user_agent' => 'SettleMigrate/1.0']]);
        $bytes = @file_get_contents($url, false, $ctx);
        if ($bytes === false || $bytes === '') { @unlink($tmp); return null; }
        if (@file_put_contents($tmp, $bytes) === false) { @unlink($tmp); return null; }
    }
    if (!is_file($tmp) || filesize($tmp) <= 0) { @unlink($tmp); return null; }
    return $tmp;
}

// ===== ORCHESTRATION =========================================================

function mwp_main(array $argv): int
{
    $root       = dirname(__DIR__);              // settle-private/
    $configPath = $root . '/config/config.php';
    if (!is_file($configPath)) {
        fwrite(STDERR, "config.php not found at {$configPath}\n");
        return 1;
    }
    $config = require $configPath;
    $GLOBALS['settle_config'] = $config;

    spl_autoload_register(static function (string $class) use ($root): void {
        $prefix = 'Settle\\';
        if (strncmp($class, $prefix, strlen($prefix)) !== 0) return;
        $file = $root . '/src/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
        if (is_file($file)) require $file;
    });

    \Settle\Database::init($config['db']);
    date_default_timezone_set('America/Chicago');

    $uploadRoot = $argv[1] ?? (dirname($root) . '/public_html/Settle/uploads');
    $uploadRoot = realpath($uploadRoot) ?: $uploadRoot;
    if (!is_dir($uploadRoot) || !is_writable($uploadRoot)) {
        fwrite(STDERR, "Uploads directory missing or not writable: {$uploadRoot}\n");
        fwrite(STDERR, "Pass the correct path as the first argument.\n");
        return 1;
    }

    // uploaded_by: first active admin, else lowest user id.
    $uid = (int)(\Settle\Database::query(
        "SELECT id FROM users WHERE role = 'admin' ORDER BY id LIMIT 1"
    )->fetchColumn() ?: 0);
    if ($uid === 0) {
        $uid = (int)(\Settle\Database::query("SELECT id FROM users ORDER BY id LIMIT 1")->fetchColumn() ?: 0);
    }
    if ($uid === 0) {
        fwrite(STDERR, "No users found to own the uploads.\n");
        return 1;
    }

    echo "WP asset migration — uploads: {$uploadRoot}  (uploaded_by user #{$uid})\n";
    $map = mwp_map();
    $tmpDir = sys_get_temp_dir();
    $imported = 0; $reused = 0; $failed = 0; $swapped = 0; $embedded = 0;

    // Resolve one asset to a local /uploads URL (import or reuse). Returns
    // ['url'=>..,'rel'=>..] or null on failure.
    $resolve = function (string $file, string $name, ?string $alt) use ($uploadRoot, $uid, $tmpDir, &$imported, &$reused, &$failed): ?array {
        $existing = \Settle\Database::query(
            'SELECT filename FROM media WHERE original_name = :n LIMIT 1', [':n' => $name]
        )->fetchColumn();
        if ($existing !== false) {
            $reused++;
            echo "  reuse   {$name}\n";
            return ['url' => '/uploads/' . ltrim((string)$existing, '/'), 'rel' => (string)$existing];
        }
        $tmp = mwp_download(MWP_SRC . $file, $tmpDir);
        if ($tmp === null) {
            $failed++;
            echo "  FAILED  download {$file}\n";
            return null;
        }
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($tmp) ?: '';
        if (!isset(MWP_ALLOWED[$mime])) {
            $failed++; @unlink($tmp);
            echo "  FAILED  {$file} — type '{$mime}' not allowed\n";
            return null;
        }
        $ext = MWP_ALLOWED[$mime];
        $rel = date('Y') . '/' . date('m') . '/' . bin2hex(random_bytes(8)) . '.' . $ext;
        $dir = $uploadRoot . '/' . dirname($rel);
        if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
            $failed++; @unlink($tmp);
            echo "  FAILED  {$file} — cannot create {$dir}\n";
            return null;
        }
        $dest = $uploadRoot . '/' . $rel;
        if (!@copy($tmp, $dest)) {
            $failed++; @unlink($tmp);
            echo "  FAILED  {$file} — cannot write {$dest}\n";
            return null;
        }
        @unlink($tmp);

        $width = null; $height = null; $thumb = null;
        if ($mime !== 'application/pdf') {
            $dims = mwp_resize_in_place($dest, $mime);
            if ($dims === null) {
                $failed++; @unlink($dest);
                echo "  FAILED  {$file} — image unreadable\n";
                return null;
            }
            [$width, $height] = $dims;
            $thumb = \Settle\Upload::makeThumbnail($rel, $uploadRoot); // real shared code
        }
        \Settle\Model\Media::create([
            'filename'           => $rel,
            'thumbnail_filename' => $thumb,
            'original_name'      => $name,
            'mime_type'          => $mime,
            'file_size'          => (int)filesize($dest),
            'width'              => $width,
            'height'             => $height,
            'alt_text'           => $alt,
            'caption'            => null,
        ], $uid);
        $imported++;
        echo "  import  {$name}  ->  /uploads/{$rel}\n";
        return ['url' => '/uploads/' . $rel, 'rel' => $rel];
    };

    $loadBody = fn(string $slug): ?array => (\Settle\Database::query(
        'SELECT id, body_html FROM pages WHERE slug = :s LIMIT 1', [':s' => $slug]
    )->fetch() ?: null);
    $saveBody = function (int $id, string $body): void {
        \Settle\Database::query('UPDATE pages SET body_html = :b WHERE id = :id', [':b' => $body, ':id' => $id]);
    };

    // --- PDFs: import + URL swap ---
    echo "\nPDFs:\n";
    foreach ($map['pdfs'] as $pdf) {
        $res = $resolve($pdf['file'], $pdf['name'], null);
        if ($res === null) continue;
        $page = $loadBody($pdf['slug']);
        if ($page === null) { echo "  (page '{$pdf['slug']}' not found; URL not swapped)\n"; continue; }
        $old = MWP_SRC . $pdf['file'];
        if (strpos((string)$page['body_html'], $old) === false) {
            echo "  (link already swapped on '{$pdf['slug']}')\n";
            continue;
        }
        $saveBody((int)$page['id'], mwp_swap_pdf((string)$page['body_html'], $old, $res['url']));
        $swapped++;
        echo "  link -> '{$pdf['slug']}' now points at {$res['url']}\n";
    }

    // --- Images: import + embed ---
    echo "\nImages:\n";
    foreach ($map['images'] as $slug => $spec) {
        $page = $loadBody($slug);
        if ($page === null) { echo "  (page '{$slug}' not found; skipped)\n"; continue; }
        $body = (string)$page['body_html'];
        $figures = '';
        foreach ($spec['items'] as $img) {
            $res = $resolve($img['file'], $img['name'], $img['alt']);
            if ($res === null) continue;
            if (strpos($body, $res['url']) !== false) continue; // already embedded
            $figures .= mwp_figure($res['url'], $img['alt']);
        }
        if ($figures === '') { echo "  ({$slug}: nothing new to embed)\n"; continue; }
        if ($spec['anchor'] === null) {
            $saveBody((int)$page['id'], mwp_prepend($body, $figures));
            $embedded++;
            echo "  embed   {$slug} (prepended)\n";
        } else {
            [$new, $did] = mwp_embed_at_anchor($body, $spec['anchor'], $figures);
            if ($did) {
                $saveBody((int)$page['id'], $new);
                $embedded++;
                echo "  embed   {$slug} (at anchor)\n";
            } else {
                echo "  NOTE    {$slug}: anchor gone — image is in the Media Library; place it manually.\n";
            }
        }
    }

    // --- Import-only assets ---
    echo "\nImport-only (Media Library, not placed on a page):\n";
    foreach ($map['import'] as $a) {
        $resolve($a['file'], $a['name'], $a['alt']);
    }

    echo "\nDone. imported={$imported}, reused={$reused}, failed={$failed}, "
       . "pdf-links-swapped={$swapped}, image-blocks-embedded={$embedded}.\n";
    echo "Reminder: the Give page still links the old Legacy-Giving-Guide.pdf — "
       . "point it at your manual upload (Media Library → Copy link).\n";
    if ($failed > 0) {
        echo "Some downloads failed (run again while settleumc.com is still live, before cutover).\n";
    }
    return 0;
}

// Run unless included by the test harness.
if (empty($GLOBALS['__MWP_TEST'])) {
    exit(mwp_main($argv));
}
