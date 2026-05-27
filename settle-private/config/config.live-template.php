<?php
/**
 * Settle Memorial UMC — configuration.
 *
 * DO NOT commit this file with real credentials to source control.
 * On production, set permissions to 0640 and owner to the web user.
 */
return [
    'app' => [
        'name'     => 'Settle Memorial UMC',
        'base_url' => 'https://settlemem.org',
        'debug'    => false,
    ],
    'db' => [
        'host'    => '127.0.0.1',
        'port'    => 3306,
        'name'    => 'infowebs_settle',       // database name created in cPanel
        'user'    => 'infowebs_settle-root',  // cPanel-prefixed username
        'pass'    => 'PASTE_REAL_PASSWORD_HERE',
        'charset' => 'utf8mb4',
    ],
    'session' => [
        'lifetime_default'  => 4 * 3600,          // 4 hours
        'lifetime_remember' => 30 * 24 * 3600,    // 30 days
    ],

    /*
     * Feature flags  (see PROJECT_HANDOFF.md §14.4)
     * For Settle, every flag is true. Plumbing exists so the eventual
     * multi-church split is mechanical, not a retrofit.
     */
    'features' => [
        'pages'     => true,
        'media'     => true,
        'slideshow' => true,
        'staff'     => true,
        'prayer'    => true,
        'contact'   => true,
        'menu'      => true,
        'blog'      => true,
        'calendar'  => true,
    ],
];
