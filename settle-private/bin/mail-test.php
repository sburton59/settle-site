<?php
declare(strict_types=1);

/**
 * SMTP smoke test — run from the server shell, NOT as a web request.
 *
 *   php settle-private/bin/mail-test.php you@example.com
 *
 * Loads config.php, wires a minimal autoloader for the Settle\ namespace,
 * and sends one plain-text email through \Settle\Mailer using the live
 * 'mail' config. Prints the result.
 *
 * Use this to confirm the cPanel mailbox / SMTP settings work before
 * trusting the contact + prayer notification paths. It does NOT touch
 * the database.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("This script is CLI-only.\n");
}

$to = $argv[1] ?? '';
if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "Usage: php mail-test.php <recipient@example.com>\n");
    exit(1);
}

$root       = dirname(__DIR__);              // settle-private/
$configPath = $root . '/config/config.php';
if (!is_file($configPath)) {
    fwrite(STDERR, "config.php not found at {$configPath}\n");
    exit(1);
}

$GLOBALS['settle_config'] = require $configPath;

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

$subject = 'Settle SMTP test · ' . date('Y-m-d H:i:s');
$body    = "This is a test message from settle-private/bin/mail-test.php.\n\n"
         . "If you received it, authenticated SMTP is working.\n";

echo "Sending test email to {$to} ...\n";
$ok = \Settle\Mailer::send($to, $subject, $body);

if ($ok) {
    echo "OK — Mailer::send() returned true. Check the inbox (and spam folder).\n";
    exit(0);
}

echo "FAILED — Mailer::send() returned false.\n";
echo "Check settle-private/storage/logs/php-error.log for the reason.\n";
echo "(Common causes: wrong host/port, bad mailbox password, 'enabled' => false,\n";
echo " or the host blocking outbound SMTP egress.)\n";
exit(2);
