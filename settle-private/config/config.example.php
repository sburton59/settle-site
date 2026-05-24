<?php
/**
 * Settle Memorial UMC — configuration template.
 *
 * SETUP:
 *   1. Copy this file to config.php (in the same directory).
 *   2. Fill in the real database credentials below.
 *   3. Set permissions: chmod 0640 config.php
 *
 * The real config.php is gitignored. NEVER commit it.
 */
return [
    'app' => [
        'name'     => 'Settle Memorial UMC',
        'base_url' => 'https://settleumc.com',
        'debug'    => false,
    ],
    'db' => [
        'host'    => '127.0.0.1',
        'port'    => 3306,
        'name'    => 'REPLACE_WITH_DB_NAME',
        'user'    => 'REPLACE_WITH_DB_USER',
        'pass'    => 'REPLACE_WITH_DB_PASSWORD',
        'charset' => 'utf8mb4',
    ],
    'session' => [
        'lifetime_default'  => 4 * 3600,
        'lifetime_remember' => 30 * 24 * 3600,
    ],
];