# **Settle Memorial UMC Website Modernization — Project Handoff**

**Document version:** 2.8 **Date prepared:** June 6, 2026 **Purpose:** This document brings a new contributor (human or AI) fully up to speed on the project so work can continue without losing context.

**Changes in v2.8:** **Calendar display enhancements (roadmap #8a) shipped.** The public calendar grew three things: a **redesigned month grid**, a **list view**, and a **day view**, plus **subscribe links** and a shared formatting helper. **Month grid** is now a stack of **week rows** rendered Google-Calendar-style: **multi-day and all-day events render as horizontal bars that span the days they cover** (instead of repeating in every cell), while single-day timed events render as a compact **time + title** entry inside the day cell. An event that **crosses a week boundary** is split into **one bar per week** with a "continues" cue on the cut edge (the only no-JS approach). The whole layout is **server-computed** — a new private `PublicController::buildMonthWeeks()` splits events into bars vs. in-cell singles, clips each bar to the week, **greedily packs bars into lanes** (sorted earliest-column then longest-span), and computes a per-day **"+N More" overflow** (cap **3** own-events per cell, counting bars passing through that day against the budget). The template draws it with CSS only: each `.cal-week` is a `position:relative` grid of 7 cells, bars are absolutely positioned via inline `--col/--span/--lane` custom properties, and the week's `--lanes` count (inherited into the cells) reserves matching top padding so every cell aligns. **The old in-page chronological details list under the grid is gone** — the list/day views replace it, and the month no longer uses the legacy `.calendar-grid`/`.calendar-chip` CSS (retained, harmless). **List view** (`GET /calendar/list`) is the upcoming events (current-or-future, so in-progress multi-day events are included) in chronological order, **paginated 25/page**, reusing the blog's pagination markup. **Day view** (`GET /calendar/day/{date}`) shows everything overlapping one day with prev/next-day + back-to-month nav; an invalid date redirects to `/calendar`. Both reuse a shared **`_calendar_event_item.php`** partial (mirrors the old details-row markup), and all three views share a **`_calendar_toolbar.php`** partial (Month/List switcher + subscribe buttons). **Subscribe links** (Google `render?cid=` + `webcal://…/public/basic.ics`) are derived from `google_calendar.calendar_id` (a public calendar id is not a secret — only the API key is) and **auto-hide** when the id is blank/placeholder. A new **`\Settle\CalendarFormat`** helper centralises the formatting that was duplicated as template closures: `timeLabel()` (all-day / single / same-day range / cross-day), `clockRange()` (time-only, used by the homepage cards), `shortStart()` ("8:45a"/"9a"/"All day" for the grid), `cleanDescription()` (strips `[featured]`/`[hide]`), and `subscribeUrls()`. **Homepage event cards** now show the **start–end range** (`clockRange`) and link to **`/calendar/day/{date}`** (the old `#event-id` anchor target no longer exists). **NEW:** `src/CalendarFormat.php`, `templates/public/_calendar_toolbar.php`, `templates/public/_calendar_event_item.php`, `templates/public/calendar_list.php`, `templates/public/calendar_day.php`. **REPLACE:** `src/Model/CalendarEvent.php` (+`forRange`/`forDay`/`upcomingList`/`countUpcoming`, existing methods untouched), `src/Controller/PublicController.php` (`calendar()` rewritten + `buildMonthWeeks`/`calendarList`/`calendarDay`/`subscribeUrls`), `src/Controller/MenuController.php` (+"Calendar (list view)" in the URL registry), `templates/public/calendar.php`, `templates/public/home.php`, `public_html/Settle/index.php` (+2 routes in the calendar feature block), `assets/css/theme.css` (+"8a" section). **NO schema change, no migration, no config change** (the override-editor table from #4b is unchanged; this is pure display). Times are formatted verbatim from stored values (datetimes are already church-local from sync — no tz math). Validated by **two harnesses, all passing**: a **32-assertion** data harness (CalendarFormat cases, the four new model queries against SQLite incl. hidden/override-hide/past exclusion + `effective_featured` + pagination order, and `buildMonthWeeks` via reflection — multi-day span/lane packing, cross-week split with continues flags, all-day-single-as-bar, timed-single-in-cell, overflow budgeting with and without a bar through the day) and a **22-assertion** template render harness (bar custom-props, `--lanes`, cells wrapped in `.cal-week__cells`, shortStart entries, "+N More", list pagination state, day prev/next + back-to-month, subscribe show/hide). The roadmap is re-pointed at **#8b (dashboard enrichment)** next — unless the **#9.5 renovation follow-along** becomes time-sensitive, in which case it jumps ahead.

**v2.8 addendum — post-review refinements (same #8a delivery):** three owner-requested tweaks. (1) **List view is the default on phones** — a small progressive-enhancement `<script>` in `calendar.php` redirects narrow screens (`max-width:640px`) to `/calendar/list`; it degrades gracefully (JS off → the month grid still renders), and the toolbar **"Month" button now links to `/calendar?view=month`**, which the script treats as an explicit override so a phone user can still force the grid. (2) **List/day event images** are capped at **`50vw`** (≈ half the browser window) — `.calendar-list__image` `max-width` 100% → 50vw. (3) **Month grid:** long event **titles now wrap** (cell column widths stay fixed; the grid stretches a week's cells to equal height) instead of truncating with an ellipsis, and in-cell entries show the **start–end range** (`CalendarFormat::clockRange`) rather than just the start (the time stays on one line, only the title wraps). Touches only `templates/public/calendar.php`, `templates/public/_calendar_toolbar.php`, and `assets/css/theme.css`; re-validated (render harness now **24 assertions**, data harness unchanged at 32).

**Changes in v2.7:** **Login rate-limiting (roadmap #8) shipped** — the long-standing "5 attempts / 15 min lockout planned, not enforced" gap (§7) is closed. A new dependency-free, **fail-open** `\Settle\RateLimiter` counts timestamped attempt rows under an opaque key inside a rolling window. **Key = `sha256(ip . '|' . lower(identifier))`** (hashed for a fixed-width `CHAR(64)` index and to keep attacker-supplied identifiers / email PII out of the table), keyed on **both ip and username** so one attacker IP can't lock every account and one targeted account can't be locked from everywhere. **Threshold 5 / 15-min rolling window** (no separate "locked-until" column — access returns automatically as failures age out). API: `key()`, `tooMany($key,$max,$window)`, `hit($key,$window): int` (records + returns the in-window count), `clear($key)`. **Fail-open is load-bearing:** every method swallows + `error_log()`s its own failures and `tooMany()` returns **false** on error, so a DB hiccup can only mean "no throttling", never "no login" (mirrors `Mailer`/`AuditLog` best-effort). **Time discipline (§13.8):** the window is computed in **PHP** (`date()`, app TZ) and bound as `:since`/`:now` — never SQL `NOW()`. **Wiring (controller-level, so `Auth` stays a pure credential check):** `AuthController::doLogin()` checks `tooMany` **before** verifying (blocked requests short-circuit, so the counter doesn't run away), `clear()`s on success, and on failure calls `hit()` and audits **`auth.lockout`** *exactly once* — at the crossing (`$count === MAX_ATTEMPTS`), not on every blocked attempt. The throttle message reuses the existing `login_error` flash channel (generic, non-enumerating) so **`auth/login.php` needs no edit**. The **password-reset request form** (`PasswordResetController::doForgot`) gains a coarse **IP-only** request cap (10 / 15 min) through the same limiter; when tripped it returns the *same* generic "link on its way" notice (the throttle state must be indistinguishable from a normal submit). The #6b per-**account** re-issue cooldown is **retained** — it guards a different axis (live links per account across all IPs) and is complementary, not redundant (a deliberate divergence from the v2.6 NEXT_SESSION suggestion to retire it). Storage: a new **`login_attempts`** table (`id`, `attempt_key CHAR(64)`, `created_at`), **no foreign key** by design (attempts are pre-auth and may name a nonexistent user); `hit()` opportunistically prunes rows older than a fixed 24h retention (no cron), independent of the count window so a short-window caller can't delete a longer-window caller's rows. **NEW:** `src/RateLimiter.php`, `sql/migrations/0004_add_login_attempts.sql`. **REPLACE:** `sql/schema.sql` (same table), `src/Controller/AuthController.php`, `src/Controller/PasswordResetController.php`. No settings or config change; no new dependencies. New audit verb `auth.lockout` (entity_type `auth`, NULL entity_id, `details {scope:login}`); it surfaces in the v2.6 audit viewer with the IP attached (NULL actor → "—/anonymous"). Validated by an SQLite harness (**26 assertions, all passing**: real `RateLimiter`, key hashing, the crossing-count contract, rolling-window PHP-time semantics, key isolation, pruning, custom forgot-cap params, and fail-open with the table dropped). The roadmap is re-pointed at the **calendar display enhancements (#8a)** next, and the **§2.1 extended features are now sequenced into §10** (see "Changes to the roadmap" below).

**v2.7 addendum — limiter health indicator:** because the limiter is fail-open (a missing/broken `login_attempts` table silently disables throttling, exactly the symptom seen on first deploy when migration `0004` hadn't been run), a small observability hook was added so the degraded state surfaces instead of failing quietly. `\Settle\RateLimiter::healthy(): bool` is a cheap liveness probe (`SELECT 1 FROM login_attempts LIMIT 1`, no writes) that — *unlike* the fail-open hot path — is allowed to **report** failure. `DashboardController::index()` calls it for **admins only** (`null` for non-admins, who can't act on it and so don't see it) and the dashboard renders an amber `.flash-warning` banner when it's `false`, naming the exact fix (apply migration `0004`). REPLACE: `src/RateLimiter.php` (adds `healthy()`; all other methods untouched), `src/Controller/DashboardController.php`, `templates/admin/dashboard.php`; plus a `--warning` var + `.flash-warning` style in `assets/css/admin.css`. Validated by a 4-assertion SQLite harness (probe is false with the table dropped, true when present empty/with rows, writes nothing).

**Changes to the roadmap (v2.7):** the seven owner-added §2.1 "extended features" were folded into §10 with the agreed sequencing: **#8a** calendar display enhancements (start/stop times on cards + calendar, day view, list view, subscribe link) lands next as a self-contained extension of the now-complete calendar; **#8b** a richer dashboard "Welcome back" page is a low-risk quick win; **multi-image / drag-&-drop upload is merged into #9** (it shares the `Upload`/Media surface with thumbnails); **#9.5** the renovation follow-along (a blog category + landing page) is time-sensitive ("a few months out") and may jump the queue to land before the renovation begins; the **"more eye-catching home page" pass is folded into #10 as a design sub-pass (#10b)** building on #4c; the **searchable history page + old photos (#12b)** depends on site search (#12), thumbnails (#9), and content migration (#10), so it sits after those; and the **user help doc (#14, PDF + deep-linked HTML)** is scheduled last/near-launch so it doesn't go stale. None of these change the contractual-scope status (all five proposal features remain complete).

**Changes in v2.6:** **Audit-log viewer (roadmap #7) shipped** — the audit **writer** (`\Settle\AuditLog::record()`, wired since v1.4) finally has a read side in the admin UI. A new admin-only **`GET /admin/audit`** renders a paginated (50/page), reverse-chronological table of the `audit_log` table with optional, sticky filters: **Action** (one dropdown offering both "All `<group>.*` actions" and each specific verb), **Entity type**, **Actor**, and a **From/To date range**. Like Settings (#4) and Users (#5) it is **core admin, not a Features flag** (route gate `['auth'=>true,'role'=>'admin']` **plus** an in-code `Auth::hasRole('admin')` check → 403). It is **read-only** — no writes, and **viewing the log is itself not audited** (avoids self-referential noise; mirrors §9's "routine syncs aren't audited" stance). The read side was added to the **existing `\Settle\AuditLog`** class (keeps the audit concern in one place) rather than a new model: `query(filters,limit,offset)`, `count(filters)`, plus `distinctActions()`/`distinctEntityTypes()` for the dropdowns. `query()` **LEFT JOINs `users`** for the actor `display_name`, so the NULL-`user_id` rows (anonymous password-reset actions, and rows whose actor was later deleted via the `ON DELETE SET NULL` FK) render with a "—/anonymous" placeholder. **Security:** every filter is normalised in the controller and composed into a **fully parameterized WHERE** in the model (no user input concatenated); the Action value (exact or the `user.*` prefix form) and the Actor id are **whitelisted** against values actually present, so a crafted query string can't reach the query; the `details` JSON column is `json_decode`d then **escaped via `e()`** as a compact key→value list (never rendered as HTML); dates are validated `YYYY-MM-DD` and expanded to inclusive bounds; the page number is clamped. **`LIMIT`/`OFFSET` are cast to `(int)` and inlined** (can't be bound with emulation off — §9). The action-prefix match uses `LIKE` with its metacharacters (`% _ \`) **stripped** (and an empty-after-strip prefix skipped) — **no `ESCAPE` clause**, so the query behaves identically on production MySQL and the SQLite validation harness (the two disagree on backslash semantics inside `LIKE`). **New files:** `\Settle\Controller\AuditLogController` (index only) and `templates/admin/audit/index.php` (filter bar + table + prev/next pager). **REPLACE:** `src/AuditLog.php` (read methods added; writer untouched), `public_html/Settle/index.php` (one admin-only route after the Users block, + import), `templates/layout/admin.php` (an "Audit Log" link in the admin-only Users/Settings group). **No schema, settings, or config change; no new dependencies.** One carried subtlety: `record()` stamps `created_at` with SQL `NOW()` (DB clock), unlike the app's PHP-bound `:now` (§13.8); the viewer **displays times verbatim** and labels them "as recorded (server clock)" rather than converting — to be revisited once the live log is observed (owner note). New **§13.15** documents the cross-DB `LIKE` portability decision. Validated by an ad-hoc SQLite harness (**69 assertions, all passing**; real `AuditLog` + real controller via the real `View` extract path, incl. XSS-escaping and the 403 non-admin path). The roadmap is re-pointed at **#8 (rate-limit login attempts)** next.

**Changes in v2.5:** **Self-service password reset (roadmap #6b) shipped.** A locked-out staffer can now recover their own account without an admin: a public, always-on flow (mirroring the login routes — not feature-gated) at **`GET/POST /admin/forgot`** (request a link) and **`GET/POST /admin/reset`** (set a new password). New `\Settle\Controller\PasswordResetController` (showForgot/doForgot/showReset/doReset) plus `templates/auth/{forgot,reset}.php`; `\Settle\Model\User` grew four reset methods (`findActiveByUsernameOrEmail`, `setResetToken`, `findByValidResetToken`, `clearResetToken`); `templates/auth/login.php` gained a "Forgot your password?" link; `index.php` gained four routes. **No schema change** — `users.password_reset_token CHAR(64)` / `password_reset_expires DATETIME` already existed. **Token design:** 32 random bytes → 64-hex **raw** token emailed in the link; only **`sha256(raw)`** is stored (a DB leak can't be replayed). **TTL 15 minutes** (owner decision), checked with a **PHP-bound `:now`** (§13.8), not SQL `NOW()`. **Single-use** (token+expiry cleared on success). **No account enumeration:** `/admin/forgot` always returns the same "if that account exists…" message. **Inactive accounts can't reset** (active-only lookup, mirroring `Auth::attempt`). **Minimal anti-abuse** only — a fresh token isn't issued while an unexpired one exists (effectively a 15-min per-account cooldown); real throttling stays **#8**. Audited as **`user.password_reset_request` / `user.password_reset_complete`** (distinct from #5's admin-initiated `user.password_reset`); the actor is anonymous so these rows carry a **NULL `user_id`** by design, and the token/password are never logged. **The reset link's origin is the configured `app.base_url`, NOT the request `Host` header** (host-header poisoning defense; request host is a last-resort fallback) — no config-schema change, but `app.base_url` must reflect the current public host (it changes at DNS cutover, like the mail host). Two small deviations from the v2.4 brief, both deliberate: the login template is at `templates/auth/login.php` (the brief said `admin/login.php`, which doesn't exist), and a successful reset shows a **self-contained confirmation screen with a Sign in link** rather than a flash on the login page (which has no success channel — avoids touching `AuthController`). New **§13.14** documents the base_url / host-header decision. Validated by an ad-hoc SQLite harness (43 assertions, all passing; real `User` model + real controller; Argon2id verified). The roadmap is re-pointed at **#7 (audit-log viewer)** next.

**Changes in v2.4:** **User management (roadmap #5) shipped** — admins can create, edit, activate/deactivate, and delete staff logins from a new admin-only **`/admin/users`** UI, replacing the hand-run `seed_authors.sql` workflow for ongoing management (the seed file is still handy for first-boot bulk creation). It mirrors the Settings UI: **core admin, not a Features flag** (route gate `['role' => 'admin']` plus a defense-in-depth in-code `Auth::hasRole('admin')` check). **No schema change** — the `users` table already had every needed column. New `\Settle\Controller\UserController` and `templates/admin/users/{index,edit}.php`; `\Settle\Model\User` grew list/find/create/update/setActive/delete/uniqueness/`countActiveAdmins`/`isActive` methods; a sidebar "Users" link sits above "Settings". **Lockout rails** are enforced in-code: you can't change your own role, can't deactivate or delete your own account, and no action may leave the site with zero active admins (deactivate/demote/delete all checked). **Delete is also FK-guarded:** several tables reference `users(id)` `ON DELETE RESTRICT` (`posts.author_id`, `pages.updated_by`, `media.uploaded_by`, `calendar_event_overrides.updated_by`), so a user who has authored content can't be hard-deleted — the controller catches the `PDOException` and steers the admin to deactivation. **Deactivate is the primary "remove access" lever** (it blocks login via `Auth::attempt`'s existing `is_active` check), and **`Auth::check()` now re-checks `is_active` once per request** (memoized) so deactivating a *currently signed-in* user revokes access on their next request, not just at next login. Passwords: create requires an initial password (min 12, typed twice), edit can set a new one or leave blank to keep the current (Argon2id; never logged). New audit verbs: `user.create/update/role_change/activate/deactivate/password_reset/delete`. New **§13.13** documents the delete-vs-deactivate / FK-RESTRICT reality. The roadmap is re-pointed at **#6b (password reset)** next.

**Changes in v2.3:** The **calendar feature is now fully complete.** Two pieces shipped this session. (1) A **`[hide]` Google Calendar tag** mirroring `[featured]`: typing `[hide]` (case-insensitive; config key `google_calendar.hidden_keyword`, default `[hide]`) anywhere in an event's description removes that event from the public calendar page AND the homepage Upcoming-Events widget on the next sync. A new `is_hidden` column on `calendar_events_cache` (migration `0003_add_calendar_hidden.sql` + `schema.sql`) carries it; `\Settle\GoogleCalendar` sets it from the description; `\Settle\Model\CalendarEvent` now excludes `is_hidden = 1` from every public query (`upcoming`/`forMonth`/`hasAnyUpcoming`), alongside the existing `override.hide`. `[hide]` wins over `[featured]` (a hidden event never appears, even if also tagged featured). (2) The **admin override editor (roadmap #4b)** at `/admin/calendar` (editor+), **deliberately scoped to the two website-only fields that cannot live in a calendar tag: an override image and a public note** -- hide and feature are owned by the tags, so the editor never writes `hide`/`force_featured` (those columns remain honored at render as a manual SQL fallback and are never clobbered by a save). New `\Settle\Model\CalendarOverride` (upsert image+notes / clear / `allEventsForAdmin` including hidden events), `\Settle\Controller\CalendarOverrideController` (index/edit/save/clear; CSRF; atomic validation; audited as `calendar.override.set` / `calendar.override.clear`; stamps `updated_by`), `templates/admin/calendar/{index,edit}.php` (read-only Featured/Hidden badges; Media-Library picker reuse for the image; public-note textarea), and the **restored Features-gated sidebar link**. Also two **homepage Upcoming-Events tweaks:** the widget now lists events in **chronological order** (was featured-first, which read out of sequence; the star badge still appears, it just no longer reorders the list), and an event's **override image now fills its card as a background** (new `.event-card--image`: cover + dark gradient overlay + light text; a featured card keeps its brand left-border cue). A follow-up **hotfix** corrected the card-background URL: `media.filename` is a relative path with subdirectories (`uploads/YYYY/MM/<rand>.<ext>`), and url-encoding it turned the `/` separators into `%2F` (Apache 404s encoded slashes, so the card went solid black); the URL is now built like the slideshow (`'/uploads/' . ltrim($filename,'/')`, no encoding). New **§13.12** documents that. **No settings change.** The roadmap is re-pointed at **#12 (site search)** next (owner may swap to #5 user management).

**Changes in v2.2:** The **home-page design pass (roadmap #4c)** shipped — the second post-contract feature and the first pure design/polish pass. The home page went from **five stacked full-bleed bands to four**: the separate "Welcome" and "This Sunday" sections are merged into a single `.section--soft` band (welcome copy, then a compact "This Sunday" sub-head, then the worship cards), and the worship cards — previously a hardcoded single-column grid that **stacked even on desktop** — now flow into a **three-across row** at ≥700px, which was the single biggest scroll reduction. All home bands carry a new **home-only `.section--tight`** rhythm modifier (2.25rem block padding) so interior pages and the global `--section-pad-y` are left untouched. Repeated inline styles were **lifted from `home.php` into `theme.css` classes** (`.home-welcome`, `.home-welcome__lead`, `.section-head`/`--sub`, `.btn-row`, `.worship-times`, `.worship-card`/`__service`/`__time`, `.home-cta`, `.hero--empty__tagline`); the only inline style left in `home.php` is the unavoidable per-slide `background-image` on the slideshow. The **#4 derived-shade carry-over is now built (Option A):** `--brand-primary-dark`, `--brand-primary-darker`, and `--brand-ink-soft` are derived from the (DB-overridable) base variables via CSS `color-mix()`, with the prior static hex retained as the **legacy-fallback declaration** immediately above each — so an admin's brand-color change now shifts hovers, link colors, and dark accents coherently, while older browsers fall back to the static values. A follow-up removed `.site-footer`'s `margin-top: 4rem` **globally** — that margin sat outside the footer's dark background and rendered as a white strip above it (very visible where the home page's colored "Get in touch" CTA runs into the footer); separation now comes from the last section's bottom padding plus the footer's own top padding, tightening every page's footer by ~4rem. The worship **name-from-template / time-from-setting** split, the slideshow, the `[featured]` events widget, the `PublicView::render()` path, and the mobile/no-JS layout are all preserved. **No controller, model, route, schema, or settings changes.** Production PHP was confirmed as **8.4 (ea-php84)** per cPanel MultiPHP Manager (the §9 minimum stays 8.1+); **§12 gains a "Dev sandbox setup" note** documenting the one-line PHP install a validation session needs in its ephemeral container, and the 8.3-vs-8.4 sandbox drift. The roadmap is re-pointed at **#4b (calendar override-editor admin UI)** next.

**Changes in v2.1:** The **Settings UI + Branding section (roadmap #4)** shipped — the first **post-contract** feature, giving the owner real design control over the public site without touching the database or CSS. A new admin-only `SettingsController` + `templates/admin/settings/edit.php` expose church identity, contact, **email-notification routing** (`contact_notify_to` / `prayer_notify_to`), worship times, social/app links, homepage copy, SEO meta, and **branding** (logo/favicon/apple-icon via the existing Media Library picker, plus brand **colors**) as one sectioned form (8 sections, 30 fields, schema-driven). Persistence reuses the existing `\Settle\Settings::put()` (upsert + cache flush) — **no settings model or schema change**. Validation is server-side and **atomic** (any invalid field saves nothing and re-renders with per-field errors); writes are CSRF-protected (router-enforced), audited as the new `settings.update` verb, and the route is admin-only (route gate + in-code `Auth::hasRole('admin')` check). The form prefills each field with its **current value or its schema default** (mirroring `seed_settings.sql`) so it is meaningful on a fresh/partial DB. **Brand colors** are applied by an inline `<style>:root{…}</style>` block emitted from the public layout (after `theme.css`, before `</head>`), with each value **re-validated against `/^#[0-9a-fA-F]{6}$/` at emit time** (defense in depth — a value set via raw SQL still can't inject); blank/invalid falls back to `theme.css`. Per the approved plan (Option A), v1 overrides only the two base variables (`--brand-primary`, `--brand-ink`); derived shades waited for #4c (now built — see Changes in v2.2). Two seed rows (`brand_primary` `#9E2A2B`, `brand_ink` `#2C2C2E`, matching the theme defaults) were added to `seed_settings.sql` — a **seed, not a migration** (§9). §15's planned design note is now **built**. The roadmap was re-pointed at **#4c (home-page tightening)** next.

Two bugs were fixed alongside #4. (1) **Public Contact & Prayer pages returned a blank page**: their controllers' `renderPublic()` called `\Settle\View::render(.., 'public')` **directly**, bypassing `\Settle\PublicView` and so never injecting `$settings`/`$menu_tree`; the public layout's nav renderer then got a null `$menu_tree` and threw a `TypeError` (fatal under `display_errors=off`). Both now route through `PublicView::render()` and pass a page title. (2) **Worship service-time labels**: the `worship_*` settings hold the **time only**; the **template** supplies the service name. The footer printed bare times (no name), and a homepage de-dup misstep briefly removed the only labels. Final model: homepage `<h3>` + footer `<strong>` supply the names ("Sunday School", "Traditional Worship", "Contemporary Worship"), seed/defaults are time-only, and the Settings "Worship times" section tells the admin to enter the time only. New §13.9 documents the **`View::render` `extract($data, EXTR_SKIP)` key-collision** found during this work (passing values under the key `data` silently blanks the form).

**Changes in v2.0:** The multi-author blog (roadmap #3) shipped end-to-end — **the last contractual proposal feature is done, so all five are now complete.** Authors write/edit/publish their own posts; editors moderate all; the public sees only live ones. The build is the first real use of the `author` role tier, with **in-code ownership** enforcement (author-owns-own-post OR editor+) layered on top of the router's role gate. Posts support a featured image (`posts.featured_media_id`) and inline images via the existing Media Library + TinyMCE flow (inline images live in `body_html`; `post_media` is reserved/unused). The client opted in to **blog categories** (a curated, editor-managed list of ministry areas — Music, Youth, Children's Programs, etc.), which added two tables via migration `0002_add_post_categories.sql` (`categories`, `post_categories`, many-to-many) — the first schema migration since `0001`. Public surfaces: **`/blog`** (paginated listing), **`/blog/{slug}`** (single post), **`/blog/category/{slug}`** (category archive). A **scheduled-publishing + staff-preview** capability was added on top of the base blog: a post is "scheduled" when its status is `published` with a future `published_at` (no new enum, no migration); public visibility is gated on `published_at <= :now` where **`:now` is bound from PHP (app timezone), not SQL `NOW()`** — this is load-bearing and also fixes a timezone-skew bug found in testing where a live post failed to appear. Signed-in staff (the post's author, or any editor) can **preview** a scheduled/draft/archived post by URL with a "Preview" banner, while the public gets a 404. New audit verbs: `post.create/update/publish/schedule/unpublish/archive/delete`, `category.create/update/delete`. New §13.8 documents the bound-`:now` timezone lesson.

The roadmap (§10) was also re-sequenced at the owner's direction: after the calendar override-editor (#4b), the next two items are the **Settings UI + Branding section (#4)** and then a **home-page tightening / public-UI design pass (#4c)** — branding first. Two new items were added from a review of the live settleumc.com site (§16): a **site search** feature and a **content-migration + missing-features gap analysis** (Sermons, Watch/livestream, Give, bulletin, newsletter, employment, ministry pages).

**Changes in v1.9:** Google Calendar integration (roadmap #2) shipped end-to-end — the second-to-last contractual feature is done. The site now mirrors a **public** Google Calendar into the local `calendar_events_cache` table via an API key (read-only; no OAuth, no service account), driven by a cron CLI script (`bin/calendar-sync.php`) on a 15-minute cadence. A new dependency-free `\Settle\GoogleCalendar` service performs the fetch/normalize/upsert and is resilient to Google outages (a failed fetch leaves the existing cache intact — the page never blanks). A new read-side model `\Settle\Model\CalendarEvent` overlays website-only adjustments from `calendar_event_overrides` at render time. The public **`/calendar`** page renders a **month grid** plus a chronological details list; the **homepage** gains an **Upcoming Events** widget that surfaces events tagged `[featured]` (case-insensitive) in the Google event description. A new config block `google_calendar` holds the API key (secret) and the calendar ID, so the launch swap to the church's real calendar is a one-line config edit plus a cache flush. The **override-editor admin UI was deliberately deferred** (overrides are applied at render but authored directly in the table for now) — see §7 and §10 #4b. New §13.7 documents a recurrence of the stale-`raw`-URL trap and the clone-over-CDN workaround.

**Changes in v1.8:** Email sending (roadmap #6) shipped end-to-end — the launch-blocking notification gap was closed. A dependency-free `\Settle\Mailer` sends plain-text transactional mail over authenticated SMTP through a cPanel mailbox. The contact form forwards submissions to a configured inbox; the prayer form notifies the prayer team (private requests send a **bodyless** alert so the admin role-gate is not bypassed by email). Notification targets live in two `settings` keys (`contact_notify_to`, `prayer_notify_to`); mail transport config lives in a `mail` block in `config.php`. A CLI smoke-test (`bin/mail-test.php`) was added. The multi-church shared-core refactor was **shelved** in favor of independent per-church clones (§3.7, §14).

**Changes in v1.7:** Public theming (Phase A) and the data-driven menu system (Phase B) complete. All public templates read church identity from `settings`; all brand values live in CSS custom properties in `theme.css`. `\Settle\Features` wired through every route block and the admin sidebar. New helpers: `\Settle\Settings`, `\Settle\Features`, `\Settle\Menu`, `\Settle\PublicView`.

**Changes in v1.6.1:** Second church named **Trinity**. New site not live; public still sees WordPress at `https://settleumc.com`; launch by DNS cutover. **v1.6:** scope expanded toward shared-core (since reversed). **v1.5:** Contact Form. **v1.4:** Prayer Requests + `\Settle\AuditLog`. **v1.3:** Staff Directory + `\Settle\EmailObfuscator`, `\Settle\PhoneFormatter`. **v1.2:** Homepage Slideshow + TinyMCE. **v1.1:** Media Library; GitHub repo + symlink deploy.

---

## **1\. Executive Summary**

Settle Memorial United Methodist Church (Owensboro, Kentucky) is replacing its existing WordPress site at **settleumc.com** with a custom-built PHP/MySQL application. The motivations are explicit: WordPress's attack surface, the licensing cost of premium plugins, and the maintenance burden of a heavily customized install.

The new site is built from scratch — no framework, no WordPress, no CMS dependency — using plain PHP 8.1+ and MySQL/MariaDB. It must be (a) secure, (b) inexpensive to maintain, and (c) operable by non-technical church staff through a clean admin panel.

Current status as of handoff: **Pages CRUD, Media Library, WYSIWYG editor, Homepage Slideshow, Staff Directory, Prayer Requests, Contact Form, public theming, a data-driven menu system, outbound email notifications, Google Calendar integration, the multi-author blog (with categories + scheduled publishing), an admin Settings UI with a Branding section, AND a home-page design pass are all working end-to-end.** A staff member can log in, edit pages, manage images, manage the homepage slideshow, maintain a staff directory, triage prayer requests and contact messages, manage the public navigation, and author blog posts — all through the admin panel; an admin can additionally edit church identity, contact, notification routing, social links, homepage copy, and branding (logo/favicon and brand colors) from `/admin/settings`. New contact and prayer submissions notify staff by email. Church events are managed entirely in Google Calendar and appear automatically on the public site.

**All five contractual proposal features are complete** (standard pages/photo management, homepage slideshow, multi-author blog, Google Calendar integration, secure admin panel), and the **first two post-contract features — the Settings UI + Branding section (#4, v2.1) and the home-page design pass (#4c, v2.2) — also shipped**. The remaining roadmap is quality-of-life and hardening (calendar override-editor, user management, audit-log viewer, password reset, rate-limiting, thumbnails), two newly-added items (site search, and a settleumc.com content-migration/gap analysis), plus content migration and a pre-launch checklist. The roadmap is in §10.

The site is visually presentable for stakeholder review, email-functional, and calendar-functional. It still runs at `settlemem.org` (and `settleumc.org`) only; `settleumc.com` continues to serve the old WordPress site until DNS cutover at launch.

**Multi-church direction (unchanged since v1.8):** A second Methodist church (Trinity) expressed informal interest but has not committed. If a church later commits, it will be stood up as an **independent clone** (separate repo, server, database), not via a shared core. The v1.7 conventions that aid reuse (no hardcoded church identity, brand values in CSS variables, feature flags, data-driven menu) are kept because they are good practice and make a future clone-and-rebrand a configuration exercise. See §3.7 and §14.

**Pre-launch status:** The new Settle site is **not live.** The public continues to see the existing WordPress site at `https://settleumc.com`. Launch happens by DNS cutover when the build is ready.

---

## **2\. Original Proposal (Verbatim Recap)**

The original proposal commits the project to delivering:

* **Standard church information pages** (Home, About, Staff, Sermons, Contact, etc.) editable through a simple admin panel
* **Photo management** — a media library and a rotating homepage slideshow
* **Multi-author staff blog** — several staff members can log in and write/edit/publish posts independently
* **Google Calendar integration** — events live in a single Google Calendar; the website auto-syncs and displays the full calendar. Events tagged inside Google Calendar are featured on the homepage
* **Secure password-protected admin panel** with individual staff logins
* **No recurring software licensing fees**; church owns 100% of the code

These five capabilities are the contractual scope. **All five are now complete** — the multi-author blog shipped in v2.0, joining Google Calendar (v1.9), the slideshow/photo management, the standard pages, and the secure admin panel.

---

## **2.1 Extended Features**
New features to explore, no particular order:

* **Flesh out more useful Admin Dashboard "Welcome back" page with things like: notices of recent contacts, recent prayer requests, recent blog posts, stats of most used pages
* **Multi image upload capability under Admin/Media, maybe a drag & drop function for multiple images.
* **A user help document both as a printable PDF and as HTML pages with links from Admin functions to the appropriate section of the help page.
* **Calendar entries should show start and stop times on both the Upcoming Events cards and the full calendar. The calendar page should also have a day view, a list view and a link to subscribe to the Google Calendar (see https://settleumc.com/calendar/month/) for an idea on how the month vew should look and https://settleumc.com/calendar/list/ for how the list view show look.
* **More eye catching home page - See https://settleumc.com/
* **History page - I have 2 small books on the history of the church that I want to digital and put on the website as well as nay new historical information we come across. I want all of that to be searchable.  This would also be a place to put old pictures of the church and it's members.
* **We'll be starting a renovation project in a few months. I'll want a way the congregation can follow the work.  

## **3\. Architectural Decisions and Rationale**

### **3.1 No framework**

Plain PHP with a tiny custom router, a PDO database wrapper, and PHP-as-templates. No Laravel, no Symfony, no Composer dependencies. The total custom code is auditable by one person in an afternoon. **Both the v1.8 mailer and the v1.9 calendar sync hold this line:** the SMTP mailer is a hand-rolled ~250-line class, and the calendar sync talks to the Google Calendar API v3 with plain `curl` (stream-wrapper fallback) returning JSON — no Google PHP SDK.

### **3.2 Two-tier directory layout (private code outside public_html)**

* `public_html/Settle/` — the web-accessible folder. Front controller (`index.php`), `.htaccess`, static assets, uploads.
* `settle-private/` — sibling to `public_html/`, outside the web root. All PHP source, templates, config, logs, SQL, and CLI scripts (`bin/`).

### **3.3 Google Calendar as the single source of truth for events (IMPLEMENTED v1.9)**

Events are NOT authored in the local database. They live in Google Calendar and are pulled into the `calendar_events_cache` table. **Auth: an API key against a PUBLIC calendar, read-only** — no OAuth, no service account. The public site renders only from the cache, never directly from Google, so a Google outage can slow or skip a sync but can never blank the calendar page.

Implementation details:
* `\Settle\GoogleCalendar::sync()` GETs `events.list` with `singleEvents=true&orderBy=startTime&timeMin&timeMax`, paginates on `nextPageToken`, skips `status=cancelled`, converts timed events from their source offset into the church's local timezone (stored as naive `DATETIME`, so render needs no tz math), and handles Google's **exclusive** all-day `end.date` (subtract one day at store time to avoid the classic off-by-one).
* Upsert is `INSERT … ON DUPLICATE KEY UPDATE` keyed on the event UID. Pruning of out-of-window/removed rows happens **only after a clean full fetch**, inside a transaction — a failed fetch prunes nothing.
* Featured detection: a case-insensitive match of the configured keyword (`[featured]` by default) anywhere in the event **description** sets `is_featured`. The original description is stored verbatim; the keyword is stripped only at render.
* The calendar ID and API key live in `config.php` under `google_calendar` (not hardcoded), so dev runs against a dummy/personal public calendar and the launch swap to the real calendar is a one-line config edit plus a cache flush (§8.6).
* Sync trigger is **cron** (`bin/calendar-sync.php`, every 15 min). A lazy-on-view fallback exists in code (`GoogleCalendar::maybeLazySync()`, gated by `google_calendar.lazy_sync`) for hosts without cron; it is **off** for Settle.

### **3.4 Three-tier role model**

`admin` > `editor` > `author`. Admins manage users and settings. Editors manage all content. Authors can write their own blog posts and upload to the media library. The router enforces this with per-route `auth` and `role` middleware. Roles are hierarchical — `Auth::hasRole('editor')` returns true for editors and admins. **The blog (roadmap #3, shipped v2.0) is the first feature to exercise the `author` tier meaningfully:** authors edit/publish only their own posts, editors moderate all. Because the router's `role` middleware can only express "author or higher" — not "the author who owns *this* post, OR any editor" — per-post **ownership is enforced in-code** in `PostController` (and in `PublicController::canPreview()` for the staff preview path). This in-code-ownership pattern is the model for any future owner-scoped feature.

### **3.5 Security baseline**

* Argon2id password hashing with auto-rehashing on login
* Session-based auth with `session_regenerate_id(true)` on login
* CSRF tokens on every POST (`hash_equals`); JS endpoints use an `X-CSRF-Token` header
* Strict cookie flags (`HttpOnly`, `SameSite=Lax`, `Secure` when HTTPS)
* All DB access uses prepared statements; emulation disabled
* HTML output escaped through a template-local `e()` helper; `body_html` columns are trusted because only authenticated staff write them
* `.htaccess` blocks PHP execution in the uploads folder
* Security headers (`X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`, `Permissions-Policy`)
* **Email obfuscation:** `\Settle\EmailObfuscator` XOR-encodes addresses with a JS decoder in `admin.js`.
* **Anti-spam on public forms:** honeypot field plus session-stamped time-on-page check (3-second minimum). Prayer and contact use distinct session keys.
* **Audit logging:** `\Settle\AuditLog::record($action, $entityType, $entityId, $details)`. Called from `PrayerRequestController`, `ContactMessageController`, and `MenuController`.
* **Outbound email header-injection defense:** `\Settle\Mailer` validates every recipient/Reply-To with `FILTER_VALIDATE_EMAIL` (rejecting embedded CR/LF) and strips CR/LF from header text. Visitor-supplied free text only ever appears in the body. Subjects are built from constants.
* **Private-content email isolation:** private prayer-request text is role-gated in the admin UI; the prayer notification email omits the request text for private submissions.
* **Calendar secret handling (v1.9):** the Google API key is a secret — it lives in `config.php` only (gitignored, `0640`), never in `config.example.php`, never committed, and is only ever sent server-to-Google. The sync layer logs HTTP status codes on failure, never the request URL (which carries the key).
* **Calendar output escaping (v1.9):** all event fields (title, location, description, override notes) are escaped via `e()` on the public calendar page and homepage widget. Google event descriptions are treated as untrusted text (escaped, not rendered as HTML).
* **Blog ownership + visibility (v2.0):** per-post ownership is enforced in-code (author-owns-own OR editor+) on every admin write path *and* on the public preview path — never by route-role middleware alone. Public post visibility is gated on `status='published' AND published_at <= :now`, with `:now` bound from PHP (app timezone) so a future-dated post is hidden exactly until its scheduled time regardless of the DB's timezone. The signed-in staff **preview** of a not-yet-live post (`PublicController::post()` → `canPreview()`) re-checks the same ownership rule; anonymous visitors and non-owners get a 404, never the content. `body_html` is the one trusted column (admin-authored, rendered unescaped); every other post/category field is escaped via `e()`.
* **Settings & branding (v2.1):** the Settings UI is **admin-only** — both a route gate (`'role' => 'admin'`) and an in-code `Auth::hasRole('admin')` check in `SettingsController`. Only keys in the controller's field schema can be written (an arbitrary posted key is ignored), and saves are **atomic** (any invalid field writes nothing). All values are validated server-side: emails via `FILTER_VALIDATE_EMAIL`, external links (`url` fields) require absolute `http(s)`, Media-Library image fields (`media`) accept a root-relative `/uploads/...` path **or** absolute `http(s)` (rejecting `//host`, `javascript:`, `data:`), and brand colors against `/^#[0-9a-fA-F]{6}$/`. The **brand-color override is re-validated against the same hex pattern at emit time** in the public layout — a value placed directly in the DB still cannot break out of the `<style>` declaration or inject markup. Output is escaped via `e()`; the POST is CSRF-protected (router-enforced); saves are audited as `settings.update` (logging the changed **keys**, not values).
* **User management (v2.4):** the `/admin/users` UI is **admin-only** (route gate `['role' => 'admin']` plus an in-code `Auth::hasRole('admin')` check). Lockout rails are enforced in-code: an admin cannot change their own role, cannot deactivate or delete their own account, and no action may leave the site with zero active admins. Passwords are Argon2id-hashed and never logged; the audit log records `user.*` verbs (role changes log from/to). **Deactivation is the canonical "revoke access" action** — `Auth::attempt()` refuses an `is_active=0` row and `Auth::check()` re-verifies `is_active` once per request, so a signed-in user loses access on their next request when deactivated. Hard delete is additionally refused by the DB (`ON DELETE RESTRICT`) for any user who has authored content.
* **Self-service password reset (v2.5):** the `/admin/forgot` + `/admin/reset` flow is public and always-on (like login). Only **`sha256(raw)`** is stored (`users.password_reset_token`); the raw 256-bit token is emailed. Tokens are **single-use** and expire in **15 min**, validated against a **PHP-bound `:now`** (§13.8). The request form is **non-enumerating** (one fixed response) and **active-only** (deactivated accounts can't reset). The emailed link's origin is the **configured `app.base_url`**, never the request `Host` header (host-header poisoning defense; §13.14). A new token isn't issued while an unexpired one exists (minimal anti-abuse; real throttling is #8). The new password is Argon2id-hashed; the token and password are never logged. Audited as `user.password_reset_request` / `user.password_reset_complete` (anonymous actor → NULL `user_id`).

### **3.6 Deployment via GitHub clone + symlinks**

* A clone of the GitHub repo lives at `~/settle-site-repo/`
* The Apache document root paths are symlinks into that clone
* Deploying a change is one command: `cd ~/settle-site-repo && git pull`

Workflow notes:
* `chmod 755 ~/settle-site-repo` so Apache can traverse (cPanel clones default to 700)
* `config.php` is gitignored; created on the server after clone
* `.gitattributes` enforces LF line endings via `* text=auto eol=lf`
* Every Windows clone must have `git config core.ignorecase false` set
* cPanel "Restart PHP" clears opcache if a deploy doesn't seem to take effect
* **Cron jobs** (added v1.9) are configured in cPanel → Cron Jobs, not in the repo. The calendar sync line is documented in §8.6.

### **3.7 Multi-church readiness (conventions kept; shared-core shelved)**

Unchanged from v1.8. The shared-core/per-site refactor is **not** being built; a committed second church gets an **independent clone**. The v1.7 conventions in §9 are kept because they are good engineering and make a future clone-and-rebrand a configuration exercise. The former §14 shared-core design is retained as historical reference only.

---

## **4\. Content & Asset Inventory**

A complete inventory was extracted from the existing settleumc.com WordPress site. Key assets:

* **Brand colors:** Deep crimson red (`#9E2A2B`) for header/nav (`--brand-primary` in `theme.css`); near-black for the cross/shield (`--brand-ink`). The darker shades and `--brand-ink-soft` are now **derived** from these via `color-mix()` (v2.2) so an admin recolor stays coherent.
* **Logo:** `https://settleumc.com/wp-content/uploads/Settle-UMC-Logo.png` (in `settings.brand_logo_url`)
* **Favicon (32x32):** `https://settleumc.com/wp-content/uploads/cropped-Favicon-32x32.png`
* **Apple touch icon (180x180):** `https://settleumc.com/wp-content/uploads/cropped-Favicon-180x180.png`
* **Typography:** Cinzel (display, uppercase) + Lato (body), from Google Fonts
* **21 homepage slideshow photos** — not yet imported to the Media Library
* **3 section background images** (Im-New.jpg, Faith-Development.jpg, Worship.jpg) — not yet imported
* **10 staff portrait photos** — not yet imported
* **All page text content** — extracted as copy-ready prose
* **10-person staff directory** with titles and emails (Mark Dickinson, Alecia Meyer, Aimee Keith, Kim Massey, Rebecca Volk, Chris Tolliver, Jeff Keeley, Libby Kassinger, Lori Roach, Sharee Best, Wesley Marcum)
* **Contact info:** (270) 684-4226; P.O. Box 1756, Owensboro, KY 42302; physical address **202 E. 4th Street, Owensboro, KY 42303**
* **Social:** facebook.com/SettleMem, instagram.com/shoutatsettle, YouTube @settlememorialunitedmethod5839
* **Mobile apps:** iOS `id1639009037`, Android `com.redpixelstudios.settleumc`

All of the above are populated in the `settings` table via `seed_settings.sql`; `seed_settings_mail.sql` adds `contact_notify_to` and `prayer_notify_to`.

**Resolved data discrepancies:** Physical address **202 E. 4th**. Youth ministry: Jeff Keeley (Middle School), Wesley Marcum (Senior High + Young Adults). Cindy Palacios is not current staff.

---

## **5\. Database Schema Overview**

Seventeen tables in MySQL 8 / MariaDB 10.5+, InnoDB, utf8mb4. Full DDL is in `settle-private/sql/schema.sql`.

| Table | Purpose |
| ----- | ----- |
| `users` | Admin/editor/author logins |
| `media` | Uploaded photos (file metadata + alt text) |
| `pages` | Static informational pages |
| `posts` | Multi-author blog entries (**in use as of v2.0**) |
| `post_media` | Junction for inline post images (**reserved; unused — inline images live in `body_html`**) |
| `categories` | Curated blog categories / ministry areas (**added v2.0, migration `0002`**) |
| `post_categories` | Junction: posts ↔ categories, many-to-many (**added v2.0**) |
| `slideshow_slides` | Homepage rotating slideshow |
| `staff` | Staff directory cards |
| `calendar_events_cache` | Local cache of Google Calendar events (**in use as of v1.9**) |
| `calendar_event_overrides` | Website-only event adjustments (**applied at render since v1.9; admin authoring UI shipped v2.3 (#4b)**) |
| `settings` | Key/value site config |
| `prayer_requests` | Submissions from the prayer request form |
| `contact_messages` | Submissions from the contact form |
| `audit_log` | "Who did what when" trail |
| `menu_items` | Data-driven public navigation |
| `login_attempts` | Pre-auth failed-attempt counter for the login + reset throttle (**added v2.7, migration `0004`; no FK**) |

Foreign keys are enforced. `ON DELETE` policies: RESTRICT for owned content (e.g. `posts.author_id` → `users`), SET NULL for optional images (e.g. `posts.featured_media_id` → `media`), CASCADE for menu children and post-media junctions.

Schema migrations live in `settle-private/sql/migrations/`. Three migrations to date: `0001_add_menu_items.sql`, `0002_add_post_categories.sql` (the `categories` and `post_categories` tables for the v2.0 blog), and `0003_add_calendar_hidden.sql` (the `is_hidden` column on `calendar_events_cache` for the v2.3 `[hide]` tag). **v1.9 added no schema migration** — both calendar tables already existed; the new `google_calendar` config block is config, not schema. Fresh installs run `schema.sql`; existing databases run migrations in order.

`posts` columns of note: `id, slug` (unique), `title, excerpt` (nullable), `body_html` (trusted), `featured_media_id` (→ `media`, SET NULL), `author_id` (→ `users`, RESTRICT), `status` (enum `draft`/`published`/`archived`), `published_at` (nullable; a future value = a scheduled post — there is no separate `scheduled` status), `created_at`, `updated_at`. There is no `updated_by` column on `posts`. `categories`: `id, slug` (unique), `name, sort_order, timestamps`. `post_categories`: `(post_id, category_id)` composite PK, CASCADE both ways.

`calendar_events_cache` columns of note (confirm against `schema.sql` before editing the sync): `google_event_id` (UID), `google_calendar_id`, `title`, `description`, `location`, `starts_at`, `ends_at`, `is_all_day`, `is_featured`, `is_hidden` (the `[hide]` tag, v2.3), `raw_tags`, `html_link`, `last_synced_at` — unique key on `(google_event_id, google_calendar_id)`. `calendar_event_overrides` keys on `google_event_id` (UNIQUE) and carries `force_featured` (0/1), `hide` (0/1), `override_image_id` (→ `media`, SET NULL), `notes` (VARCHAR 500), `updated_by` (→ `users`, RESTRICT — NOT NULL), `updated_at`. Note `force_featured` is a plain boolean: the render overlay computes `effective_featured = (is_featured OR force_featured)`, so an override can **promote** an event to featured but cannot force-**un**feature a `[featured]`-tagged one (force-unfeature would need a schema change — relevant to #4b, §10).

---

## **6\. Codebase Tour**

```
public_html/Settle/                    ← Apache document root
├── index.php                          ← Front controller; routes all requests (calendar route added v1.9)
├── .htaccess                          ← URL rewrite + security headers
├── assets/
│   ├── css/admin.css                  ← Admin-only styling
│   ├── css/theme.css                  ← Public theme (brand vars in :root; derived shades color-mix v2.2; home classes v2.2)
│   ├── js/admin.js                    ← Admin UX helpers + email-obfuscation decoder
│   ├── js/slideshow.js                ← Public hero slideshow rotator
│   └── img/silhouette.svg             ← Staff photo placeholder
└── uploads/                           ← User-uploaded photos
    └── .htaccess                      ← Blocks PHP execution in uploads

settle-private/                        ← Outside web root; not URL-accessible
├── src/
│   ├── bootstrap.php                  ← Loads config, sessions, autoloader, DB
│   ├── Database.php                   ← PDO singleton wrapper
│   ├── Router.php                     ← Regex router with auth/role/CSRF middleware
│   ├── Auth.php                       ← Login, logout, role checks (Argon2id)
│   ├── Csrf.php                       ← Token generation and verification
│   ├── View.php                       ← PHP-as-template renderer with layouts
│   ├── PublicView.php                 ← Public-side wrapper; injects $settings + $menu_tree
│   ├── Upload.php                     ← Upload validation, MIME detection, image resizing (GD)
│   ├── EmailObfuscator.php            ← XOR-hex email obfuscation
│   ├── PhoneFormatter.php             ← US phone formatting helpers
│   ├── AuditLog.php                   ← Audit-log writer
│   ├── Mailer.php                     ← Authenticated-SMTP plain-text mailer (v1.8)
│   ├── GoogleCalendar.php             ← Calendar sync service (fetch/normalize/upsert/prune) (v1.9)
│   ├── Features.php                   ← Feature flag registry (reads config.php)
│   ├── Menu.php                       ← Public menu facade — renderTree()
│   ├── Settings.php                   ← Settings reader with per-request cache
│   ├── CalendarFormat.php             ← Calendar time/desc/subscribe formatting helper (v2.8)
│   ├── RateLimiter.php                ← Windowed attempt limiter; fail-open (v2.7)
│   ├── Controller/
│   │   ├── BaseController.php
│   │   ├── AuthController.php
│   │   ├── DashboardController.php
│   │   ├── PagesController.php
│   │   ├── MediaController.php
│   │   ├── SlideshowController.php
│   │   ├── StaffController.php
│   │   ├── PrayerRequestController.php
│   │   ├── ContactMessageController.php
│   │   ├── MenuController.php             ← buildUrlRegistry() already lists /calendar + /blog (gated)
│   │   ├── PostController.php             ← admin blog CRUD; in-code ownership (v2.0)
│   │   ├── CategoryController.php         ← editor+ category CRUD (v2.0)
│   │   ├── SettingsController.php          ← admin-only settings + branding (v2.1)
│   │   ├── UserController.php              ← admin-only user management (v2.4)
│   │   ├── PasswordResetController.php     ← public self-service password reset (v2.5)
│   │   ├── CalendarOverrideController.php  ← admin override editor: image + note (v2.3)
│   │   ├── AuditLogController.php          ← admin-only audit-log viewer; read-only (v2.6)
│   │   └── PublicController.php           ← calendar() + home() events (v1.9); blog()/blogCategory()/post() + canPreview() (v2.0)
│   └── Model/
│       ├── User.php
│       ├── Page.php
│       ├── Media.php
│       ├── Slideshow.php
│       ├── Staff.php
│       ├── PrayerRequest.php
│       ├── ContactMessage.php
│       ├── MenuItem.php
│       ├── CalendarEvent.php              ← read-side; overlays overrides + filters `is_hidden`/`hide` (v1.9, v2.3).
│       ├── Post.php                       ← blog post model; scheduling-aware queries (v2.0)
│       ├── Category.php                   ← blog category model (v2.0)
│       └── CalendarOverride.php           ← override write side + admin listing incl. hidden (v2.3)
├── templates/
│   ├── layout/{admin.php, auth.php, public.php}
│   ├── auth/{login,forgot,reset}.php       ← forgot/reset added v2.5
│   ├── admin/
│   │   ├── dashboard.php
│   │   ├── pages/{index,edit}.php
│   │   ├── media/{index,edit,picker}.php
│   │   ├── slideshow/{index,edit}.php
│   │   ├── staff/{index,edit}.php
│   │   ├── prayer/{index,show}.php
│   │   ├── contact/{index,show}.php
│   │   ├── posts/{index,edit}.php        ← blog post admin (v2.0)
│   │   ├── categories/{index,edit}.php   ← blog category admin (v2.0)
│   │   ├── settings/edit.php             ← admin settings + branding form (v2.1)
│   │   ├── audit/index.php               ← admin audit-log viewer: filters+table+pager (v2.6)
│   │   ├── users/{index,edit}.php        ← admin user management (v2.4)
│   │   ├── menu/{index,edit}.php
│   │   └── calendar/{index,edit}.php  ← override editor: list + image/note form (v2.3)
│   └── public/
│       ├── home.php                       ← Upcoming Events widget (v1.9); tightened layout (v2.2); chronological events + override-image card bg (v2.3)
│       ├── page.php
│       ├── staff.php
│       ├── prayer.php
│       ├── contact.php
│       ├── calendar.php                   ← month grid + details list (v1.9)
│       ├── blog.php                       ← listing + category archive (v2.0)
│       └── post.php                       ← single post; staff preview banner (v2.0)
├── bin/
│   ├── mail-test.php                  ← CLI SMTP smoke test (v1.8)
│   └── calendar-sync.php              ← CLI calendar sync for cron (v1.9)
├── config/
│   ├── config.php                     ← DB + mail + google_calendar + features (gitignored)
│   ├── config.example.php             ← Template (committed; google_calendar block added v1.9)
│   └── .gitkeep
├── storage/
│   ├── uploads/                       ← (reserved)
│   └── logs/                          ← PHP error log
└── sql/
    ├── schema.sql                     ← Full schema (all 16 tables)
    ├── seed_settings.sql              ← Settle's church-identity settings
    ├── seed_settings_mail.sql         ← Mail routing addresses (v1.8)
    ├── seed_menu.sql                  ← Settle's initial public navigation
    ├── seed_authors.sql              ← TEMPLATE: blog author accounts (v2.0; edit before running)
    └── migrations/
        ├── 0001_add_menu_items.sql
        ├── 0002_add_post_categories.sql   ← categories + post_categories (v2.0)
        ├── 0003_add_calendar_hidden.sql    ← is_hidden on calendar cache (v2.3)
        └── 0004_add_login_attempts.sql     ← login throttle table (v2.7)
```

---

## **7\. What's Working vs. What's Stub/Missing**

### **Fully working end-to-end**

* ✅ Web-server routing, PDO singleton, session-based auth, CSRF, role checks
* ✅ Admin layout shell with sidebar; prayer + contact unread badges
* ✅ Login screen with error messaging
* ✅ Dashboard placeholder
* ✅ **Pages: full CRUD** with WYSIWYG (TinyMCE 7) and Media Library picker
* ✅ **Media Library: full CRUD** — upload, browse, edit metadata, delete; auto-resize to 2000px long edge
* ✅ **Homepage Slideshow: full admin CRUD** with drag-to-reorder, plus public crossfade rotation
* ✅ **Staff Directory: full CRUD with public page** — card grid, formatted phones, obfuscated emails
* ✅ **Prayer Requests: full intake + admin inbox** — anti-spam, role-gated privacy, audit-logged, email notification (private requests bodyless)
* ✅ **Contact Form: full intake + admin inbox** — anti-spam, conditional required fields, audit-logged, email forwarding
* ✅ **Email sending** — `\Settle\Mailer` over authenticated SMTP; contact + prayer notifications; `bin/mail-test.php`
* ✅ **Google Calendar integration (v1.9)** — `\Settle\GoogleCalendar` syncs a public calendar via API key into `calendar_events_cache` on a 15-min cron; failure-resilient. `\Settle\Model\CalendarEvent` overlays `calendar_event_overrides`. Public **`/calendar`** month-grid page (prev/next nav, today highlight, event chips anchoring to a details list) + homepage **Upcoming Events** widget surfacing `[featured]`-tagged events. Route gated by `Features::enabled('calendar')`; `/calendar` already in the menu URL picker.
* ✅ **Multi-author blog (v2.0)** — admin CRUD for posts with the `author`/`editor` split and **in-code ownership**; curated, editor-managed **categories** (many-to-many); featured image + inline images via Media Library + TinyMCE; **scheduled publishing** (future `published_at`, PHP-bound `:now` visibility) with signed-in **staff preview** of not-yet-live posts. Public `/blog` (paginated), `/blog/{slug}`, `/blog/category/{slug}`. Audit-logged; gated by `Features::enabled('blog')`; `/blog` in the menu URL picker.
* ✅ **Settings UI + Branding (v2.1)** — admin-only `/admin/settings`: one schema-driven sectioned form (Identity / Contact / Email notifications / Worship times / Social & apps / Homepage / Meta / Branding) editing church identity, notification routing, social/app links, homepage copy, SEO meta, logo/favicon/apple-icon (Media Library picker), and brand **colors**. Atomic server-side validation, CSRF, audited (`settings.update`). Brand colors apply via a hex-validated inline `<style>:root{…}</style>` override in the public layout, falling back to `theme.css`. Reuses `Settings::put()`; no schema change. §15's plan is now built.
* ✅ **User management (v2.4)** — admin-only `/admin/users`: list / create / edit / activate-deactivate / delete staff logins (author/editor/admin). Argon2id passwords (set on create, resettable on edit, min 12). In-code lockout rails (no self-role-change, no self-deactivate/-delete, never zero active admins) and FK-guarded delete (falls back to "deactivate instead"). A per-request `is_active` recheck in `Auth::check()` drops a signed-in user the moment they're deactivated. Audited (`user.*`). No schema change. Supersedes manual `seed_authors.sql` for ongoing management.
* ✅ **Self-service password reset (v2.5)** — public, always-on `/admin/forgot` (request) + `/admin/reset` (set new password). `PasswordResetController` + `templates/auth/{forgot,reset}.php`; four `User` reset methods; a "Forgot your password?" link on the login screen. Hashed single-use token (`sha256(raw)`, 15-min TTL, PHP-bound `:now`), non-enumerating + active-only, link host from `app.base_url`, minimal re-issue guard, audited (`user.password_reset_request`/`_complete`). No schema change (the reset columns already existed).
* ✅ **Audit-log viewer (v2.6)** — admin-only `/admin/audit`: a paginated, reverse-chronological, **read-only** view of the `audit_log` table with sticky filters (action exact/prefix, entity type, actor, date range). Read methods on `\Settle\AuditLog` (`query`/`count`/`distinct*`, LEFT JOIN `users` for the actor; NULL actor → anonymous/system placeholder); parameterized WHERE; whitelisted filter inputs; escaped `details`; inlined `LIMIT`/`OFFSET`. New `AuditLogController` + `templates/admin/audit/index.php`; sidebar link in the admin group. Viewing is not itself audited. No schema change.
* ✅ **Home-page design pass + brand-shade coherence (v2.2)** — home consolidated from five full-bleed bands to four (welcome + "This Sunday" merged into one soft band; worship cards now a row at ≥700px, not a desktop stack; events row tightened), reduced vertical rhythm via a **home-only `.section--tight`**, repeated inline styles lifted into `theme.css` classes, and the footer's white-gap `margin-top` removed globally. Derived brand shades (`--brand-primary-dark/-darker`, `--brand-ink-soft`) now track the DB-overridable base vars via CSS `color-mix()` (the prior static hex kept as a legacy fallback), so an admin color change stays coherent across hovers/accents. CSS/template only — no controller/model/route/schema/settings changes.
* ✅ **Calendar `[hide]` tag + override editor (v2.3)** — the calendar feature is now complete. `[hide]` in a Google Calendar event description hides it site-wide (mirror of `[featured]`; `is_hidden` column, migration `0003`; `[hide]` wins over `[featured]`). `/admin/calendar` (editor+) authors a website-only override **image** (Media-Library picker) and **public note** per event; hide/feature stay tag-driven; the list shows read-only Featured/Hidden badges. `CalendarOverride` model + `CalendarOverrideController`; audited (`calendar.override.set`/`clear`); `updated_by` stamped; Features-gated sidebar link restored.
* ✅ **Homepage Upcoming Events polish (v2.3)** — the widget lists events in chronological order (no longer featured-first; the star badge remains), and an event's override image fills its card as a background (`.event-card--image`: cover + dark gradient + light text).
* ✅ **Audit logging** — used by prayer, contact, menu, post, and category controllers
* ✅ **Email obfuscation, phone formatting**
* ✅ **Public-facing site theme** — header with logo + nav, footer from `$settings`, hero slideshow, polished forms, staff grid, calendar. Brand values in CSS custom properties. Cinzel + Lato. Mobile-first; CSS-only mobile drawer.
* ✅ **Settings helper + seed**, **Features flag registry**, **PublicView helper**
* ✅ **Menu system: full data + CRUD + public render**
* ✅ **Migration runner pattern**

### **Designed but not implemented in code**

* ⏳ **Site search** (roadmap #12, new v2.0). A `/search` page over published pages + posts + staff. The live settleumc.com has a header search box; this restores parity. See §16.
* ⏳ **settleumc.com missing features / content migration** (roadmap #13, new v2.0). Sermons, Watch/livestream, Give, worship bulletin, newsletter archive, employment/news, and the deep ministry pages (I'm New, Connect → Children/Preschool/PDO/Youth/Adult/Missions). Most are Pages-CRUD content; a few are small features or external links; two need client decisions (Sermons, Newsletter). Full mapping in §16.

### **Known design considerations not yet addressed**

* No automated tests in the repo yet. **Note (v1.9):** the calendar work was validated by ad-hoc PHP test harnesses (parser, override-overlay, and template-render assertions) run during development against SQLite; these were not committed. The v2.2 home pass was likewise validated by an ad-hoc render harness (21 assertions). PHPUnit + Playwright remain the intended permanent solution.
* No automated backup strategy documented
* Image resizing on upload exists (long-edge 2000px) but no thumbnail variant generation
* ~~No rate-limiting on login~~ **DONE (v2.7, #8):** `\Settle\RateLimiter` enforces 5 attempts / 15-min rolling window on the admin login (keyed on ip+username) and a coarse IP-only cap on the reset-request form; fail-open. Its one fail-open downside — a missing/broken `login_attempts` table silently disables throttling — is now surfaced to admins via `RateLimiter::healthy()` and a dashboard warning banner (v2.7 addendum). The remaining hardening gap here is none.
* Mailer and calendar sync are synchronous; no retry/queue. Calendar sync is best-effort and bounded by `google_calendar.http_timeout`.

---

## **8\. Install & Deploy Workflow**

Hosted on cPanel shared hosting, deployed from GitHub via clone-and-symlink (§3.6).

### **8.1 First-time server install**

1. Create the database in cPanel.
2. Clone the repo via cPanel Git Version Control.
3. `chmod 755 ~/settle-site-repo`.
4. Copy `config.example.php` to `config.php`; fill in DB credentials, the `mail` block (§8.5), and the `google_calendar` block (§8.6). `chmod 0640`.
5. Import in phpMyAdmin: `schema.sql`, then `seed_settings.sql`, then `seed_settings_mail.sql`, then `seed_menu.sql`. (Fresh `schema.sql` already includes the v2.0 `categories`/`post_categories` tables; an **existing** database instead runs `sql/migrations/0002_add_post_categories.sql`.) Optionally seed blog authors after editing it: `sql/seed_authors.sql` (see §8.8).
6. Seed the first admin user (`password_hash` via `php -r`).
7. Set up symlinks so Apache reads from the clone.
8. Verify storage and upload directories are writable.
9. Point the domain at `public_html/Settle/`.
10. Visit `/admin/login`, change the seeded password.
11. Set up the calendar sync cron (§8.6) and run it once manually.

### **8.2 First-time Windows clone**

1. Clone via GitHub Desktop or `git clone`.
2. **Immediately run `git config core.ignorecase false`.** Critical — see §13.
3. Optional: configure your editor to enforce LF line endings.

### **8.3 Deploy workflow (after first install)**

1. Locally on Windows: edit, commit, push via GitHub Desktop.
2. On the server: `cd ~/settle-site-repo && git pull`.
3. If a deploy doesn't take effect, cPanel → "Restart PHP" to clear opcache.

### **8.4 Important workflow rules**

* Never edit code directly on the server.
* GitHub Desktop's "Commit to main" does not push — pushing is a separate click.
* `.gitattributes` enforces LF line endings.
* Every Windows clone must have `git config core.ignorecase false`.
* Files outside git: `config.php` and everything in `uploads/`, `storage/logs/`, `storage/uploads/`.

### **8.5 Email setup (v1.8)**

1. **cPanel → Email Accounts:** create the sending mailbox (e.g. `noreply@settlemem.org`) with a strong password.
2. **cPanel → Email Deliverability:** confirm SPF and DKIM exist for the domain.
3. **In `config.php`,** fill the `mail` block: `host`, `port` (465 ssl / 587 tls), `encryption`, `username` (full mailbox), `password`, `from_email` (keep on the mailbox's domain for DKIM), `from_name`. `enabled => false` disables all outbound mail without breaking the forms.
4. **Set routing targets:** edit `contact_notify_to` and `prayer_notify_to` in `settings`.
5. **Verify:** `php settle-private/bin/mail-test.php you@example.com` from the server shell.
6. **At DNS cutover:** update `mail.host`/`username`/`from_email` to the live domain in `config.php`.

### **8.6 Calendar setup (NEW v1.9)**

1. **Make the church Google Calendar PUBLIC:** Google Calendar → the calendar's Settings → *Access permissions* → "Make available to public" (see only all event details).
2. **Copy the Calendar ID:** same Settings page → *Integrate calendar* → *Calendar ID* (looks like `…@group.calendar.google.com`).
3. **Create an API key:** Google Cloud Console → enable the *Google Calendar API* → create an API key → **restrict it to the Google Calendar API** (and, if possible, to the server's IP). Treat the key as a secret.
4. **Fill the `google_calendar` block in `config.php`** (NOT `config.example.php`):
   * `api_key` → the restricted key
   * `calendar_id` → the public calendar ID
   * `enabled => true`, `timezone => 'America/Chicago'`, `featured_keyword => '[featured]'`, plus the window/cache/timeout defaults shipped in `config.example.php`.
5. **Run a sync manually:** `php settle-private/bin/calendar-sync.php` → expect `OK — N event(s) cached.` On failure, read `settle-private/storage/logs/php-error.log`.
6. **Confirm rows:** `SELECT google_event_id, title, starts_at, is_featured FROM calendar_events_cache ORDER BY starts_at;`
7. **Schedule cron** (cPanel → Cron Jobs; every 15 minutes; adjust the path):
   `0,15,30,45 * * * * php /home/USER/settle-site-repo/settle-private/bin/calendar-sync.php >/dev/null 2>&1`
8. **Featured on homepage:** staff add `[featured]` (case-insensitive) anywhere in an event's **description** in Google Calendar; it surfaces on the homepage widget on the next sync and the keyword is hidden from visitors.
8a. **Hide from the website (v2.3):** staff add `[hide]` (case-insensitive; config `google_calendar.hidden_keyword`) anywhere in an event's description to drop it from the public calendar and homepage on the next sync. `[hide]` wins over `[featured]`. No `config.php` edit is needed — the code defaults the keyword to `[hide]`.
9. **Optional — add Calendar to the public nav:** Admin → Menu → add an item; the URL picker lists "Calendar" (`/calendar`).

### **8.7 Launch swap to the real church calendar (NEW v1.9)**

The site is currently synced against a dummy/dev public calendar. At launch, to point it at the church's real calendar:
1. Make the real church calendar public and copy its Calendar ID (§8.6 steps 1–2).
2. In `config.php` on the server, change `google_calendar.calendar_id` (and `api_key` if a different key). This is a **config edit, not a code deploy.**
3. Flush the dev cache once: `DELETE FROM calendar_events_cache;`
4. Run `php settle-private/bin/calendar-sync.php` to populate from the real calendar.
Because both dev and prod calendars are public + API-key, **nothing but the ID (and possibly key) changes.**

### **8.8 Seeding blog authors (NEW v2.0)**

Blog authors can now be created in the admin `/admin/users` UI (roadmap #5, shipped v2.4); `seed_authors.sql` remains a convenient way to bulk-seed the initial set on first boot. `sql/seed_authors.sql` is a **template with deliberately invalid placeholders** (`REPLACE_EMAIL`, `REPLACE_HASH`) so an unedited run fails loudly.

1. For each author, generate an Argon2id hash on the server:
   `php -r 'echo password_hash("their-temp-password", PASSWORD_ARGON2ID), "\n";'`
2. Paste each hash + a real email into the matching row. The file ships rows for the six non-admin staff authors (Aimee Keith, Rebecca Volk, Chris Tolliver, Jeff Keeley, Wesley Marcum, Lori Roach), seeded as role `author`.
3. **Mark Dickinson and Alecia Meyer are the assumed initial admins** (§11 #6); since admins already have authoring rights they are intentionally left commented-out in the seed (adding them as `author` would duplicate the person). Uncomment only if you want them as plain authors instead.
4. Import the edited file. `INSERT IGNORE` makes it safe to re-run (existing usernames/emails are skipped). Have each author change their password after first login.

---

## **9\. Coding Conventions**

* **PHP version:** 8.1+ required. **Production runs PHP 8.4 (ea-php84)** per cPanel MultiPHP Manager (confirmed v2.2); 8.1+ remains the stated minimum. A validation sandbox may run 8.3 (see §12) — that gap is immaterial for lint/template work but 8.4-specific behavior must be confirmed on the server.
* **Strict types** at the top of every PHP file.
* **Namespace:** `Settle\` for all source. File paths under `src/` mirror the namespace exactly. **The autoloader is case-sensitive** because the production filesystem is Linux. `Settle\Model\CalendarEvent` requires `src/Model/CalendarEvent.php` (capital C, E).
* **Naming:** `PascalCase` classes, `camelCase` methods, `snake_case` columns.
* **No global state** except `$GLOBALS['settle_config']` and the `Database` singleton. **`\Settle\Mailer` reads `$GLOBALS['settle_config']['mail']` and `\Settle\GoogleCalendar` reads `$GLOBALS['settle_config']['google_calendar']`** — consistent with this rule.
* **Templates** receive `$data` as locals via `extract()`. Escape output with `htmlspecialchars`/`e()`, except `body_html` columns. **External/untrusted text (e.g. Google Calendar event fields) is always escaped, never treated as `body_html`.** **Do NOT name a render-data key `data`, `template`, or `layout`** — `View::render` uses `extract($data, EXTR_SKIP)` and those keys collide with its own parameters, so they are silently skipped and the template variable comes out empty (see §13.9). Use a distinct key (the Settings UI uses `values`).
* **Email addresses** in public templates render via `\Settle\EmailObfuscator::link()`. Never a raw `mailto:`.
* **Phone numbers** display via `\Settle\PhoneFormatter::formatUs()`; `tel:` via `::telHref()`.
* **CSRF token field** via `\Settle\Csrf::field()` in every `<form method="post">`. JS endpoints send `X-CSRF-Token`.
* **Audit log writes** via `\Settle\AuditLog::record(...)`. Dot-namespaced verbs. Failures silently logged. **Routine 15-min calendar syncs are NOT audited** (by design — avoids ~96 rows/day of noise); sync failures go to `error_log`. Override edits ARE audited (v2.3) as `calendar.override.set` (create/update) and `calendar.override.clear` (delete), logging the cache row id as `entity_id` and the `google_event_id` + changed fields in `details`. **`settings.update` (v2.1)** records a settings save, logging the changed **keys** (not values) under `details['changed']`.
* **Outbound email** via `\Settle\Mailer::send(...)`. Plain-text only. Never put visitor-supplied text in a header.
* **External-API best-effort philosophy (v1.9):** network calls to third parties (Google) must swallow + `error_log()` failures and never break a user-visible action or blank a page. Render from local cache. Mirror `Mailer`/`AuditLog`. Never log a URL that carries a secret.
* **`LIMIT` binding:** with PDO emulation off, `LIMIT` cannot be bound as a string parameter; cast to `(int)` and inline it (see `CalendarEvent::upcoming()`). All other values stay bound.
* **Role checks** route-level (`'role' => 'editor'`) or in-code (`Auth::hasRole('editor')`).
* **Owner-scoped access (v2.0):** when a route is "author or higher" but the real rule is "the owner of *this* record, OR an editor+", enforce it **in-code** — never by route role alone. The blog is the reference: `PostController` checks `post.author_id === $_SESSION['user_id'] || Auth::hasRole('editor')` on every write, and `PublicController::canPreview()` repeats it for the public preview path. Copy this pattern for any future owner-scoped feature.
* **Time-based visibility (v2.0):** when "is it live yet?" depends on a stored timestamp, compare against a `:now` **bound from PHP** (`date('Y-m-d H:i:s')`, app timezone via bootstrap), not SQL `NOW()` — the DB may be in a different timezone. The blog's scheduled-publishing gate (`published_at <= :now`) is the reference; see §13.8.
* **Account active recheck (v2.4):** an authenticated user's `is_active` is re-verified once per request in `Auth::check()` (memoized), not only at login, so deactivating a signed-in user revokes access immediately. Any future "is this account still allowed?" gate should ride this rather than trusting the session alone.
* **Public form submissions** must include both anti-spam checks (honeypot + time-on-page), each with its own session key.
* **Redirects** via `BaseController::redirect()`.
* **Database writes** set `updated_by` to `$_SESSION['user_id']` when the table has the column. (`calendar_event_overrides.updated_by` is NOT NULL — #4b must set it.)

* **Conventions retained from the multi-church-readiness work** (now justified as plain good practice — §3.7):
  * **No hardcoded church identity in templates.** Read from `$settings`.
  * **No hardcoded brand values in CSS.** Every brand value is a CSS custom property in `theme.css`'s `:root`. **(Calendar styles in §8 of `theme.css` follow this — they reference the existing brand variables, add no new `:root` values.)**
  * **No new feature renders without a `Features::enabled()` check** at the route block, the sidebar entry, and (if it exposes a URL) the menu URL picker.
  * **Public navigation is data-driven** via `\Settle\Menu::renderTree()`.
  * **Public templates render through `\Settle\PublicView::render()`.**
  * **Separate public theme assets from admin assets.**

* **Frontend conventions:** mobile-first CSS; no framework; display font Cinzel (headings, all-caps, letter-spacing ≥0.08em); body font Lato; fonts via `<link>` not `@import`; no-JS fallbacks where reasonable. **The calendar month grid is server-rendered and works without JavaScript** (prev/next are plain links; chips are in-page anchors). **Derived brand shades (v2.2):** `--brand-primary-dark`, `--brand-primary-darker`, and `--brand-ink-soft` are `color-mix()` of the overridable base vars (`--brand-primary`/`--brand-ink`), with the static hex kept as a **preceding legacy-fallback declaration** — never hardcode a derived shade as a literal; an admin color change must keep them coherent. **Home-page vertical rhythm (v2.2):** uses a home-only `.section--tight` modifier (2.25rem block padding), not a change to the global `--section-pad-y` — keep interior-page rhythm out of home-only tweaks. **Footer (v2.2):** `.site-footer` carries **no top margin** — separation from preceding content comes from the last section's bottom padding plus the footer's own top padding. Don't reintroduce a footer `margin-top`; against a full-bleed colored last band (the home CTA) it renders as a white strip.

* **Migration discipline:** schema changes ship as a numbered file in `sql/migrations/`, idempotent where possible; update `schema.sql`. **Data/config-only changes (v1.8 settings keys, v1.9 `google_calendar` config) ship as a seed or a documented config edit, not a migration.**

---

## **10\. Prioritized Roadmap**

| Priority | Feature | Est. Effort | Why |
| ----- | ----- | ----- | ----- |
| ~~1~~ | ~~Public theming~~ | — | **DONE (v1.7).** |
| ~~1.5~~ | ~~Menu system~~ | — | **DONE (v1.7).** |
| ~~1.75~~ | ~~Core/site refactor~~ | — | **SHELVED (v1.8).** Independent-clone strategy instead (§3.7). |
| ~~6~~ | ~~Email sending~~ | — | **DONE (v1.8).** Password-reset email remains (#6b). |
| ~~2~~ | ~~Google Calendar sync + display~~ | — | **DONE (v1.9).** Override-editor admin UI split out as #4b. |
| ~~3~~ | ~~Blog Posts (multi-author CRUD + public listing)~~ | — | **DONE (v2.0).** Posts + categories + scheduled publishing + staff preview. **Last contractual feature — all five now complete.** |
| ~~4~~ | ~~Settings UI + Branding section~~ | — | **DONE (v2.1).** Admin-only `/admin/settings`; identity/contact/notifications/social/homepage/meta + logo/favicon/colors. Validated, atomic, CSRF, audited (`settings.update`). Brand colors via hex-validated inline `<style>` override. §15 built. |
| ~~4c~~ | ~~Public-UI design pass / home-page tightening~~ | — | **DONE (v2.2).** Home consolidated five bands → four; home-only `.section--tight` rhythm; inline styles lifted to `theme.css` classes; footer white-gap margin removed (global); derived shades (`--brand-primary-dark/-darker`, `--brand-ink-soft`) now `color-mix()`-derived from the overridable base vars (Option A). |
| ~~4b~~ | ~~Calendar `[hide]` tag + override editor~~ | — | **DONE (v2.3).** `[hide]` Google Calendar tag mirrors `[featured]` (`is_hidden` column, migration `0003`); the render overlay filters it everywhere. `/admin/calendar` (editor+) authors a website-only override **image + public note** (hide/feature stay tag-driven). New `CalendarOverride` model + `CalendarOverrideController`; audited (`calendar.override.set`/`clear`); sidebar link restored. Homepage widget: chronological order + override-image card background. **The calendar feature is now fully complete.** |
| ~~5~~ | ~~User management UI~~ | — | **DONE (v2.4).** Admin-only `/admin/users` CRUD + activate/deactivate; in-code lockout rails (no self-role-change, no self-deactivate/-delete, never zero active admins); FK-guarded delete; per-request `is_active` recheck; Argon2id passwords; audited (`user.*`). No schema change. |
| ~~6b~~ | ~~Self-service password reset~~ | — | **DONE (v2.5).** Public always-on /admin/forgot + /admin/reset. Hashed single-use token (sha256(raw), 15-min TTL, PHP-bound :now), non-enumerating, active-only, link host from app.base_url (not the Host header), minimal re-issue guard, audited (user.password_reset_request/_complete). New PasswordResetController + templates/auth/{forgot,reset}.php; 4 User methods; login-screen link. No schema change. |
| ~~7~~ | ~~Audit log viewer~~ | — | **DONE (v2.6).** Admin-only read-only `/admin/audit`: paginated, reverse-chronological, with sticky filters (action exact/prefix, entity type, actor, date range). Read methods added to `\Settle\AuditLog` (`query`/`count`/`distinct*`; LEFT JOIN users; parameterized + whitelisted; inlined LIMIT/OFFSET; escaped JSON details). New `AuditLogController` + `templates/admin/audit/index.php`; admin-group sidebar link. Not self-audited. No schema change. |
| ~~8~~ | ~~Rate-limit login attempts~~ | — | **DONE (v2.7).** `\Settle\RateLimiter` (fail-open): 5/15-min rolling window on login keyed on ip+username; coarse IP-only cap (10/15min) on the reset-request form; `auth.lockout` audited at the crossing. New `login_attempts` table (migration `0004`, no FK). #6b per-account cooldown retained (different axis). |
| **8a** | **Calendar display enhancements** | 1 day | New, from §2.1. Start/stop times on Upcoming-Events cards AND the full calendar; **day view** + **list view** (see live `/calendar/month/` and `/calendar/list/`); a "Subscribe to Google Calendar" link. Self-contained extension of the now-complete calendar; times already cached — no schema change. |
| **8b** | **Dashboard "Welcome back" enrichment** | 0.5–1 day | New, from §2.1. Recent contacts, recent prayer requests, recent blog posts on the admin dashboard; optional most-used-pages stats (needs a tiny view counter). Low-risk quick win; models already exist. |
| 9 | **Media: thumbnail variants + multi-image / drag-&-drop upload** | 1 day | Thumbnails (grid + blog cards still use full-size images) **plus** the §2.1 multi-image / drag-&-drop upload — bundled because both touch the `\Settle\Upload` / Media surface. |
| **9.5** | **Renovation follow-along** | 0.5 day | New, from §2.1. A way for the congregation to follow the upcoming renovation — a dedicated blog category + a landing page (rides the existing blog). **Time-sensitive** ("a few months out"); should land before work begins, so it may jump the queue. |
| 10 | **Migration of existing content** | 1–2 days | Bulk-import current settleumc.com text + images. Overlaps with #13. **#10b (design sub-pass):** the §2.1 "more eye-catching home page" pass, building on #4c — wants a references conversation before scoping. |
| 11 | **Tests** | ongoing | PHPUnit for models/controllers; Playwright end-to-end. Commit the calendar + blog + home-render + audit + **rate-limiter (v2.7, 26 assertions)** + **calendar #8a (v2.8: 32-assertion data + 22-assertion render)** harnesses as a starting set. |
| 13 | **settleumc.com missing features / content migration gap** | scoping | New (v2.0). Sermons, Watch/livestream, Give, bulletin, newsletter, employment, ministry pages. Mostly Pages content + a few small features; client decisions resolved in §16 (Sermons/Newsletter/Bulletin stay Constant Contact / YouTube links, but the pages should be more attractive — Sermons with Live / Traditional / Shout / Special-Services categories). |
| 12 | **Site search** | 1 day | New (v2.0). `/search` over published pages + posts + staff (MySQL `LIKE` or FULLTEXT). Restores the header search the live site has. **Deliberately deferred until content is populated** (after #10/#13). See §16. |
| **12b** | **Searchable history page + photo archive** | scoping | New, from §2.1. Digitize the two church-history books + new historical material and old member/church photos, all **searchable**. Depends on **#12 (search)**, **#9 (thumbnails)**, and **#10 (content)** — so it sits after those. |
| **14** | **User help document** | 1 day | New, from §2.1. A user guide as a printable **PDF** and as **HTML** pages, with deep links from admin functions to the relevant section. Scheduled **near launch** (after features freeze) so it doesn't go stale. |

**Recommended sequence (updated v2.8, per owner):** the contractual scope, the Settings/Branding UI (#4), the home-page design pass (#4c), the **calendar feature incl. the [hide] tag + override editor (#4b)**, **user management (#5)**, **self-service password reset (#6b)**, **audit-log viewer (#7)**, **login rate-limiting (#8)**, and now the **calendar display enhancements (#8a — spanning-bar month grid + list/day views + subscribe links)** are all done. Next is **#8b dashboard enrichment** (a richer "Welcome back" page — low-risk quick win), then **#9 media thumbnails + multi-image upload** (bundled). **#9.5 renovation follow-along is time-sensitive and may jump ahead** of #8b/#9 if the renovation start date approaches. Then **content migration (#10, incl. the #10b home-page design sub-pass) + the settleumc.com gap items (#13)**. **Site search (#12) stays deliberately deferred** until the site is fully populated, after which the **searchable history page (#12b)** can build on it. The **user help doc (#14)** and a pre-launch checklist come last, before DNS cutover. (#11 tests is ongoing.)

---

## **11\. Open Questions for the Client**

1. ~~Physical address~~ — **Resolved: 202.**
2. ~~Youth ministry~~ — **Resolved.**
3. ~~Google Calendar ID for the real church calendar~~ — **Still needed at launch.** Dev currently runs against a dummy public calendar; the swap is documented in §8.7. Obtain the real public calendar ID before cutover.
4. ~~Service account vs API key~~ — **Resolved: API key + public calendar (§3.3). Implemented v1.9.**
5. ~~Email provider~~ — **Resolved: authenticated SMTP via a cPanel mailbox (v1.8).**
6. **Initial admins** — assumption is Mark Dickinson and Alecia Meyer; confirm before seeding production.
7. ~~Where contact messages route~~ — **Resolved: `contact_notify_to` (v1.8). Confirm the actual address.**
8. ~~Where private prayer requests route~~ — **Resolved: `prayer_notify_to`; private alerts bodyless (v1.8). Confirm the actual address.**
9. ~~Domain switchover plan~~ — **Resolved: parallel build, DNS cutover at launch.**
10. **Mobile app dependency:** are the Red Pixel Studios iOS/Android apps staying?
11. **Sermons archive:** dynamic from YouTube or manual list?
12. **Newsletter archive:** auto-list or manual?
13. **Staff bios:** does the church want bios for some/all staff?
14. ~~**Blog (roadmap #3) specifics:** authors? self-publish vs approval? categories?~~ — **Resolved & shipped (v2.0).** Authors **self-publish their own** posts; editors manage all. **Categories yes** (curated, editor-managed: Music, Youth, Children's Programs, Senior Programs, …) — many-to-many. Excerpts manual with auto-fallback. No comments. Authors seeded: Aimee Keith, Rebecca Volk, Chris Tolliver, Jeff Keeley, Wesley Marcum, Lori Roach (Mark Dickinson + Alecia Meyer assumed admins, §11 #6). Scheduled publishing + staff preview added on top.
15. ~~Trinity branding & details~~ — **No longer blocking. Multi-church shelved (§3.7).**
16. **Confirm the real notification inboxes** for `contact_notify_to` and `prayer_notify_to`. *(As of v2.1 these are editable in the admin Settings UI under "Email notifications" — no DB edit needed; still confirm the actual addresses with the church.)*
17. **Featured-event convention buy-in:** confirm staff are comfortable typing `[featured]` into an event description (vs. another convention). Easy to change via `google_calendar.featured_keyword`.
18. ~~Calendar override-editor (#4b) scope~~ — **Resolved & shipped (v2.3).** Owner chose: hide/feature via Google Calendar tags (a `[hide]` tag was added to mirror `[featured]`), and the `/admin/calendar` editor (editor+) authors only the website-only **image + public note**. No force-unfeature and no title/text override were wanted, so no extra migration beyond `0003` (the `is_hidden` column).

---

## **12\. How to Use This Document in a New Claude Session**

* Ask the new Claude to **read this document first** before any code work.
* Source code lives at `https://github.com/sburton59/settle-site` (public repo). `urls.txt` lists every source file as a `raw.githubusercontent.com` URL. **`urls.txt` lists *code*, not the docs;** the handoff/brief live in the project files. A companion `urls-details.txt` lists each file's last-modified timestamp and byte size — use it as a smoke test to detect stale fetches.
* **Prefer a fresh `git clone` over fetching individual `raw` URLs** when you need to read multiple current files — see §13.5/§13.7. The raw CDN has repeatedly served stale snapshots of *modified* files; a clone reads true HEAD.
* Effective prompt format: *"I want to implement feature X from §10's roadmap. Please review [reference controller] for conventions, then propose the implementation."*
* Always follow §9 conventions — strict types, prepared statements with distinct named placeholders, CSRF on POSTs, role-checked middleware, escaped output, `AuditLog::record()` on security-relevant writes, `Mailer::send()` for outbound mail, best-effort failure handling for any external API.
* When making schema changes, create a new migration in `sql/migrations/` AND update `schema.sql`. Data/config-only changes ship as a seed or documented config edit.
* **File delivery:** a single zip per phase with the directory structure mirroring the repo, plus a `MANIFEST.txt` (NEW/REPLACE + verification recipe). Validated through Phase A+B, the v1.8 email pass, the v1.9 calendar pass, and the v2.1/v2.2 passes.

### **12.1 Dev sandbox setup (for an AI/dev session that runs validation)**

A new Claude session does its validation (`php -l`, ad-hoc render/parse harnesses) in an **ephemeral container that starts with no PHP** and resets every session — nothing installed persists, and it can't be pre-baked. So the first step of any session that will lint or run a harness is a one-time install:

```
apt-get update && apt-get install -y php-cli php-mbstring php-sqlite3 php-curl php-gd
```

That enables the extensions the codebase touches in a sandbox: `mbstring` (the `mb_*` calls in templates — e.g. the homepage welcome-lead fallback), `curl` (`\Settle\GoogleCalendar`), `gd` (`\Settle\Upload` resizing), and `sqlite3`/`pdo_sqlite` (the test harnesses run against SQLite). `pdo_mysql` is **not** needed in the sandbox — there's no MySQL server there; harnesses use SQLite by design.

**Version drift to be aware of:** production is **PHP 8.4 (ea-php84)** per cPanel MultiPHP Manager. The sandbox installs **8.3** (the newest in the default Ubuntu repos; 8.4 would need the `ondrej/php` PPA, which the sandbox network blocks). The stated minimum (§9) is still 8.1+. For linting and template/CSS work the 8.3↔8.4 gap is immaterial, but anything that depends on 8.4-specific behavior must be confirmed on the server, not just in the sandbox.

**Deliberately NOT in the repo:** this is sandbox-only tooling. A setup script committed to `settle-site` would deploy to the church server as dead weight, so it lives here in the handoff (which is project knowledge, not the repo) instead.

---

## **13\. Lessons Learned: File-Handoff Pitfalls**

### **13.1 The case-collision trap**
Windows treats `staff.php` and `Staff.php` as the same file. Distinct flat filenames (or a single mirrored-directory zip) avoid the collision.

### **13.2 Prevention rules**
* On every Windows clone, run `git config core.ignorecase false`.
* Prefer a single zip with the directory structure mirroring the repo.
* `ls -la` file sizes are a fast smoke test (cross-check against `urls-details.txt`).
* When debugging a routing/rendering mystery where standard checks pass, `cat` the suspect file.

### **13.3 The "wrong file saved" trap**
When a downloadable file produces unexpected output, first check whether the file on disk contains what you think. SSH in and `cat` it. When iterating across turns, version-suffix each download.

### **13.4 Other minor bugs**
* PowerShell `Get-Content | Set-Content` mangles `<`/`>` in PHP files.
* `sed -i` has failed multiple times. Full-file replacement is more reliable.
* **Crontab lines in PHP block comments (v1.9):** a `*/15` crontab spec inside a `/* … */` PHP comment closes the comment early (`*/`). Document cron cadence as `0,15,30,45 * * * *` (no `*/`) in any commented example, or escape it.

### **13.5 The stale-GitHub-URL trap**
`/blob/main/` URLs are CDN-cached and can return stale snapshots after a push. `raw.githubusercontent.com/.../main/...` *usually* serves live content, but **not always** (see §13.7). `urls.txt` uses raw URLs exclusively. If a fetch contradicts known-recent work, suspect the cache, and re-fetch or cross-check a second file before trusting it.

### **13.6 The zip-delivery pattern**
1. One zip per batch. 2. Inside: a `MANIFEST.txt` plus files in a repo-mirroring directory structure. 3. The user extracts and merges with the local clone. 4. The MANIFEST lists each file, its destination, NEW vs REPLACE, and a verification recipe. Used through Phase A+B, the v1.8 email pass, the v1.9 calendar pass, and the v2.1/v2.2 passes with zero file-handoff bugs.

### **13.7 The stale `raw` CDN on MODIFIED files — prefer a clone (NEW v1.9)**
At the start of the v1.9 session, `raw.githubusercontent.com` served **pre-v1.7/v1.8 snapshots** of files that had existed earlier and were later modified (`index.php`, `config.example.php`), while a brand-new file (`Mailer.php`) came through current. **Re-fetching the same raw URL returned the same stale bytes** — a plain retry does not bust the edge cache. The tell: fetched file sizes disagreed with `urls-details.txt`, and the fetched `index.php` was missing v1.7 routes that are known to be live.

**Workaround that worked:** `git clone --depth 1 https://github.com/sburton59/settle-site.git` reads true HEAD directly and bypasses the raw CDN entirely. Every cloned file's byte size then matched `urls-details.txt` exactly. (Used again in the v2.2 pass — clone, then cross-check sizes.)

**Rule going forward:** when you need the current state of more than a file or two — especially files that have been edited across sessions — **clone the repo and read from the working tree**, rather than fetching individual `raw` URLs. Keep `urls-details.txt` (size + mtime per file) as the cross-check; if a fetched file's size disagrees with it, you are looking at a stale cache.

### **13.8 The SQL-`NOW()` vs PHP-time trap (NEW v2.0)**
The first cut of the blog's public queries gated visibility with `published_at <= NOW()` (SQL's `NOW()`). In testing, a freshly-published post failed to appear on `/blog` and 404'd at its own URL. Cause: `published_at` is stamped from PHP (`date()`, app timezone `America/Chicago` per `bootstrap.php`), but `NOW()` returns the **database** server's clock, which on shared hosting can be a different timezone (often UTC). A Chicago-stamped "just now" looked like the future to a UTC `NOW()`, so the row was filtered out.

**Fix / rule:** for any "is this live yet?" comparison, bind `:now` from PHP (`date('Y-m-d H:i:s')`) and compare `column <= :now`, rather than using SQL `NOW()`/`CURDATE()`. That keeps the stored timestamp and the comparison on the same clock. This became load-bearing once scheduled publishing shipped (a future `published_at` must mean exactly "hidden until that Chicago time"). The same principle applies if any future feature date-gates content. (The calendar sync is unaffected — it stores naive local datetimes and never compares against `NOW()` at render.)

### **13.9 The `View::render` `extract($data, EXTR_SKIP)` key-collision (NEW v2.1)**
`\Settle\View::render(string $template, array $data, ?string $layout)` makes template variables with `extract($data, EXTR_SKIP)`. `EXTR_SKIP` means **a key that collides with a variable already in scope is silently skipped** — and inside `render()`, `$data`, `$template`, and `$layout` are already in scope (they're the parameters). The first cut of the Settings UI passed the field values under the key **`'data'`**. `extract()` skipped it (a `$data` already existed — the parameter), so the template's `$data` was the *parameter array*, not the values map. Every field rendered blank; the blank GET form then POSTed empty strings and the save **blanked the other settings**. The tell: fields empty on screen even though the DB had values, and "editing one setting wipes the rest."

**Fix / rule:** never pass template data under the keys `data`, `template`, or `layout` — they collide with `View::render`'s own parameters under `EXTR_SKIP`. The Settings UI uses **`'values'`**. When debugging "template variable is unexpectedly empty/null," check for a name collision with the renderer's parameters first. (Latent elsewhere: the public prayer/contact templates also receive a `'data'` key; harmless today because those forms start blank, but a validation-error re-render would drop the visitor's typed values — fix if echo-on-error is ever wanted there.)

### **13.10 The `PublicView` bypass that blanked public pages (NEW v2.1)**
The public Contact and Prayer pages rendered blank in production. Their controllers' `renderPublic()` called `\Settle\View::render($template, $data, 'public')` **directly** instead of `\Settle\PublicView::render($template, $data)`. `PublicView` is the only thing that injects `$settings` and `$menu_tree`; without them the public layout's nav renderer received a null `$menu_tree` (typed `array`) and threw a `TypeError` — an uncaught fatal that, under production's `display_errors=off`, surfaces as a blank page.

**Fix / rule:** all public pages render through `\Settle\PublicView::render()`, never `View::render(.., 'public')` directly (this is already a §9 convention; the two controllers had regressed from it). When a public page is blank with no visible error, suspect a fatal hidden by `display_errors=off` — re-run with errors on, or tail `storage/logs/php-error.log`.

### **13.11 The footer `margin-top` that rendered as a white gap (NEW v2.2)**
`.site-footer` carried `margin-top: 4rem`. That margin sits **outside** the footer's dark background, so against the white page body it rendered as a 4rem white strip above the footer. Harmless when the last band was white content, but the v2.2 home page ends in a full-bleed colored "Get in touch" band, so the strip showed as an obvious gap between the colored band and the dark footer.

**Fix / rule:** the margin was removed globally; separation now comes from the last section's bottom padding plus the footer's own top padding. Don't give the footer a top margin — if a page can end in a full-bleed colored band, an outside margin will show as a contrasting strip. (An earlier scoped fix using `main:has(> .section--brand:last-child) + .site-footer` was considered but dropped in favor of the simpler global removal, at the owner's direction.)

### **13.12 The url-encoded media path that broke a CSS background (NEW v2.3)**
The v2.3 homepage Upcoming-Events card was given an event's override image as a CSS `background-image`. The first cut built the URL with `rawurlencode($filename)`. But `media.filename` (set by `\Settle\Upload`) is a **relative path with subdirectories** — `uploads/YYYY/MM/<random>.<ext>` — so `rawurlencode` turned the `/` separators into `%2F`, yielding `/uploads/2026%2F06%2F....jpg`. Apache returns 404 for encoded slashes by default (`AllowEncodedSlashes Off`), so the image never loaded and the card fell back to its `--brand-ink` background — a solid black box. The homepage slideshow, which has rendered a `media.filename` as a background since v1.2, worked precisely because it does NOT encode.

**Fix / rule:** a `media.filename` is already a URL-safe relative path (a hex name under a `YYYY/MM/` folder). Turn it into a URL the way the slideshow does — `'/uploads/' . ltrim($filename, '/')`, output through `e()` for attribute safety, with **no url-encoding** (which corrupts the `/` path separators). When any future feature renders a `media.filename` as a URL, follow this pattern; never `rawurlencode` the whole path.

---

## **14\. Multi-Church Architecture (HISTORICAL — shelved, retained for reference)**

> **Status (unchanged since v1.8): NOT being implemented.** The shared-core/per-site refactor described here was the plan as of v1.6–v1.7. A committed church will instead be stood up as an **independent clone** (separate repo, server, database). The §9 conventions that aid reuse are kept; the refactor below is not. This section remains only so the reasoning is recoverable if the decision is ever revisited.

The original design: a shared `Settle\Core\` namespace in a single core repo, with per-church repos overriding branding, theming, navigation, and feature enablement. Core would contain all PHP source, admin templates, default public templates, schema/migrations, and a CSS-variable-based default stylesheet. Each per-site repo would contain only `config.php`, `theme.css` variable overrides, brand assets, any public-template overrides, and a thin front controller. `View` would gain a template-resolution fallback chain (`SITE_ROOT` first, then `CORE_PATH`). Feature flags, the menu system, and per-site deploy pipelines were all specified. The independent-clone strategy supersedes all of the above.

---

## **15\. Theming & Branding UI (BUILT v2.1; derived shades completed v2.2 — design note retained)**

> **Status: BUILT (v2.1), extended (v2.2).** Implemented as designed below; retained as rationale. **As-built (v2.1):** the override is emitted from `templates/layout/public.php` right after the `theme.css` `<link>` and before `</head>`; each color is re-validated against `/^#[0-9a-fA-F]{6}$/` **at emit time** (not only on save); v1 overrides the two base variables `--brand-primary` and `--brand-ink`; seed defaults `#9E2A2B` / `#2C2C2E` match `theme.css`. **Update (v2.2):** the derived shades (`--brand-primary-dark`, `--brand-primary-darker`, `--brand-ink-soft`) are now **`color-mix()`-derived from the base vars** directly in `theme.css` (Option A — `80% / black`, `60% / black`, `85% / white` respectively, with the prior static hex retained as a legacy fallback), so an admin's base-color change now stays coherent across hovers and dark accents. The deferral to #4c is resolved.

The site admin should eventually change the logo and brand colors without a developer. This is folded into roadmap **#4 (Settings UI)** because most of the plumbing already exists.

**What already works in our favor:**
* Church identity (logo URL, favicon, contact info, social links) already lives in `settings` and is read by the templates. A plain settings form exposes all of it — no schema change. Logo/favicon selection reuses the Media Library.
* All brand values are already CSS custom properties in `theme.css`'s `:root` — including the calendar styles added in v1.9, which reference those variables and introduce no new hardcoded colors.

**The one piece to build for colors:** move the brand color values from static `:root` declarations in `theme.css` into `settings` keys (e.g. `brand_primary`, `brand_ink`), then have the public layout emit a small inline `<style>:root{ --brand-primary: …; }</style>` block (values validated server-side against a strict hex pattern) that overrides the stylesheet defaults from the database. `theme.css` keeps sane fallback values so the site still renders if a key is blank.

**Outcome:** shipped in v2.1 as designed, with a "Branding" section in the Settings UI from the start. The half-day estimate held (most plumbing — `Settings::put()`, the Media picker, the `:root` variables — already existed). The derived-shade coherence was completed in v2.2 (Option A, `color-mix()`).

---

## **16\. settleumc.com Review — Missing Features & Content Gap (NEW v2.0)**

A review of the live WordPress site (settleumc.com) on June 1, 2026 mapped what it offers against the new build, to drive roadmap #13 and the pre-launch content migration (#10). The new platform's Pages CRUD + Media Library already *support* most of this; the gap is mostly **content to create**, not code to write.

### **Build-vs-migrate-vs-decide**

**A. Content pages (no new code — create via Pages CRUD, roadmap #10/#13).** The live nav exposes a deep ministry tree:
* **I'm New**, **About Us**, **Directions & Parking**, **Weekly Schedule**
* **Connect** → **Children**, **Settle Preschool**, **Parent's Day Out**, **Youth**, **Adult Ministries** (+ **The Roadrunners**), **Missions** (+ **Mission Partners**, **Mission Outreach**, **Give To Faith Promises**)
* **Newsletter** (landing)
These become `pages` rows with menu entries. Bulk text + images import is roadmap #10.

**B. Small features / external links (little code).**
* **Give / donations** — the live site links out to a giving page. Simplest parity = a menu link to the church's existing giving provider; no in-app payments. **Confirm the URL.**
* **Watch / livestream** — a page embedding the church's YouTube (`@settlememorialunitedmethod5839`). A simple Page with an embedded player, or a small dedicated template.
* **Worship bulletin** ("This Sunday's Bulletin") — currently a weekly PDF/link. Could be a Media Library upload linked from a page, or a small "current bulletin" setting.
* **Mobile-app smart-banner** — the live site shows an iOS/Android install banner. App URLs are already in `settings` (`app_ios_url`, `app_android_url`); this is a small layout addition, not new data.
* **Employment / news posting** — the live site has an Employment page under a `/news/` section. Could be a Page, or (nicely) just a **blog category** now that the blog ships.

**C. Needs a client decision before building.**
* **Sermons / sermon series** (open question #11) — dynamic pull from YouTube vs. a manual list. A manual list could be a blog category or a small dedicated model; a dynamic feed is more work and an external dependency. **Decide before scoping.**
* **Newsletter archive** (open question #12) — auto-listed (e.g. from uploaded PDFs in the Media Library) vs. a manually maintained page. **Decide before scoping.**
		Answer: For Sermons, Newsletters and Worship Bulletins, they currently created using Constant Contact (or Youtube for sermon videos) and then a link is listed on the appropriate page (https://settleumc.com/sermon-series/, https://settleumc.com/newsletter/, https://settleumc.com/this-sundays-bulletin/).  We'll continue that process but I want to make those pages on the new site more attractive and useful than just a long list. With the sermon page having categories for "Live", "Traditional", "Shout", "Special Sundays/Services" videos.

### **D. Already at parity or better**
Home slideshow, Upcoming Events (homepage widget + full calendar), Staff directory, Contact form, Prayer requests, secure admin, multi-author blog, and an admin Settings/Branding UI — all built. **Site search** (the live header has a search box) is the one interactive parity gap and is roadmap #12.

### **Suggested handling**
Treat **A** as the bulk of content migration (#10). Fold **B** into #13 as quick wins (the smart-banner and Give link are near-trivial; Watch and bulletin are a page each). Resolve the two **C** decisions with the client, then scope. None of A/B blocks launch individually, but the pre-launch checklist should confirm every live-site nav destination has a home (page, link, or a deliberate decision to drop it).

### **13.13 Deleting a user is usually impossible — deactivate instead (NEW v2.4)**
Building user management surfaced that a hard `DELETE` on `users` is refused by the database for almost any real staff account. Four tables reference `users(id)` with `ON DELETE RESTRICT`: `posts.author_id`, `pages.updated_by`, `media.uploaded_by`, and `calendar_event_overrides.updated_by`. So a user who has ever authored a post, edited a page, uploaded a photo, or saved a calendar override cannot be deleted — the `DELETE` throws a `PDOException` (integrity constraint). Only `audit_log.user_id` (SET NULL), `menu_items.updated_by` (SET NULL), and `user_sessions.user_id` (CASCADE) let go.

**Rule:** treat **deactivation (`is_active = 0`) as the canonical "remove access" action**, not deletion. `Auth::attempt()` already refuses an inactive row, and `Auth::check()` now re-checks `is_active` per request so a signed-in user is dropped on their next request. `UserController::destroy()` still offers delete (guarded against self-delete and the last active admin) but catches the `PDOException` and tells the admin to deactivate instead. Any future "remove a user" affordance should lead with deactivate and treat delete as best-effort for never-used accounts.

### **13.14 Password-reset link host: use configured base_url, not the request Host (NEW v2.5)**
The #6b reset email contains an absolute link carrying a valid (raw) token. The obvious way to build it — `$_SERVER['HTTP_HOST']` — is a known **password-reset poisoning** vector: the `Host` header is attacker-controllable, so an attacker who triggers a reset for a victim can forge the host, and the victim's *real* inbox then receives a working-token link pointing at an attacker-controlled domain. The fix is to build the origin from a **server-side configured value**, not request input.

**Rule:** `PasswordResetController::baseUrl()` reads `$GLOBALS['settle_config']['app']['base_url']` (already present in `config.php`), and only falls back to the request scheme+host if that is unset. There is **no config-schema change**, but **`app.base_url` must reflect the current public host** — it is `settlemem.org`/`settleumc.org` pre-cutover and `settleumc.com` after, so it changes at DNS cutover exactly like the mail host (§8.5/§8.7). Add it to the pre-launch checklist. The same "configured origin, never the Host header" rule applies to any future feature that emails or otherwise hands out an absolute link with a credential in it.

A secondary, smaller note from the same build: the login screen has **no success-message channel** (its template only renders an error). Rather than widen `AuthController` to add one, a successful reset renders a **self-contained confirmation screen** (in `auth/reset.php`, `done` state) with a "Sign in" link. If a future flow wants a green banner on the login page itself, add a `login_notice` read to `AuthController::showLogin` and a `flash-success` render to `auth/login.php` — deliberately not done here to keep #6b's footprint to its approved file set.

### **13.15 Cross-DB `LIKE` portability — strip metacharacters, don't `ESCAPE` (NEW v2.6)**
The audit viewer's action **prefix** filter (`action LIKE 'user.%'`) first reached for the textbook safe form, `LIKE :p ESCAPE '\\'` with the user portion backslash-escaped. That is correct on MySQL but **breaks the SQLite validation harness**: the two engines disagree on backslash handling inside string literals / `LIKE` (MySQL treats `\` as an escape in string literals by default; SQLite does not, and an `ESCAPE` argument must be exactly one character), so the harness would have been exercising a *different* query than production — defeating the point of validating against it. **Fix / rule:** since the prefix values are server-derived and alphanumeric (the segment before the first dot of a known action — `user`, `prayer`, `calendar`, …), the model **strips** `LIKE` metacharacters (`%`, `_`, `\`) from the prefix and appends a single trailing `%`, with **no `ESCAPE` clause** — identical and safe on both engines. An empty-after-strip prefix (e.g. a bare `%`) is **skipped** rather than emitted as a match-all `LIKE`. Defense in depth still holds because the controller separately **whitelists** the prefix against the set actually present in the log. When a future filter needs `LIKE` over user-influenced input, prefer strip-and-anchor over `ESCAPE` if a SQLite harness is in the loop; reserve `ESCAPE` for MySQL-only paths where the literal `%`/`_` must be preserved. (Tooling aside: a harness that defines `Settle\*` stubs via **bracketed** `namespace {}` blocks must put *all* top-level code — including helper functions and `require`s — inside a namespace block; PHP forbids any statement before the first bracketed namespace except `declare`.)
