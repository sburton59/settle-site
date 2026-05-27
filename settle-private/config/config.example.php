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

    /*
     * Feature flags  (see PROJECT_HANDOFF.md §14.4)
     *
     * For Settle, every flag is true. Each flag turns off:
     *   - Route registration (disabled features return a clean 404)
     *   - Sidebar nav entries (admins do not see disabled features)
     *   - Menu URL picker entries (admins cannot link to a disabled URL)
     *
     * Flipping a flag is a deliberate deploy-time decision, not an
     * admin-UI toggle, because enablement is policy not configuration
     * (e.g. exposing finances is a board decision, not a settings click).
     *
     * Tables stay in the schema regardless of enablement. Disk is cheap;
     * enabling later is a config flip, not a migration.
     *
     * Unknown keys default to TRUE (fail-open) so that introducing a new
     * feature does not silently break any deployment whose config has
     * not been updated yet.
     */
    'features' => [
        'pages'     => true,  // static informational pages CRUD
        'media'     => true,  // media library (dependency of slideshow, staff, blog)
        'slideshow' => true,  // homepage rotating slideshow
        'staff'     => true,  // staff directory and public /staff page
        'prayer'    => true,  // public prayer request form and admin inbox
        'contact'   => true,  // public contact form and admin inbox
        'menu'      => true,  // data-driven public navigation (roadmap #1.5)
        'blog'      => true,  // multi-author blog posts (roadmap #3, not yet built)
        'calendar'  => true,  // Google Calendar integration (roadmap #2, not yet built)
    ],
];
