<?php
declare(strict_types=1);

/**
 * migrate-wp-images.php — one-time image import for the Slideshow,
 * Staff portraits, and section backgrounds (image pass, v3.2).
 *
 * Sibling to bin/migrate-wp-assets.php (which handled page-body images
 * and PDFs). This one fills the admin SURFACES that don't live in page
 * bodies. It downloads each image the old WordPress site still serves at
 * settleumc.com/wp-content/uploads/..., stores it in the Media Library
 * exactly the way an admin upload would, and wires it up:
 *
 *   - Slideshow (21): each image is imported and a slideshow_slides row
 *     is created for it (in source order, no caption, active).
 *   - Staff portraits (9): each image is imported and attached to the
 *     matching staff row's photo_media_id. Jeff Keeley and Lori Roach
 *     have no live photo and are skipped (they fall back to the
 *     silhouette placeholder on the public staff page).
 *   - Section backgrounds (3): imported to the Media Library only — they
 *     have no rendered home in the current home page; they are staged
 *     for the #10b home-page redesign.
 *
 *   php settle-private/bin/migrate-wp-images.php
 *   php settle-private/bin/migrate-wp-images.php /home/USER/public_html/Settle/uploads
 *
 * RUN ORDER: run sql/seed_staff.sql FIRST (it creates the 11 staff rows
 * this script attaches portraits to), THEN this script. Both must run
 * BEFORE DNS cutover — the source URLs only exist while WordPress is
 * still live at settleumc.com.
 *
 * Idempotent / safe to re-run:
 *   - An asset whose Media row already exists (matched on original_name)
 *     is NOT re-downloaded; the existing row is reused.
 *   - A slideshow image is NOT re-added if a slideshow_slides row already
 *     references its media row.
 *   - A portrait is NOT re-attached if the staff row already has a photo
 *     (guarded UPDATE: ... WHERE full_name = ? AND photo_media_id IS NULL),
 *     so a photo you set by hand is never clobbered.
 *
 * Best-effort, mirroring the project's external-call philosophy: a failed
 * download is logged and skipped; it never aborts the run or half-writes a
 * row.
 *
 * Like bin/migrate-wp-assets.php, this can't call \Settle\Upload::handle()
 * (HTTP-upload-only) — it reuses the public surface (Upload::makeThumbnail()
 * / Upload::thumbPath() for the 600px thumbnail, Model\Media::create() for
 * the row) and re-implements only the >2000px down-scale, mirroring the
 * private Upload::maybeResize() GD calls and quality constants below
 * (kept in sync deliberately — see PROJECT_HANDOFF §13.18).
 *
 * Exit codes: 0 = ran (per-asset summary printed), 1 = setup failure.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("This script is CLI-only.\n");
}

// ---- mirrors of \Settle\Upload constants (kept in sync deliberately) --------
const MWI_MAX_DIMENSION = 2000;
const MWI_JPEG_QUALITY  = 85;
const MWI_PNG_COMPRESS  = 6;
const MWI_WEBP_QUALITY  = 85;
const MWI_ALLOWED = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/gif'  => 'gif',
    'image/webp' => 'webp',
];
const MWI_RASTER = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
const MWI_SRC = 'https://settleumc.com/wp-content/uploads/';

/**
 * The image map. Three buckets:
 *   slideshow — import + create a slideshow_slides row (in this order).
 *   portraits — import + attach to the named staff row's photo_media_id.
 *   import    — import to the Media Library only (no placement).
 *
 * 'file' is the exact wp-content filename to download; 'name' is the
 * original_name stored on the Media row (also the reuse-dedup key).
 */
function mwi_map(): array
{
    return [
        // Homepage hero slideshow, in live display order. Flickr-numbered
        // files carry no human-meaningful name, so alt text is generic and
        // captions are blank.
        'slideshow' => [
            ['file' => '52692832574_bcd39d1af1_k-1.jpg', 'name' => 'Slideshow-01.jpg'],
            ['file' => '54102702126_b0e0796078_k.jpg',   'name' => 'Slideshow-02.jpg'],
            ['file' => '53804792604_598eee8e19_k.jpg',   'name' => 'Slideshow-03.jpg'],
            ['file' => '53804463266_bf85c6ecf2_b.jpg',   'name' => 'Slideshow-04.jpg'],
            ['file' => '54084922262_3236d25651_k.jpg',   'name' => 'Slideshow-05.jpg'],
            ['file' => '53612595828_eeea5a425e_k.jpg',   'name' => 'Slideshow-06.jpg'],
            ['file' => '53043626316_c4a0fb9a61_b.jpg',   'name' => 'Slideshow-07.jpg'],
            ['file' => '53044112328_a7242b2745_b.jpg',   'name' => 'Slideshow-08.jpg'],
            ['file' => '53044112343_4f7d7001c3_o.jpg',   'name' => 'Slideshow-09.jpg'],
            ['file' => '53054666812_9f92406530_b.jpg',   'name' => 'Slideshow-10.jpg'],
            ['file' => '53044011160_f3c9a9b1ab_k.jpg',   'name' => 'Slideshow-11.jpg'],
            ['file' => '53004856597_ac8f2f71f5_k.jpg',   'name' => 'Slideshow-12.jpg'],
            ['file' => '52977321523_9e971bddac_k.jpg',   'name' => 'Slideshow-13.jpg'],
            ['file' => '52989194263_cfd6be0530_k.jpg',   'name' => 'Slideshow-14.jpg'],
            ['file' => '52991112484_cac07e7c90_k.jpg',   'name' => 'Slideshow-15.jpg'],
            ['file' => '52991435633_5b0d4afd0d_k.jpg',   'name' => 'Slideshow-16.jpg'],
            ['file' => '53169983927_2deed95e1b_k.jpg',   'name' => 'Slideshow-17.jpg'],
            ['file' => '53107016764_4bb834ca9a_k.jpg',   'name' => 'Slideshow-18.jpg'],
            ['file' => '53118572669_3e935d3ca8_b.jpg',   'name' => 'Slideshow-19.jpg'],
            ['file' => '54086255955_9e5aedba6e_k.jpg',   'name' => 'Slideshow-20.jpg'],
            ['file' => '54102959443_6a7d08f851_k.jpg',   'name' => 'Slideshow-21.jpg'],
        ],
        // Staff portraits → staff.full_name (must match seed_staff.sql /
        // your /admin/staff rows exactly). 'title' only feeds alt text.
        // Jeff Keeley and Lori Roach have no live photo and are absent.
        'portraits' => [
            ['staff' => 'Mark Dickinson',  'title' => 'Senior Pastor',                                  'file' => 'DSC_0300-1-768x980.jpg',            'name' => 'Mark-Dickinson.jpg'],
            ['staff' => 'Alecia Meyer',    'title' => 'Church Administrator',                           'file' => '3-768x432.jpg.webp',                'name' => 'Alecia-Meyer.webp'],
            ['staff' => 'Aimee Keith',     'title' => "Children's Ministry and Parent's Day Out Director", 'file' => 'DSC_0296-1-768x1013.jpg',         'name' => 'Aimee-Keith.jpg'],
            ['staff' => 'Kim Massey',      'title' => 'Church Accountant',                              'file' => 'Untitled-design-1-768x432.jpg.webp', 'name' => 'Kim-Massey.webp'],
            ['staff' => 'Rebecca Volk',    'title' => 'Traditional Music Director',                     'file' => 'DSC_0345-768x1027.jpg',             'name' => 'Rebecca-Volk.jpg'],
            ['staff' => 'Chris Tolliver',  'title' => 'Church Organist',                                'file' => '2-1-768x432.jpg.webp',              'name' => 'Chris-Tolliver.webp'],
            ['staff' => 'Libby Kassinger', 'title' => null,                                             'file' => 'IMG_0705-768x1024.jpeg.webp',       'name' => 'Libby-Kassinger.webp'],
            ['staff' => 'Sharee Best',     'title' => 'Preschool Director',                             'file' => 'image0-768x952.jpeg.webp',          'name' => 'Sharee-Best.webp'],
            ['staff' => 'Wesley Marcum',   'title' => 'Senior High Youth and Young Adults',             'file' => '1-768x432.jpg.webp',                'name' => 'Wesley-Marcum.webp'],
        ],
        // Section backgrounds — Library only (staged for #10b). Names are
        // from the §4 inventory; the download is best-effort and will
        // report a 404 if a name is wrong.
        'import' => [
            ['file' => 'Im-New.jpg',            'name' => 'Section-Im-New.jpg',            'alt' => "I'm New section background"],
            ['file' => 'Faith-Development.jpg', 'name' => 'Section-Faith-Development.jpg', 'alt' => 'Faith Development section background'],
            ['file' => 'Worship.jpg',           'name' => 'Section-Worship.jpg',           'alt' => 'Worship section background'],
        ],
    ];
}

// ===== PURE / GD HELPERS (mirrors of Upload; unit-tested by the harness) =====

/**
 * Down-scale an image file in place if its long edge exceeds
 * MWI_MAX_DIMENSION. Mirrors \Settle\Upload::maybeResize (private).
 * Returns [width, height] of the final file, or null on failure.
 */
function mwi_resize_in_place(string $path, string $mime): ?array
{
    $info = @getimagesize($path);
    if (!$info) {
        return null;
    }
    [$origW, $origH] = $info;
    $long = max($origW, $origH);
    if ($long <= MWI_MAX_DIMENSION) {
        return [$origW, $origH];
    }
    $scale = MWI_MAX_DIMENSION / $long;
    $newW = (int)round($origW * $scale);
    $newH = (int)round($origH * $scale);
    $src = mwi_load($path, $mime);
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
    $saved = mwi_save($dst, $path, $mime);
    imagedestroy($src);
    imagedestroy($dst);
    return $saved ? [$newW, $newH] : null;
}

function mwi_load(string $path, string $mime)
{
    switch ($mime) {
        case 'image/jpeg': return @imagecreatefromjpeg($path);
        case 'image/png':  return @imagecreatefrompng($path);
        case 'image/gif':  return @imagecreatefromgif($path);
        case 'image/webp': return @imagecreatefromwebp($path);
    }
    return null;
}

function mwi_save($image, string $path, string $mime): bool
{
    switch ($mime) {
        case 'image/jpeg': return imagejpeg($image, $path, MWI_JPEG_QUALITY);
        case 'image/png':  return imagepng($image, $path, MWI_PNG_COMPRESS);
        case 'image/gif':  return imagegif($image, $path);
        case 'image/webp': return imagewebp($image, $path, MWI_WEBP_QUALITY);
    }
    return false;
}

// ===== NETWORK ===============================================================

/** Download a URL to a temp file. Returns the temp path, or null on failure. */
function mwi_download(string $url, string $tmpDir): ?string
{
    $tmp = tempnam($tmpDir, 'mwi_');
    if ($tmp === false) {
        return null;
    }
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

function mwi_main(array $argv): int
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

    // uploaded_by: first admin, else lowest user id.
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

    echo "WP image migration — uploads: {$uploadRoot}  (uploaded_by user #{$uid})\n";
    $map = mwi_map();
    $tmpDir = sys_get_temp_dir();
    $imported = 0; $reused = 0; $failed = 0; $slides = 0; $attached = 0;

    // Resolve one asset to a Media row + local /uploads URL (import or
    // reuse). Returns ['id'=>mediaId,'url'=>..,'rel'=>..] or null on failure.
    $resolve = function (string $file, string $name, ?string $alt) use ($uploadRoot, $uid, $tmpDir, &$imported, &$reused, &$failed): ?array {
        $existing = \Settle\Database::query(
            'SELECT id, filename FROM media WHERE original_name = :n LIMIT 1', [':n' => $name]
        )->fetch();
        if ($existing) {
            $reused++;
            echo "  reuse   {$name}\n";
            return [
                'id'  => (int)$existing['id'],
                'url' => '/uploads/' . ltrim((string)$existing['filename'], '/'),
                'rel' => (string)$existing['filename'],
            ];
        }
        $tmp = mwi_download(MWI_SRC . $file, $tmpDir);
        if ($tmp === null) {
            $failed++;
            echo "  FAILED  download {$file}\n";
            return null;
        }
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($tmp) ?: '';
        if (!isset(MWI_ALLOWED[$mime])) {
            $failed++; @unlink($tmp);
            echo "  FAILED  {$file} — type '{$mime}' not allowed\n";
            return null;
        }
        $ext = MWI_ALLOWED[$mime];
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

        $dims = mwi_resize_in_place($dest, $mime);
        if ($dims === null) {
            $failed++; @unlink($dest);
            echo "  FAILED  {$file} — image unreadable\n";
            return null;
        }
        [$width, $height] = $dims;
        $thumb = \Settle\Upload::makeThumbnail($rel, $uploadRoot); // real shared code

        $mediaId = \Settle\Model\Media::create([
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
        return ['id' => $mediaId, 'url' => '/uploads/' . $rel, 'rel' => $rel];
    };

    // --- Slideshow: import + create a slide row (dedup on media_id) ---
    echo "\nSlideshow:\n";
    foreach ($map['slideshow'] as $img) {
        $res = $resolve($img['file'], $img['name'], 'Settle Memorial church life');
        if ($res === null) continue;
        $already = (int)\Settle\Database::query(
            'SELECT COUNT(*) FROM slideshow_slides WHERE media_id = :mid', [':mid' => $res['id']]
        )->fetchColumn();
        if ($already > 0) {
            echo "  (slide already exists for {$img['name']})\n";
            continue;
        }
        \Settle\Model\Slideshow::create([
            'media_id'  => $res['id'],
            'caption'   => '',
            'link_url'  => '',
            'is_active' => 1,
        ]);
        $slides++;
        echo "  slide   {$img['name']} added\n";
    }

    // --- Staff portraits: import + guarded attach (never clobbers a photo) ---
    echo "\nStaff portraits:\n";
    foreach ($map['portraits'] as $p) {
        $row = \Settle\Database::query(
            'SELECT id, photo_media_id FROM staff WHERE full_name = :n LIMIT 1', [':n' => $p['staff']]
        )->fetch();
        if (!$row) {
            echo "  NOTE    no staff row '{$p['staff']}' — run sql/seed_staff.sql first; skipped\n";
            continue;
        }
        if ($row['photo_media_id'] !== null) {
            echo "  (\"{$p['staff']}\" already has a photo; skipped)\n";
            continue;
        }
        $alt = $p['title'] !== null ? $p['staff'] . ', ' . $p['title'] : $p['staff'];
        $res = $resolve($p['file'], $p['name'], $alt);
        if ($res === null) continue;
        // Guarded: only attach while still unset, so a hand-set photo wins.
        \Settle\Database::query(
            'UPDATE staff SET photo_media_id = :mid WHERE full_name = :n AND photo_media_id IS NULL',
            [':mid' => $res['id'], ':n' => $p['staff']]
        );
        $attached++;
        echo "  attach  {$p['staff']} <- {$p['name']}\n";
    }

    // --- Section backgrounds: Library only (staged for #10b) ---
    echo "\nSection backgrounds (Media Library only, for #10b):\n";
    foreach ($map['import'] as $a) {
        $resolve($a['file'], $a['name'], $a['alt']);
    }

    echo "\nDone. imported={$imported}, reused={$reused}, failed={$failed}, "
       . "slides-added={$slides}, portraits-attached={$attached}.\n";
    echo "Reminder: add staff EMAILS in /admin/staff (seeded NULL — the live "
       . "site obfuscates them).\n";
    if ($failed > 0) {
        echo "Some downloads failed (run again while settleumc.com is still live, before cutover).\n";
    }
    return 0;
}

// Run unless included by the test harness.
if (empty($GLOBALS['__MWI_TEST'])) {
    exit(mwi_main($argv));
}
