<?php
declare(strict_types=1);

/**
 * Google Calendar sync — run from cron or the server shell, NOT as a web
 * request.
 *
 *   php settle-private/bin/calendar-sync.php
 *
 * Loads config.php, wires a minimal autoloader and the database (the sync
 * writes to calendar_events_cache), and runs one \Settle\GoogleCalendar
 * sync against the configured PUBLIC calendar. Prints the result.
 *
 * Cron (every 15 minutes — adjust the path to your clone):
 *
 *   0,15,30,45 * * * * php /home/USER/settle-site-repo/settle-private/bin/calendar-sync.php >/dev/null 2>&1
 *
 * Exit codes: 0 = sync wrote N events, 2 = sync failed / not configured
 * (see settle-private/storage/logs/php-error.log for the reason). The
 * public calendar always renders from whatever is already cached, so a
 * failed run never affects the live site.
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

// The sync writes to the DB, so the connection must be live.
\Settle\Database::init($config['db']);

// Match the app's timezone handling.
date_default_timezone_set('America/Chicago');

echo 'Syncing Google Calendar at ' . date('Y-m-d H:i:s') . " ...\n";
$count = \Settle\GoogleCalendar::sync();

if ($count >= 0) {
    echo "OK — {$count} event(s) cached.\n";
    exit(0);
}

echo "FAILED — sync returned -1 (not configured, disabled, or the fetch failed).\n";
echo "Check settle-private/storage/logs/php-error.log for the reason.\n";
echo "(Common causes: api_key/calendar_id still set to REPLACE_WITH_…,\n";
echo " 'enabled' => false, the calendar not public, or blocked egress.)\n";
exit(2);
