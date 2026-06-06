<?php
declare(strict_types=1);

/**
 * Thumbnail backfill — generate the #9 thumbnail variant for images that were
 * uploaded before thumbnails existed. Run once from the server shell after
 * applying migration 0005; safe to re-run (idempotent — only touches rows that
 * still have a NULL thumbnail_filename).
 *
 *   php settle-private/bin/thumbnail-backfill.php
 *
 * Optionally pass an explicit uploads root as the first argument if the default
 * (../public_html/Settle/uploads relative to settle-private/) is wrong for your
 * layout:
 *
 *   php settle-private/bin/thumbnail-backfill.php /home/USER/public_html/Settle/uploads
 *
 * For each image row without a thumbnail it regenerates one with
 * \Settle\Upload::makeThumbnail() (the exact same logic the uploader uses) and
 * records the path. Images already <= the thumbnail size reuse themselves as
 * their own thumbnail (no second file is written), which is still recorded so
 * the row is not reconsidered on a later run. PDFs and non-image rows are
 * skipped by the query.
 *
 * Exit codes: 0 = completed (with a per-row summary), 1 = setup failure.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("This script is CLI-only.\n");
}

$root       = dirname(__DIR__);              // settle-private/
$configPath = $root . '/config/config.php';
if (!is_file($configPath)) {
    fwrite(STDERR, "config.php not found at {$configPath}\n");
    exit(1);
}

$config = require $configPath;
$GLOBALS['settle_config'] = $config;

spl_autoload_register(static function (string $class) use ($root): void {
    $prefix = 'Settle\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }
    $relative = substr($class, strlen($prefix));
    $file = $root . '/src/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

\Settle\Database::init($config['db']);
date_default_timezone_set('America/Chicago');

// Resolve the public uploads directory. Default mirrors MediaController:
// settle-private/ -> ../public_html/Settle/uploads. Allow an override arg.
$uploadRoot = $argv[1] ?? (dirname($root) . '/public_html/Settle/uploads');
$uploadRoot = realpath($uploadRoot) ?: $uploadRoot;
if (!is_dir($uploadRoot)) {
    fwrite(STDERR, "Uploads directory not found: {$uploadRoot}\n");
    fwrite(STDERR, "Pass the correct path as the first argument.\n");
    exit(1);
}

echo "Thumbnail backfill — uploads root: {$uploadRoot}\n";

$rows = \Settle\Model\Media::imagesWithoutThumbnail();
$total = count($rows);
echo "Images without a thumbnail: {$total}\n";

$made = 0;
$reused = 0;
$failed = 0;

foreach ($rows as $row) {
    $id       = (int)$row['id'];
    $filename = (string)$row['filename'];
    $thumb    = \Settle\Upload::makeThumbnail($filename, $uploadRoot);

    if ($thumb === null) {
        $failed++;
        echo "  [{$id}] FAILED  {$filename} (missing file or unreadable image)\n";
        continue;
    }

    \Settle\Model\Media::setThumbnail($id, $thumb);
    if ($thumb === $filename) {
        $reused++;
        echo "  [{$id}] reuse   {$filename} (already <= thumbnail size)\n";
    } else {
        $made++;
        echo "  [{$id}] thumb   {$filename} -> {$thumb}\n";
    }
}

echo "\nDone. Generated: {$made}, reused-original: {$reused}, failed: {$failed}, of {$total}.\n";
if ($failed > 0) {
    echo "Failed rows kept their NULL thumbnail and still render at full size; ";
    echo "re-run after fixing the underlying files to retry only those.\n";
}
exit(0);
