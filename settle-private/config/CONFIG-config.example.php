<?php
declare(strict_types=1);

/**
 * Settle Memorial UMC — example configuration.
 *
 * Copy this file to config.php and fill in real values. config.php is
 * gitignored; this example file is committed.
 *
 * NOTE TO REVIEWER:
 *   This file is a PROPOSAL showing where the new `features` array fits.
 *   Steve — please send me the current contents of config.example.php so
 *   I can produce a true full-file replacement that preserves everything
 *   already in there (DB credentials block, app name, paths, session
 *   config, etc.). Do not save this version over the existing file yet.
 */

return [

    // ---------------------------------------------------------------
    // Database connection
    // ---------------------------------------------------------------
    'db' => [
        'host'     => 'localhost',
        'name'     => 'settleumc',
        'user'     => 'settleumc_app',
        'password' => 'CHANGE_ME',
        'charset'  => 'utf8mb4',
    ],

    // ---------------------------------------------------------------
    // App identity
    // ---------------------------------------------------------------
    'app_name' => 'Settle Memorial UMC',
    'base_url' => 'https://settlemem.org',
    'debug'    => false,

    // ---------------------------------------------------------------
    // Paths
    // ---------------------------------------------------------------
    'paths' => [
        'uploads' => __DIR__ . '/../../public_html/Settle/uploads',
        'logs'    => __DIR__ . '/../storage/logs',
    ],

    // ---------------------------------------------------------------
    // Feature flags  (see PROJECT_HANDOFF.md §14.4)
    //
    // For Settle, every flag is true. Each flag turns off:
    //   - Route registration (disabled features return a clean 404)
    //   - Sidebar nav entries (admins don't see disabled features)
    //   - Menu URL picker entries (no broken links possible)
    //
    // Flipping a flag is a deliberate deploy-time decision, not an
    // admin-UI toggle, because enablement is policy not configuration.
    //
    // To disable a feature at a per-site deployment, set its key to
    // false. Tables stay in the schema regardless — enabling later
    // is a config flip, not a migration.
    // ---------------------------------------------------------------
    'features' => [
        'pages'     => true,  // Static informational pages CRUD
        'media'     => true,  // Media library (dependency of slideshow/staff/blog)
        'slideshow' => true,  // Homepage rotating slideshow
        'staff'     => true,  // Staff directory + public /staff page
        'prayer'    => true,  // Public prayer request form + admin inbox
        'contact'   => true,  // Public contact form + admin inbox
        'menu'      => true,  // Data-driven public navigation (roadmap #1.5)
        'blog'      => true,  // Multi-author blog posts (roadmap #3, not yet built)
        'calendar'  => true,  // Google Calendar integration (roadmap #2, not yet built)
    ],

];
