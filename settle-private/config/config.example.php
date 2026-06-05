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
     * Email — authenticated SMTP through a cPanel mailbox.
     *
     * The site sends transactional notifications (contact-form forwards
     * and prayer-team alerts) by logging into a real mailbox over SMTP,
     * so mail goes out domain-aligned (SPF/DKIM) and actually reaches
     * inboxes — unlike anonymous PHP mail() from a shared-hosting process.
     *
     * SETUP (cPanel):
     *   1. Email Accounts → create the mailbox in 'username' below
     *      (e.g. noreply@yourdomain) with a strong password.
     *   2. Email Deliverability → confirm SPF + DKIM exist for the domain
     *      (cPanel auto-generates them when it manages the domain's DNS;
     *      if DNS lives at a registrar/Cloudflare, copy the records over).
     *   3. Put the real mailbox password in 'password' below — in
     *      config.php only, NEVER in config.example.php and NEVER in git.
     *
     * PORT / ENCRYPTION:
     *   465 + 'ssl'  → implicit TLS  (recommended; simplest to get right)
     *   587 + 'tls'  → STARTTLS
     *   (Port 25 is normally blocked on shared hosting — don't use it.)
     *
     * from_email / from_name are what recipients see. Keep from_email on
     * the SAME domain as the mailbox so DKIM stays aligned. At DNS
     * cutover, point host/username/from_email at the live domain — that's
     * an edit to config.php on the server, not a code deploy.
     *
     * Set 'enabled' => false to disable all outbound mail (e.g. a dev box
     * with no mailbox yet). The contact + prayer forms still work and
     * still save to the admin inbox — they just don't send.
     */
    'mail' => [
        'enabled'    => true,
        'host'       => 'mail.settlemem.org',          // cPanel mail hostname
        'port'       => 465,                            // 465 (ssl) or 587 (tls)
        'encryption' => 'ssl',                          // 'ssl' for 465, 'tls' for 587
        'username'   => 'noreply@settlemem.org',        // full mailbox address
        'password'   => 'REPLACE_WITH_MAILBOX_PASSWORD',
        'from_email' => 'noreply@settlemem.org',        // keep on the mailbox's domain
        'from_name'  => 'Settle Memorial UMC',
        'timeout'    => 15,                             // socket timeout, seconds
    ],

    /*
     * Google Calendar  (roadmap #2; see PROJECT_HANDOFF.md §3.3)
     *
     * The site mirrors a PUBLIC Google Calendar into the local
     * calendar_events_cache table and renders the public /calendar page
     * and homepage widget from that cache — never directly from Google,
     * so a Google outage can never blank the page.
     *
     * AUTH: an API key against a PUBLIC calendar, read-only. No OAuth, no
     * service account. The api_key is a SECRET — it lives here in
     * config.php (gitignored, 0640) only, NEVER in config.example.php and
     * NEVER in git. It is only ever sent server-to-Google.
     *
     * SETUP:
     *   1. In Google Calendar, make the church calendar PUBLIC
     *      (Settings → Access permissions → "Make available to public").
     *   2. Copy its Calendar ID (Settings → Integrate calendar → Calendar ID),
     *      e.g. abc123@group.calendar.google.com — put it in 'calendar_id'.
     *   3. In Google Cloud Console, create an API key restricted to the
     *      Google Calendar API (and ideally to your server's IP). Put it
     *      in 'api_key' below.
     *   4. Run a sync:  php settle-private/bin/calendar-sync.php
     *   5. Schedule cron (every 15 min):
     *      0,15,30,45 * * * * php /home/USER/settle-site-repo/settle-private/bin/calendar-sync.php >/dev/null 2>&1
     *
     * LAUNCH SWAP: to move from a dummy/dev calendar to the real church
     * calendar, change 'calendar_id' (and 'api_key' if different) here on
     * the server, then flush the cache once:
     *      DELETE FROM calendar_events_cache;
     * and run calendar-sync.php. That is a config edit + one query, not a
     * code deploy. Because both dev and prod calendars are public + API
     * key, nothing else changes.
     *
     * FEATURED EVENTS: staff mark an event for the homepage by putting the
     * 'featured_keyword' (default "[featured]", case-insensitive) anywhere
     * in the event's DESCRIPTION inside Google Calendar. The keyword is
     * detected at sync and stripped from the displayed text.
     *
     * Set 'enabled' => false to disable syncing entirely (the /calendar
     * page still renders from whatever is already cached).
     */
    'google_calendar' => [
        'enabled'            => true,
        'api_key'            => 'REPLACE_WITH_GOOGLE_API_KEY',     // SECRET — config.php only
        'calendar_id'        => 'REPLACE_WITH_PUBLIC_CALENDAR_ID', // e.g. abc123@group.calendar.google.com
        'timezone'           => 'America/Chicago',                 // render events in the church's local tz
        'featured_keyword'   => '[featured]',                      // case-insensitive, matched in the description
        'hidden_keyword'     => '[hide]',                          // case-insensitive; [hide] in the description removes the event from the site
        'window_past_days'   => 1,                                 // keep just-passed / in-progress events
        'window_future_days' => 365,                               // how far ahead to cache
        'cache_ttl'          => 900,                               // seconds; only used by the lazy fallback
        'lazy_sync'          => false,                             // cron is used; flip true only if cron is unavailable
        'http_timeout'       => 10,                                // seconds, per request to Google
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
