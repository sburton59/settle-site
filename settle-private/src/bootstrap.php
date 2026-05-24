<?php
declare(strict_types=1);

/**
 * Bootstrap — loaded once per request by public_html/Settle/index.php.
 *
 * Sets up error handling, secure session defaults, the autoloader,
 * the database connection, and makes config globally accessible.
 */

// Error handling: log, never display
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/../storage/logs/php-error.log');

// Secure session defaults
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
if (!empty($_SERVER['HTTPS']) || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https') {
    ini_set('session.cookie_secure', '1');
}
session_name('settle_admin');
session_start();

// Load config
$config = require __DIR__ . '/../config/config.php';

// PSR-4-ish autoloader for the Settle\ namespace
spl_autoload_register(function (string $class): void {
    $prefix = 'Settle\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) return;
    $relative = substr($class, strlen($prefix));
    $file = __DIR__ . '/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($file)) require $file;
});

// Database connection
Settle\Database::init($config['db']);

// Stash config for anything that needs it later (avoiding a DI container for now)
$GLOBALS['settle_config'] = $config;

// Global timezone — adjust if needed
date_default_timezone_set('America/Chicago');