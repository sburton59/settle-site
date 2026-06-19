# **Settle Memorial UMC Website Modernization — Project Handoff**

**Document version:** 3.10 **Date prepared:** June 19, 2026 **Purpose:** This document brings a new contributor (human or AI) fully up to speed on the project so work can continue without losing context.

**Recent changes** (full history in `CHANGELOG.md`):

| Ver | Date | Summary | More |
| --- | --- | --- | --- |
| 3.10 | 2026-06-19 | **3rd-tier flyout hover-gap fix.** The third-level fly-out menu vanished ~80% of the time on the way to clicking it: the `.site-nav__submenu .site-nav__submenu` rule offsets the side-fly-out with `margin-left: 0.25rem`, leaving a ~4px **dead strip** of un-hoverable page between the 2nd-tier item and the 3rd-tier panel — crossing it dropped `:hover`/`:focus-within` and `display:none` snapped the panel shut. Fixed with a transparent **hover bridge** (`.site-nav__submenu .site-nav__submenu::before`, `left:-0.25rem; width:0.25rem; height:100%`) that fills the gap; because it lives inside the fly-out's subtree, hovering it keeps the parent item's `:hover` alive, so the panel stays open while the 0.25rem visual separation is preserved. The 2nd tier was never affected (it sits flush, `top:100%`, no offset). **CSS only — one rule appended after the 3rd-tier block; no new `:root` vars (§9), no template/PHP/schema/SQL/route/menu change.** Note: CSS can't fully solve diagonal "menu-aim" (clipping a sibling on a diagonal path) — that needs JS hover-intent — but the gap was the dominant cause. | §13.19 |
| 3.9 | 2026-06-18 | **Cover-image buttons on `/books`.** The library shelf now selects each book by its scanned **cover** rather than a text card. Covers are committed static assets at `public_html/Settle/assets/img/books/{slug}.jpg` (served by Apache, off the Media Library — matching the Books feature's off-CMS stance), optimized to ~1000-px-tall progressive JPEG. `BooksController::BOOKS` gained an optional `cover` key; the index renders `<img class="cover">` + a caption (title/subtitle/year), and a book **without** a cover degrades to the old bordered text card (so no broken images). Shelf is a centered flex-wrap row aligned at top so cover tops line up despite differing aspect ratios; hover-lift + keyboard focus ring. **No schema/migration/config/route/menu change — no SQL.** | §7, §13 |
| 3.8 | 2026-06-18 | **Second book + `/books` library index** (extends v3.7 Books). Added *Behind the Open Door* (1995), a history of the Open Door Sunday School Class — set from the booklet PDF, web-cleaned (hyphens rejoined, scannos + the "EPLOGUE" heading fixed, original folios v/1–34 kept), with the 1950s Minstrel section reproduced verbatim as record. New structures (asterisk anecdote vignettes, project sub-headings, deeds list, leaders block) via **additive-only** `_styles.php` classes, so *Our Church* stays **byte-identical**. With a 2nd title in hand, the deferred **`/books` library index** shipped: `BooksController::library()` + `books_index.php` (a "shelf" off the same registry) + the literal `/books` route registered before `/books/{slug}`; three internal-link-picker entries. **No schema/migration/config/settings change — no SQL this release.** | §7, §13 |
| 3.7 | 2026-06-17 | **Church-history web edition (Books feature)** — a small public `/books/{slug}` feature serving long-form historical reprints as standalone cream-paper book editions, kept off the Pages/TinyMCE surface so their hand-set markup is never mangled. First book: the 1976 reprint *Our Church — A Story of a Hundred Years' Service* (OCR-cleaned, line-break hyphens rejoined, original folios 3–28 preserved in the margin). One `BooksController` + a registry, a shared `book` chrome, a shared `_styles.php`, and the content fragment; the `/books` library index is deliberately held until a 2nd book exists. Linked from the published History page. **No schema/migration/config/settings change.** | §7, §13 |
| 3.5 | 2026-06-15 | **Admin help doc (#14)** — shipped. One source of truth (`\Settle\Help`) rendered two ways: full single-page doc at `/admin/help` (TOC, per-role capability matrix, per-section print links, print CSS with one section per page) and per-section view at `/admin/help/{slug}` (printable on its own). New `HelpController`; new help templates (`index.php`, `section.php`, shared `_styles.php`). Per-role matrix (Author/Editor/Administrator) transcribed from the **actual route role gates + in-code ownership/`hasRole()` checks** (not the sidebar, which shows most links to everyone). Owner chose **single sidebar "Help" link** over per-screen contextual links (least disruptive to the in-use admin templates); includes the "you may see links you can't use → Forbidden" expectation note + an account/forgot-password section. Auth-required, no role gate (everyone may read help). | §3.4, §10 |
| 3.4 | 2026-06-15 | §2.2 church-admin review fixes — **Batch 1** (footer P.O.-Box emphasis, configurable Constant Contact newsletter link, mobile-menu scroll cap, explicit create-password guard, **no-JS staff email link**, `telHref()` `tel:` fix) + **Batch 2** (prayer-chain opt-in, migration `0006`, multi-address notify for prayer & contact, `email_list` settings type, prayer-form repopulation fix). Renovation follow-along (#9.5) **dropped as a feature** — it's now just a blog post. | §2.2, §10, §13 |
| 3.2 | 2026-06-08 | Image pass (#10 tail): `seed_staff.sql` (11 staff rows) + `bin/migrate-wp-images.php` — 21 slideshow imports, 9 staff portraits attached, 3 section bgs to Library | §4, §17 |
| 3.1 | 2026-06-08 | Content migration (#10): 3-tier nav + 21 draft pages, content seeds (link / welcome-info / Connect-ministry), nav-toggle fix, media Copy-link, wp-asset migration CLI | §7, §10, §17 |
| 3.0 | 2026-06-06 | Media thumbnails + multi-image / drag-&-drop upload (#9) | §5, §7, §10 |
| 2.9 | 2026-06-06 | Dashboard enrichment (#8b): role-aware cards + recent activity + quick actions | §7, §10 |
| 2.8 | 2026-06-06 | Calendar display (#8a): spanning-bar month grid + list/day views + subscribe; phone-default list, wrapping titles, end-times | §7, §10 |
| 2.7 | 2026-06-06 | Login rate-limiting (#8) + admin limiter-health banner | §3.5, §7 |
| 2.6 | 2026-06-05 | Audit-log viewer (#7) | §7, §13.15 |
| 2.5 | 2026-06-05 | Self-service password reset (#6b) | §3.5, §13.14 |
| 2.4 | 2026-06-05 | User management (#5) | §3.5, §13.13 |
| 2.3 | 2026-06-01 | Calendar `[hide]` tag + override editor (#4b) — calendar complete | §13.12 |
| 2.2 | 2026-05-31 | Home-page design pass (#4c) + derived brand shades | §13.11 |
| 2.1 | 2026-05-30 | Settings UI + Branding (#4) | §15 |
| 2.0 | 2026-05-29 | Multi-author blog (#3) — all 5 contractual features complete | §13.8 |
| 1.9 | 2026-05-30 | Google Calendar integration (#2) | §13.7 |
| 1.8 | 2026-05-30 | Email sending (#6); multi-church shelved | §3.7 |
| 1.7 | 2026-05-28 | Public theming + data-driven menu | — |
| 1.1–1.6.1 | — | Foundations: media, slideshow, staff, prayer, contact, audit log | `CHANGELOG.md` |

---

## **1\. Executive Summary**

Settle Memorial United Methodist Church (Owensboro, Kentucky) is replacing its existing WordPress site at **settleumc.com** with a custom-built PHP/MySQL application. The motivations are explicit: WordPress's attack surface, the licensing cost of premium plugins, and the maintenance burden of a heavily customized install.

The new site is built from scratch — no framework, no WordPress, no CMS dependency — using plain PHP 8.1+ and MySQL/MariaDB. It must be (a) secure, (b) inexpensive to maintain, and (c) operable by non-technical church staff through a clean admin panel.

Current status as of handoff: **Pages CRUD, Media Library, WYSIWYG editor, Homepage Slideshow, Staff Directory, Prayer Requests, Contact Form, public theming, a data-driven menu system, outbound email notifications, Google Calendar integration, the multi-author blog (with categories + scheduled publishing), an admin Settings UI with a Branding section, AND a home-page design pass are all working end-to-end.** A staff member can log in, edit pages, manage images, manage the homepage slideshow, maintain a staff directory, triage prayer requests and contact messages, manage the public navigation, and author blog posts — all through the admin panel; an admin can additionally edit church identity, contact, notification routing, social links, homepage copy, and branding (logo/favicon and brand colors) from `/admin/settings`. New contact and prayer submissions notify staff by email. Church events are managed entirely in Google Calendar and appear automatically on the public site.

**All five contractual proposal features are complete** (standard pages/photo management, homepage slideshow, multi-author blog, Google Calendar integration, secure admin panel), and a run of post-contract features have also shipped: the Settings UI + Branding (#4), the home-page design pass (#4c), the calendar `[hide]` tag + override editor (#4b), user management (#5), self-service password reset (#6b), the audit-log viewer (#7), login rate-limiting (#8), the **calendar display enhancements (#8a — spanning-bar month grid + list/day views + subscribe links)**, and the **dashboard enrichment (#8b — role-aware "Welcome back" page)**, and **media thumbnails + multi-image / drag-&-drop upload (#9)**, and the **home-page redesign (#10b — hero text/CTA overlay + compact service-times strip + three photo feature bands)**. The **§2.2 church-admin review fixes shipped in v3.4** (two batches — see §2.2), and the **admin help doc (#14) shipped in v3.5** (full HTML doc + per-section printing + a per-role capability matrix built from the real route/controller gates; reached via a single sidebar "Help" link). The remaining roadmap is the tail of content migration — the **old→new URL redirect map**, publishing the 21 draft pages, and running the wp-asset CLI before cutover — plus the settleumc.com gap items (#10/#13), site search and a searchable history page (#12/#12b), tests (#11), and a pre-launch checklist. The renovation follow-along (formerly #9.5) is **no longer a feature** — it will be a blog post. A small **church-history Books feature** also shipped in v3.7 (the 1976 *Our Church* reprint as a standalone cream-paper web edition at `/books/our-church`, linked from the History page) — a side feature outside the launch path. **The next session still targets the redirect map (#10).** The roadmap is in §10; the full per-version history is in `CHANGELOG.md`.

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

**All done, except page stats** * **Flesh out more useful Admin Dashboard "Welcome back" page with things like: notices of recent contacts, recent prayer requests, recent blog posts, stats of most used pages
**Done** * **Multi image upload capability under Admin/Media, maybe a drag & drop function for multiple images.
* **A user help document both as a printable PDF and as HTML pages with links from Admin functions to the appropriate section of the help page.
**Done** * **Calendar entries should show start and stop times on both the Upcoming Events cards and the full calendar. The calendar page should also have a day view, a list view and a link to subscribe to the Google Calendar (see https://settleumc.com/calendar/month/) for an idea on how the month vew should look and https://settleumc.com/calendar/list/ for how the list view show look.
**Done** * **More eye catching home page - See https://settleumc.com/
* **History page - I have 2 small books on the history of the church that I want to digital and put on the website as well as nay new historical information we come across. I want all of that to be searchable.  This would also be a place to put old pictures of the church and it's members.
* **We'll be starting a renovation project in a few months. I'll want a way the congregation can follow the work.  

## **2.2 Things to address after review with church admins**

All of the items raised in the church-admin review were resolved in two batches (v3.4); see §10 and `CHANGELOG.md` for the as-built detail.

* ✅ **Prayer Requests → multiple addresses + prayer-chain opt-in** (Batch 2). `prayer_notify_to` (and `contact_notify_to`) now accept a list of addresses (commas/new lines) via `Mailer::parseRecipients()`, one send per address. The public form gained an unchecked "share with our prayer-chain volunteers" opt-in (`allow_prayer_chain`, migration `0006`), forced off for private requests in browser + controller + model; surfaced to staff in the inbox/detail and in the team email.
* ✅ **Constant Contact mailing-list link, admin-configurable** (Batch 1). New `newsletter_signup_url` setting ("Social & apps" group); rendered as a "Join our mailing list" link in the footer Connect column when set.
* ✅ **Footer emphasizes the P.O. Box** (Batch 1). Mailing address now leads in bold; the physical street drops to a smaller "Visit us:" line beneath.
* ✅ **Staff email link works and stays hidden** (Batch 1). `EmailObfuscator::mailtoLink()` renders a real, entity-encoded `mailto:` (no JS dependency, address absent from the visible text and the raw source). The earlier JS-decoder approach (`href="#"` + `email-protect.js`) was abandoned — it failed silently when the script didn't run; that file is now orphaned.
* ✅ **Mobile menu scrolls** (Batch 1). The drawer is capped to the viewport below the sticky header (`max-height: calc(100dvh - var(--header-height-mobile))`, `overflow-y:auto`).
* ✅ **Blank password on user-create blocked** (Batch 1). Explicit empty-password guard in `UserController::validate()` (belt-and-suspenders over the existing length check).
* ✅ **Staff/admin phone links dial** (Batch 1, found while fixing the above). `PhoneFormatter::telHref()` returned bare digits; it now returns a proper `tel:` URI (also fixes the admin Contact-message view).

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
* **21 homepage slideshow photos** — **imported to the Media Library + slideshow (v3.2)**
* **3 section background images** (Im-New.jpg, Faith-Development.jpg, Worship.jpg) — **imported to the Media Library (v3.2); placed on the home page as the three feature bands (v3.3, #10b)**, resolved by `original_name` (`Section-*.jpg`)
* **10 staff portrait photos** — **imported & attached (v3.2);** the live roster actually carries **9** usable portraits (Jeff Keeley and Lori Roach have none) across **11** staff — see §17.7
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
| `media` | Uploaded photos (file metadata + alt text; `thumbnail_filename` added v3.0) |
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

Schema migrations live in `settle-private/sql/migrations/`. Six migrations to date: `0001_add_menu_items.sql`, `0002_add_post_categories.sql` (the `categories` and `post_categories` tables for the v2.0 blog), `0003_add_calendar_hidden.sql` (the `is_hidden` column on `calendar_events_cache` for the v2.3 `[hide]` tag), `0004_add_login_attempts.sql` (the v2.7 login-throttle table), `0005_add_media_thumbnail.sql` (the `thumbnail_filename` column on `media` for the v3.0 thumbnail variant), and `0006_add_prayer_chain_optin.sql` (the `allow_prayer_chain` column on `prayer_requests` for the v3.4 opt-in). **v1.9 added no schema migration** — both calendar tables already existed; the new `google_calendar` config block is config, not schema. Fresh installs run `schema.sql`; existing databases run migrations in order.

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
│   ├── Upload.php                     ← Upload validation, MIME detection, image resizing (GD); thumbnail variant + backfill (v3.0)
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
│   │   ├── DashboardController.php         ← enriched role-aware dashboard (v2.9, #8b)
│   │   ├── PagesController.php
│   │   ├── MediaController.php             ← media CRUD; uploadAjax() multi-upload JSON endpoint (v3.0)
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
│       ├── Media.php                        ← media model; thumbnail_filename + backfill helpers (v3.0)
│       ├── Slideshow.php
│       ├── Staff.php
│       ├── PrayerRequest.php
│       ├── ContactMessage.php
│       ├── MenuItem.php
│       ├── CalendarEvent.php              ← read-side; overlays overrides + filters `is_hidden`/`hide` (v1.9, v2.3).
│       ├── Post.php                       ← blog post model; scheduling-aware queries (v2.0); dashboardSummary (v2.9)
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
│       ├── home.php                       ← Upcoming Events widget (v1.9); tightened layout (v2.2); override-image card bg (v2.3); start–end range + day-view link (v2.8)
│       ├── page.php
│       ├── staff.php
│       ├── prayer.php
│       ├── contact.php
│       ├── calendar.php                   ← month grid: spanning multi-day bars (v2.8, #8a)
│       ├── calendar_list.php              ← upcoming list view, paginated (v2.8, #8a)
│       ├── calendar_day.php               ← single-day view (v2.8, #8a)
│       ├── _calendar_toolbar.php          ← Month/List switcher + subscribe (v2.8, #8a)
│       ├── _calendar_event_item.php       ← shared list/day event row (v2.8, #8a)
│       ├── blog.php                       ← listing + category archive (v2.0)
│       └── post.php                       ← single post; staff preview banner (v2.0)
├── bin/
│   ├── mail-test.php                  ← CLI SMTP smoke test (v1.8)
│   ├── calendar-sync.php              ← CLI calendar sync for cron (v1.9)
│   └── thumbnail-backfill.php         ← one-time thumbnail backfill for pre-#9 images (v3.0)
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
        ├── 0004_add_login_attempts.sql     ← login throttle table (v2.7)
        └── 0005_add_media_thumbnail.sql     ← media thumbnail_filename column (v3.0)
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
* ✅ **Staff Directory: full CRUD with public page** — card grid, formatted phones, obfuscated emails. **Bio display (v3.6):** the card-grid bio preview is CSS-clamped to **4 lines** (with a reserved min-height so short/no-bio cards still align and a 1-line title clamp), so a long bio can no longer inflate its grid row and leave neighbours with empty gaps. The full bio opens in a shared centred **modal** ("Read more" — shown only when the preview is actually truncated, detected client-side; opens with photo/name/title/full-bio/contact cloned from the card, closes on X / Esc / backdrop, focus-trapped, body scroll locked, honors `prefers-reduced-motion`). Progressive enhancement: with JS off the cards still render neatly (clamped) and the "Read more" buttons stay hidden. NEW `assets/js/staff-modal.js`; REPLACE `templates/public/staff.php`, `assets/css/theme.css`. No schema/route/controller change.
* ✅ **Books (church-history web editions, v3.7)** — a public `/books/{slug}` feature for long-form historical reprints, rendered with dedicated cream-paper book typography and kept off the Pages/TinyMCE surface. Content lives as trusted template fragments under `templates/public/books/` (an owner decision — fixed reprints, version-controlled, no admin-edit surface); a `BooksController` registry maps slug → fragment, a shared `book.php` chrome pulls a shared `_styles.php` once and includes the fragment, and everything renders through `PublicView::render()`. First title: *Our Church — A Story of a Hundred Years' Service* (1976). The `/books` library index is held until a second book exists (the registry already supports it). Not Features-flagged (always-on, like `/`); listed in the admin link-picker via `MenuController::buildUrlRegistry()`.
* ✅ **Prayer Requests: full intake + admin inbox** — anti-spam, role-gated privacy, audit-logged, email notification (private requests bodyless)
* ✅ **Contact Form: full intake + admin inbox** — anti-spam, conditional required fields, audit-logged, email forwarding
* ✅ **Email sending** — `\Settle\Mailer` over authenticated SMTP; contact + prayer notifications; `bin/mail-test.php`
* ✅ **Google Calendar integration (v1.9)** — `\Settle\GoogleCalendar` syncs a public calendar via API key into `calendar_events_cache` on a 15-min cron; failure-resilient. `\Settle\Model\CalendarEvent` overlays `calendar_event_overrides`. Public **`/calendar`** month-grid page (prev/next nav, today highlight, event chips anchoring to a details list) + homepage **Upcoming Events** widget surfacing `[featured]`-tagged events. Route gated by `Features::enabled('calendar')`; `/calendar` already in the menu URL picker.
* ✅ **Multi-author blog (v2.0)** — admin CRUD for posts with the `author`/`editor` split and **in-code ownership**; curated, editor-managed **categories** (many-to-many); featured image + inline images via Media Library + TinyMCE; **scheduled publishing** (future `published_at`, PHP-bound `:now` visibility) with signed-in **staff preview** of not-yet-live posts. Public `/blog` (paginated), `/blog/{slug}`, `/blog/category/{slug}`. Audit-logged; gated by `Features::enabled('blog')`; `/blog` in the menu URL picker.
* ✅ **Settings UI + Branding (v2.1)** — admin-only `/admin/settings`: one schema-driven sectioned form (Identity / Contact / Email notifications / Worship times / Social & apps / Homepage / Meta / Branding) editing church identity, notification routing, social/app links, homepage copy, SEO meta, logo/favicon/apple-icon (Media Library picker), and brand **colors**. Atomic server-side validation, CSRF, audited (`settings.update`). Brand colors apply via a hex-validated inline `<style>:root{…}</style>` override in the public layout, falling back to `theme.css`. Reuses `Settings::put()`; no schema change. §15's plan is now built.
* ✅ **User management (v2.4)** — admin-only `/admin/users`: list / create / edit / activate-deactivate / delete staff logins (author/editor/admin). Argon2id passwords (set on create, resettable on edit, min 12). In-code lockout rails (no self-role-change, no self-deactivate/-delete, never zero active admins) and FK-guarded delete (falls back to "deactivate instead"). A per-request `is_active` recheck in `Auth::check()` drops a signed-in user the moment they're deactivated. Audited (`user.*`). No schema change. Supersedes manual `seed_authors.sql` for ongoing management.
* ✅ **Self-service password reset (v2.5)** — public, always-on `/admin/forgot` (request) + `/admin/reset` (set new password). `PasswordResetController` + `templates/auth/{forgot,reset}.php`; four `User` reset methods; a "Forgot your password?" link on the login screen. Hashed single-use token (`sha256(raw)`, 15-min TTL, PHP-bound `:now`), non-enumerating + active-only, link host from `app.base_url`, minimal re-issue guard, audited (`user.password_reset_request`/`_complete`). No schema change (the reset columns already existed).
* ✅ **Calendar display enhancements (v2.8, #8a)** — month grid rebuilt as week rows with **spanning multi-day/all-day bars** (server-computed lanes in `PublicController::buildMonthWeeks()`, cross-week split, no JS) plus in-cell time+title single-day entries and a per-day **"+N More"** → day view. **`/calendar/list`** (paginated upcoming, 25/page) and **`/calendar/day/{date}`** views; a Month/List toolbar with **subscribe links** (Google + webcal/iCal, auto-hidden if the calendar id is unset). New `\Settle\CalendarFormat` helper (time labels / clean description / subscribe URLs); `CalendarEvent` gained `forRange`/`forDay`/`upcomingList`/`countUpcoming`; homepage cards show the start–end range and link to the day view. **List is the default on phones** (progressive-enhancement redirect; Month override), long titles wrap, the month shows the start–end range. No schema/config change.
* ✅ **Dashboard enrichment (v2.9, #8b)** — role- & `Features`-gated `/admin` landing: at-a-glance count cards (posts/media for authors; +prayer/contact/events/pages/staff for editors), recent-activity panels (recent posts with state badges; editor: new prayer + unread contact; admin: recent audit feed via `AuditLog::query()`), and quick actions. Additive `Post::dashboardSummary($viewerId,$isEditor)`. Private prayer requests are redacted to "Private request" on the dashboard. Retains the v2.7 limiter-health banner. No schema/route/config change.
* ✅ **Media thumbnails + multi-image upload (v3.0, #9)** — `\Settle\Upload` generates a small thumbnail (`THUMB_DIMENSION` = 600px long edge) next to each uploaded image and records it in the new `media.thumbnail_filename` column (migration `0005`). Generation lives in a shared public `Upload::makeThumbnail()`/`thumbPath()` (reused by the backfill): images already ≤600px reuse themselves (no second file), transparency is preserved, PDFs/unreadable files yield `NULL`, and a failure is best-effort (never fails the upload). Consumers: the admin grid, the editor picker (**preview only** — the inserted `data-url` stays full-size), and public blog cards (`Post::publishedList`/`publishedListByCategory` select `featured_thumbnail`; single-post/editor/admin queries unchanged); all fall back to full-size on `NULL`. `/admin/media` gains a drag-and-drop multi-file uploader posting one file per request to a JSON endpoint (`uploadAjax`, X-CSRF-Token header, per-file progress/error), with the single-file form retained as the no-JS fallback. `bin/thumbnail-backfill.php` (idempotent) backfills pre-#9 images. No auth/role change.
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

* ⏳ **Content migration / settleumc.com gap** (roadmap #10/#13). **In progress (v3.1):** the public nav was rebuilt as a **3-tier menu** and **21 pages** were created as **unpublished drafts** carrying migrated content — the welcome/info set (I'm New, Sundays, About, Directions & Parking, Weekly Schedule, Employment), the full **Connect** ministry tree (Children, Preschool, Parent's Day Out, Youth, Adult Ministries + Roadrunners, Missions + Mission Partners / Outreach / Faith Promises), and the **link pages** (Sermons, Watch, Give, Worship Bulletin, Newsletter) — all via idempotent **guarded-`UPDATE` content seeds** that only fill still-placeholder drafts. A one-time **`bin/migrate-wp-assets.php`** brings the pages' images and PDFs into the Media Library and rewires the links/embeds. **Remaining:** run that CLI **before DNS cutover** (the source URLs die at launch), review & **publish** the drafts, build the **old→new URL redirect map** (next session), and the still-undone #13 items (Sermons categorisation, Watch/livestream embed, mobile smart-banner). The slideshow / staff-portrait / section-background imports are a separate pass. Full as-built detail in **§17**; client decisions in §16.
* ⏳ **Site search** (roadmap #12, new v2.0). A `/search` page over published pages + posts + staff. The live settleumc.com has a header search box; this restores parity. Still deliberately deferred until content is populated. See §16.

### **Known design considerations not yet addressed**

* No automated tests in the repo yet. **Note (v1.9):** the calendar work was validated by ad-hoc PHP test harnesses (parser, override-overlay, and template-render assertions) run during development against SQLite; these were not committed. The v2.2 home pass was likewise validated by an ad-hoc render harness (21 assertions). PHPUnit + Playwright remain the intended permanent solution.
* No automated backup strategy documented
* ~~Image resizing on upload exists (long-edge 2000px) but no thumbnail variant generation~~ **DONE (v3.0, #9):** a ≤600px thumbnail is generated per image on upload (`media.thumbnail_filename`, migration `0005`), with `bin/thumbnail-backfill.php` for pre-#9 images. (Full-size resize at 2000px long edge unchanged.)
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
| ~~8a~~ | ~~Calendar display enhancements~~ | — | **DONE (v2.8).** Month grid rebuilt as week rows with **spanning multi-day/all-day bars** (server-computed lanes, cross-week split, no JS) + in-cell time+title singles + "+N More"→day view; **list view** `/calendar/list` (paginated 25/page); **day view** `/calendar/day/{date}`; **subscribe links** (Google + webcal). New `\Settle\CalendarFormat`; homepage cards show start–end range → day view. List default on phones; long titles wrap; month shows end time. No schema/config change. New routes only. |
| ~~8b~~ | ~~Dashboard enrichment~~ | — | **DONE (v2.9).** Role- & Features-gated admin landing: at-a-glance count cards, recent-activity panels (posts/prayer/contact; admin audit feed), quick actions. Additive `Post::dashboardSummary`. Private prayer redacted on the dashboard. Retains the v2.7 limiter banner. No schema/route/config change. |
| **8a** | **Calendar display enhancements** | 1 day | New, from §2.1. Start/stop times on Upcoming-Events cards AND the full calendar; **day view** + **list view** (see live `/calendar/month/` and `/calendar/list/`); a "Subscribe to Google Calendar" link. Self-contained extension of the now-complete calendar; times already cached — no schema change. |
| **8b** | **Dashboard "Welcome back" enrichment** | 0.5–1 day | New, from §2.1. Recent contacts, recent prayer requests, recent blog posts on the admin dashboard; optional most-used-pages stats (needs a tiny view counter). Low-risk quick win; models already exist. |
| ~~9~~ | ~~**Media: thumbnail variants + multi-image / drag-&-drop upload**~~ | — | **DONE (v3.0).** ≤600px thumbnail per image (`media.thumbnail_filename`, migration `0005`, shared `Upload::makeThumbnail`/`thumbPath`); admin grid + editor picker (preview only) + blog cards consume it, full-size fallback on `NULL`; `Post::publishedList`/`publishedListByCategory` gained `featured_thumbnail`. `/admin/media` drag-and-drop multi-upload (per-file JSON endpoint `uploadAjax`, X-CSRF-Token header, progress/error), single-file form kept as no-JS fallback. `bin/thumbnail-backfill.php` for legacy images. No auth/role change. |
| ~~9.5~~ | ~~Renovation follow-along~~ | — | **Dropped as a feature (v3.4, per owner).** With the multi-author blog already shipped, the congregation update is simply a blog post — no dedicated category or landing page needed. |
| 10 | **Migration of existing content** | in progress | **Underway (v3.1); #10b home redesign done (v3.3).** 3-tier nav + **21 draft pages** + content seeds (all guarded `UPDATE`s) + **`bin/migrate-wp-assets.php`** built. **Remaining (pre-cutover):** run the asset CLI **before DNS cutover** (source URLs die at launch), review & **publish** the 21 drafts, and build the **old→new URL redirect map** (next session — the mapping table gets owner sign-off before any `.htaccess` 301s). #13 gap items are a separate pass. See §17. |
| 11 | **Tests** | ongoing | PHPUnit for models/controllers; Playwright end-to-end. Commit the calendar + blog + home-render + audit + **rate-limiter (v2.7, 26 assertions)** + **calendar #8a (v2.8: 32-assertion data + 22-assertion render)** harnesses as a starting set. |
| 13 | **settleumc.com missing features / content migration gap** | scoping | New (v2.0). Sermons, Watch/livestream, Give, bulletin, newsletter, employment, ministry pages. Mostly Pages content + a few small features; client decisions resolved in §16 (Sermons/Newsletter/Bulletin stay Constant Contact / YouTube links, but the pages should be more attractive — Sermons with Live / Traditional / Shout / Special-Services categories). |
| 12 | **Site search** | 1 day | New (v2.0). `/search` over published pages + posts + staff (MySQL `LIKE` or FULLTEXT). Restores the header search the live site has. **Deliberately deferred until content is populated** (after #10/#13). See §16. |
| **12b** | **Searchable history page + photo archive** | scoping | New, from §2.1. Digitize the two church-history books + new historical material and old member/church photos, all **searchable**. Depends on **#12 (search)**, **#9 (thumbnails)**, and **#10 (content)** — so it sits after those. |
| **14** | **Admin help doc** | 1 day | **✅ Shipped v3.5.** One source of truth (`\Settle\Help`) rendered two ways: full HTML doc at `/admin/help` + per-section view at `/admin/help/{slug}` (printable alone). Includes a **per-role availability matrix** (admin / editor / author) driven from the actual route + controller role gates (so it reflects what the code enforces), and **print CSS** so it prints **in full or one section at a time** (HTML-first; browser print → PDF). **Owner decision:** a single sidebar "Help" link rather than per-screen contextual deep-links, to avoid touching the in-use admin section templates. |

**Recommended sequence (updated v3.4, per owner):** the contractual scope, the Settings/Branding UI (#4), the home-page design pass (#4c), the **calendar feature incl. the [hide] tag + override editor (#4b)**, **user management (#5)**, **self-service password reset (#6b)**, **audit-log viewer (#7)**, **login rate-limiting (#8)**, the **calendar display enhancements (#8a)**, the **dashboard enrichment (#8b)**, **media thumbnails + multi-image upload (#9)**, the **home redesign (#10b)**, and the **§2.2 church-admin review fixes (v3.4, Batches 1–2)** are all done. **Content migration (#10) remains:** run `bin/migrate-wp-assets.php` before cutover, review & **publish** the 21 drafts, and build the **old→new redirect map** — the redirect map is the **next-session** item (mapping table signed off before any `.htaccess`). The **admin help doc (#14) shipped in v3.5** (per-role matrix from the real route gates; full HTML doc + per-section printing; reached via a single sidebar "Help" link). **Renovation follow-along (formerly #9.5) is dropped as a feature** — it's a blog post now. **Site search (#12) stays deliberately deferred** until the site is fully populated, after which the **searchable history page (#12b)** can build on it. A **pre-launch checklist** comes last, before DNS cutover. (#11 tests is ongoing.) Full per-version detail for any item lives in `CHANGELOG.md`.

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

### **13.16 `str_replace` on a method tail can eat the next method's brace; prefer heredoc/line-splice for big blocks (NEW v2.8)**
Twice now, replacing a *trailing* block of a class (e.g. adding methods after the last existing one) via a single `str_replace` whose `old_str` ended at the class's closing `}` accidentally consumed the **preceding** method's `return …;` + closing brace, producing an "unexpected `public`/function" parse error a few lines below the edit. Two rules: (1) when appending methods, anchor `old_str` on a *complete* preceding method (include its full body **and** its closing brace in both `old_str` and `new_str`) rather than splicing at the bare class-closing `}`; or splice by line number (`head`/`tail` around a temp file). (2) For large PHP blocks containing `\DateTime`, `usort` closures, etc., write the block to a temp file with a **quoted heredoc** (`<< 'EOF'`) and line-splice it in — `str_replace` round-trips through JSON escaping and a stray `\` (e.g. `\DateTime`) is easy to get wrong; a quoted heredoc preserves backslashes literally and avoids the escaping guesswork. Both are cheap; both prevent a parse error that only shows up at lint time. (Related: §13.4 already prefers full-file replacement over `sed -i`.)

### **13.17 A later equal-specificity CSS rule wins — the mobile nav toggle showed at all widths (NEW v3.1)**
After the 3-tier menu went in, the header's hamburger / "Menu" toggle label was visible at **every** viewport width, not just on mobile. The cause was pure CSS **source order**: a desktop rule hid it (`.site-nav-toggle-label { display: none }`) *earlier* in `theme.css`, but a later base rule `.site-nav-toggle-label { display: inline-flex }` re-showed it — and at **equal specificity the last declaration wins**, so the unconditional rule overrode the media-query hide regardless of viewport. **Fix / rule:** move the hide into the **later** `@media (min-width: 1024px)` block so source order favors it (done in v2a's fix), rather than raising specificity or reaching for `!important`. When a media-query rule "doesn't take," look for a later same-specificity base rule before anything else. CSS/template only.

### **13.18 `Upload::handle()` is HTTP-upload-only — a CLI importer reuses the public bits and mirrors the rest (NEW v3.1)**
The content-migration asset importer (`bin/migrate-wp-assets.php`, §17) needed to store downloaded files exactly like an admin upload. `\Settle\Upload::handle()` **can't be called from CLI** — it gates on `is_uploaded_file()` and finishes with `move_uploaded_file()`, both of which only accept a genuine HTTP upload — and `maybeResize()` is `private`. So the importer **reuses the public surface** (`Upload::makeThumbnail()` / `Upload::thumbPath()` for the 600px thumbnail, and `Model\Media::create()` for the row) and **re-implements only the >2000px down-scale**, mirroring `maybeResize`'s GD calls and the JPEG/PNG/WebP quality constants. **Rule:** keep those mirrored constants in sync with `Upload`; if a future task needs the full store path from CLI, the cleaner fix is to extract a public `Upload::storeFromPath()` that both `handle()` and the CLI call (deliberately not done in v3.1 to avoid touching the hot upload path).

### **13.19 A CSS-margin gap between fly-out tiers is a hover dead-zone — bridge it, don't just offset (NEW v3.10)**
The 3rd-tier fly-out menu disappeared most of the time when the user moved toward it. CSS-only hover menus stay open only while the pointer is over the parent `.site-nav__item` (`:hover`/`:focus-within`); the moment it isn't, `display:none` closes the panel. The 3rd-tier rule positioned the side-fly-out at `left:100%` and then pushed it right with `margin-left:0.25rem`, so between the 2nd-tier panel's right edge and the 3rd-tier panel's left edge sat ~4px of **plain page that belongs to no hoverable element**. Travelling across it dropped the hover and snapped the panel shut. The 2nd tier never had this because it sits flush (`top:100%`, no offset). **Fix / rule:** when a fly-out must be visually offset from its parent, **fill the gap with a transparent pseudo-element inside the fly-out's own subtree** (`.site-nav__submenu .site-nav__submenu::before { content:""; position:absolute; left:-0.25rem; width:0.25rem; height:100%; }`) — hovering the bridge counts as hovering the parent item (descendant `:hover`), so the chain never breaks, and the visual gap is kept. Don't "fix" it by deleting the offset (loses the intended separation) or with `!important`. **Caveat:** this does **not** solve the diagonal "menu-aim" problem (a fast diagonal path can land on a sibling item and swap panels) — that requires JS hover-intent and was out of scope; the static gap was the dominant cause here.

---

## **17\. Content Migration (#10) — as-built (NEW v3.1)**

The bulk import of settleumc.com content. This section is the living record of how the new site's pages are populated; the per-batch narrative is in `CHANGELOG.md` (v3.1).

### **17.1 Page & menu model (confirmed facts)**
* **Public pages serve at `/page/{slug}`.** The router's `{param}` compiles to `([^/]+)`, i.e. **a single path segment** — so every slug is **flat and hyphenated** (`mission-partners`, not `connect/missions/partners`). Hierarchy is presentational only.
* **Navigation is data-driven** via `menu_items` (the v1.7 system, extended to **3 tiers** this pass — see §17.3). The page's own slug has nothing to do with its place in the menu tree.
* **`Page` (model + CRUD) uses only** `slug, title, body_html, meta_description, show_in_nav, is_published, updated_by`. The schema's `parent_id` / `hero_media_id` / `menu_order` columns are **not read** by the page code — don't rely on them for nav or heroes.
* **Body renders in `<article class="prose">`** (`templates/public/page.php`); `body_html` is the one trusted, unescaped column (admin/TinyMCE-authored).
* **Content CTAs** use the theme's `.btn` (solid) and `.btn--ghost` (outline) classes; both already exist in `theme.css`.
* **Email convention holds in body content too:** ministry contacts route to `/staff` or `/contact` rather than embedding a raw `mailto:` (anti-harvesting, §3.5). Recoverable names are used by name; addresses are not.
* **`media.filename` → URL** is `'/uploads/' . ltrim($filename, '/')` with **no url-encoding** (§13.12); the same rule governs anything the migration embeds.

### **17.2 The guarded-`UPDATE` content-seed pattern**
Page **structure** (the 21 rows) is created once by `sql/seed_pages.sql` as **unpublished drafts** whose body is a placeholder containing the sentinel `Draft — content to be added` (em-dash U+2014). Each **content** seed then fills pages with:
```
UPDATE pages SET body_html = '…', meta_description = '…'
 WHERE slug = 'X' AND body_html LIKE '%Draft — content to be added%';
```
The `AND body_html LIKE '%…%'` guard means a seed **only fills a still-placeholder draft** — it never clobbers a page you've since edited or published, and it's **safe to re-run**. Pages **stay drafts** (`is_published` untouched) so every page is reviewed in `/admin/pages` before going live. Apostrophes are SQL-escaped by doubling (`Children''s`); every seed is validated against an in-memory **SQLite** harness (running the real `seed_pages.sql` first, then the content seed) with an explicit assertion count before packaging — the harness also catches any mis-escaped quote because a bad string fails the SQL parse.

### **17.3 Deliverables (this pass)**
* **3-tier menu support** — `theme.css` (nested-flyout CSS), `MenuController` (depth guard: `MAX_TIERS = 3`, `tierOf()` / `subtreeHeight()` via a `depthError`), `MenuItem`, `templates/admin/menu/index.php` (recursive render + JS), `templates/layout/public.php` (recursive public renderers).
* **`sql/seed_pages.sql`** — 21 draft pages + idempotent 3-tier menu wiring matching the live nav.
* **Nav-toggle source-order fix** (`theme.css`) — see §13.17.
* **`sql/seed_pages_content_2a.sql`** — the 5 **link pages** (Give, Watch, Sermons, Worship Bulletin, Newsletter).
* **Media "Copy link" button** (`templates/admin/media/edit.php`) — clipboard copy of a media item's public URL (clipboard API + `execCommand` fallback), so swapping URLs into page bodies isn't hand-typing hex filenames.
* **`sql/seed_pages_content_2b1.sql`** — the 6 **welcome/info pages** (I'm New, Sundays, About, Directions & Parking, Weekly Schedule, Employment).
* **`sql/seed_pages_content_2b2.sql`** — the 10 **Connect ministry pages** (Children, Settle Preschool, Parent's Day Out, Youth, Adult Ministries, The Roadrunners, Missions, Mission Partners, Mission Outreach, Faith Promises).
* **`bin/migrate-wp-assets.php`** — the one-time asset importer (§17.4).

Harness coverage: Batch 1 = 43 assertions, 2a = 33, Copy-link render = 7, 2b-1 = 28, 2b-2 = 40, asset-CLI helpers = 15. All passing; `php -l` (and `node --check` where JS changed) clean.

### **17.4 The asset-migration CLI (`bin/migrate-wp-assets.php`)**
A one-time server-shell tool (same family as `calendar-sync` / `thumbnail-backfill`). For each image/PDF the migrated pages still reference on the **old** WordPress site, it downloads it (curl, best-effort), stores it under `uploads/YYYY/MM/<hex>.<ext>` with the **`Upload` conventions** (≤2000px down-scale mirrored from `maybeResize`, 600px thumbnail via the real `Upload::makeThumbnail()`, allowed jpg/png/gif/webp/pdf, ≤10 MB), inserts a `media` row via `Model\Media::create()` (owner = first active admin), and **rewires the page**: PDFs get their wp-content URL swapped for the new local URL (link text kept); images are embedded by **replacing the unique `<!-- TODO image pass … -->` comment** I left as an anchor (Employment's banner is prepended — no anchor). **Idempotent:** an asset already in the Library (matched on `original_name`) isn't re-downloaded, and a page already rewired isn't touched (old URL gone / new URL already present), so nothing doubles. **Must run before DNS cutover** — the source URLs only exist while WordPress is live. See §13.18 for why it can't call `Upload::handle()`.

### **17.5 Decisions & flagged discrepancies (carried for the owner)**
* **201 vs 202 E. 4th St** — the Directions page (and its Google Maps pin) say **201**; the footer/settings say **202** (the §4-resolved value). Kept faithful to each source; **reconcile which goes where.**
* **Youth page** — the live page named a youth director (Cindy Palacios) who, per the §4 resolved staff data, is **no longer staff**, plus a flyer PDF with her email in the filename. **Both dropped;** the contact routes to the Staff directory (Jeff Keeley / Wesley Marcum). Add the current director's details when ready.
* **Obfuscated ministry emails** on several live pages were unrecoverable → routed to `/staff` or `/contact`. Aimee Keith is named on PDO (current staff).
* **Two Guest Survey links** existed on the live I'm New vs Sundays pages; both migrated pages use the I'm New one — **confirm the current link.**
* **wp-content re-host:** the asset CLI handles the page images + job/registration PDFs. **The Give page's Legacy-Giving-Guide.pdf was uploaded manually by the owner** — the CLI skips it; point that one link at the manual upload via the Media "Copy link" button.
* **Flyer images** (preschool info-sheet, "is hiring" banner) migrated **faithfully** per owner instruction, though they bake text into an image (consider real text later).
* **Slideshow (21) / staff portraits / section backgrounds (3)** were out of scope for the v3.1 content pass and shipped in the **v3.2 image pass** — see §17.7.

### **17.6 Remaining for #10**
1. **Run `bin/migrate-wp-assets.php`** (page-body images/PDFs) **and `bin/migrate-wp-images.php`** (slideshow/portraits/section-bg, v3.2) on the server **before cutover** — both pull from the live WordPress site. `seed_staff.sql` must run before the latter.
2. **Review & publish** the 21 drafts in `/admin/pages` (they ship unpublished by design).
3. **Old→new URL redirect map** for cutover (owner-approved; next session).
4. **#10b** the "more eye-catching home page" design sub-pass is **done (v3.3, §17.8)** — hero overlay + service strip + three photo feature bands; the 3 imported section backgrounds (Im-New / Faith-Development / Worship) now back those bands.
5. The **slideshow/staff/section-bg** image import pass is **done (v3.2, §17.7)**; the remaining **#13** gap items stand (Sermons categorisation, Watch/livestream embed, mobile smart-banner).

---

## **17.7 Image pass — Slideshow / Staff portraits / Section backgrounds (#10 tail) — as-built (NEW v3.2)**

The image assets deferred out of v3.1 (§17.5). These live in the admin **surfaces** (Slideshow, Staff) and the Media Library, not in page bodies — so they ship as a **staff seed + a sibling import CLI**, not as content seeds.

### **17.7.1 Source map (scraped from the live site, owner-approved)**
The live WordPress site was scraped for the exact `wp-content/uploads/` filenames:
* **Slideshow:** the homepage hero carries exactly **21** Flickr-numbered JPGs; imported in live display order, no per-slide caption (the "I'm New / Sundays / Contact" overlay is the hero CTA, not slide text), generic alt "Settle Memorial church life".
* **Staff:** `/listings/staff/` lists **11** people. **9** have a usable portrait; **Jeff Keeley** and **Lori Roach** have none (they fall back to `silhouette.svg`). Per the owner, **all 9 available portraits are imported** — including the four 768×432 landscape graphics (Alecia Meyer, Kim Massey, Chris Tolliver, Wesley Marcum), which are not portrait-cropped and may want replacing later. The §4 "10 portraits" predates the live site's drift.
* **Section backgrounds:** `Im-New.jpg`, `Faith-Development.jpg`, `Worship.jpg` (names from the §4 inventory; they are CSS backgrounds on the old site, so not re-confirmed by scrape — the importer's download is best-effort and reports a 404 if a name is wrong).

### **17.7.2 Deliverables**
* **`sql/seed_staff.sql`** — the **11 staff rows** in live order (`sort_order` 10–110, `is_visible = 1`), idempotent via `INSERT … SELECT … WHERE NOT EXISTS (full_name)` (the §17.2 pattern). **Titles** are verbatim from the live roster (Mark Dickinson = "Senior Pastor"; **Libby Kassinger** had no title → NULL). **Emails are seeded NULL** — the live site obfuscates them (Cloudflare email-protection), so they were unrecoverable; add them in `/admin/staff`. **Run this first.**
* **`bin/migrate-wp-images.php`** — a one-time importer, **sibling to `bin/migrate-wp-assets.php`** and same family as `calendar-sync` / `thumbnail-backfill`. Three buckets: (a) **slideshow** — import each image, then `Model\Slideshow::create()` a slide (dedup: skip if a `slideshow_slides` row already references that media); (b) **portraits** — look up the staff row by `full_name`, then **guarded attach** `UPDATE staff SET photo_media_id = :mid WHERE full_name = :n AND photo_media_id IS NULL` (never clobbers a hand-set photo; notes a missing row so you know to run the seed first); (c) **section bgs** — import to the Media Library only.

### **17.7.3 Conventions reused / mirrored**
Per §13.18, the CLI **cannot call `Upload::handle()`** (HTTP-upload-only). It reuses the public surface — **`Upload::makeThumbnail()`/`thumbPath()`** for the 600px thumbnail and **`Model\Media::create()`** for the row — and re-implements only the **>2000px down-scale**, mirroring `Upload::maybeResize()`'s GD calls + quality constants (kept in sync deliberately). Reuse/idempotency dedup is on **`media.original_name`** (slideshow images get tidy `Slideshow-NN.jpg` names; portraits get `First-Last.ext`). `uploaded_by` = first admin (else lowest user id). Best-effort downloads (a failure is logged + skipped, never half-writes a row). **Must run before DNS cutover** while the source URLs are alive.

### **17.7.4 Run order & owner follow-ups**
1. `sql/seed_staff.sql` (phpMyAdmin) — creates the 11 rows.
2. `php settle-private/bin/migrate-wp-images.php` on the server, **before cutover**.
3. **Add staff emails** in `/admin/staff` (seeded NULL). Set **Libby Kassinger's title** if you have one.
4. The 9 portraits include 4 non-portrait landscape graphics — swap for real headshots when available.
5. The 21 slides import **active**; reorder/curate in `/admin/slideshow` to taste.
6. Section backgrounds were staged in the Library by this pass and are now placed on the home page by **#10b** (v3.3, §17.8) as the three feature bands.

### **17.7.5 Validation**
A 33-assertion SQLite/GD harness (not committed): `seed_staff.sql` rows/titles/NULL-handling/sort-order/idempotency; `mwi_map()` shape (21/9/3) + every portrait name cross-checked against the seed + Jeff/Lori absent; the mirrored down-scale (3000×1500→2000×1000, ≤2000 untouched, PNG-alpha path); and the DB write semantics (guarded portrait attach is a no-op on re-run; slide dedup). All passing; `php -l` clean. **No schema change** (staff/slideshow_slides/media tables already existed). **No code/route/template change** — seed + CLI only.


## **17.8 Home-page redesign (#10b) — as-built (NEW v3.3)**

The "more eye-catching home page" sub-pass (building on the #4c design pass). Scope chosen with the owner: **option 2 (additive)** — keep the existing band skeleton and enrich it — plus the one structural idea lifted from the option-3 mockup, the service-times strip. The new home flow is: **photo hero with a text/CTA overlay → compact crimson service-times strip → trimmed welcome band → three photo feature bands → Upcoming Events (unchanged) → "Get in touch" CTA (unchanged)**.

### **17.8.1 What shipped**
1. **Hero overlay** — an eyebrow + heading + sub-line + two CTAs (`Plan Your Visit` → `/page/im-new`, `Watch Online` → `/page/watch`) centered over the slideshow on a dark scrim (same rgba-black treatment as the existing `.slideshow__caption`). `z-index:3` (above the slides' `2`, below the dots' `4`); the overlay is `pointer-events:none` with `pointer-events:auto` re-enabled on the buttons, so the dot controls stay clickable. The same copy/CTAs render on the no-slides `.hero--empty` fallback. Heading/sub-line are **editable in Settings** (`homepage_hero_heading`, `homepage_hero_subheading`).
2. **Service-times strip** — a compact full-width crimson band directly under the hero, fed by the existing `worship_*` settings; only populated services render. The contemporary service is labeled **"Shout!"** here, and a trailing `(SHOUT!)` the stored `worship_contemporary` value carries is stripped **for display only** (settings untouched; the footer still shows it). The old "This Sunday" worship-card grid was **removed** from the welcome band (single source of truth for the times); the welcome band is now eyebrow + heading + lead only.
3. **Three photo feature bands** — `I'm New` (`/page/im-new`), `Grow in Faith` (`/page/connect`), `Worship With Us` (`/page/sundays`). Backgrounds come from the v3.2-staged Library assets, resolved by `original_name` (`Section-Im-New.jpg` / `Section-Faith-Development.jpg` / `Section-Worship.jpg`) via the new **`Media::findByOriginalNames()`** and built with the §13.12 URL convention (`/uploads/` + `ltrim`, no encoding). A missing image degrades to `.feature-band--plain` (solid ink), so the page is correct **before** `migrate-wp-images.php` runs and just gets richer once the photos land. Labels/links are hardcoded (owner decision); only the images come from the Library.
4. **Connect landing page** — a new **published** `connect` page (slug `connect`) seeded in `seed_pages.sql` so the middle feature band has a target; its body links on to the ministry pages (which light up as those drafts are published in #10). The existing "Connect" nav item is a no-link dropdown parent, so the page is `show_in_nav = 0` and does not duplicate the nav.

### **17.8.2 Files**
**REPLACE:** `templates/public/home.php` (overlay, strip, feature bands, trimmed welcome), `assets/css/theme.css` (new `.hero__overlay` / `.service-strip` / `.feature-band*` block; **no new `:root` brand values** — reuses `--brand-primary`/`-ink`, `--font-display`, `--text-on-dark`, existing `color-mix()` shades), `src/Controller/PublicController.php` (`home()` resolves the three section backgrounds), `src/Model/Media.php` (+`findByOriginalNames()`), `src/Controller/SettingsController.php` (two fields in the "Homepage" group), `sql/seed_settings.sql` (two hero rows), `sql/seed_pages.sql` (Connect page). **No new files, no schema change, no migration, no route change, no config change.** The settings form template is schema-driven, so it needed no edit.

### **17.8.3 Validation**
A 49-assertion harness (not committed): `Media::findByOriginalNames()` against SQLite (hit/miss/dedup/empty-input); a `home.php` render in both hero states asserting the overlay copy + CTAs, the service strip with the three labels and the `(SHOUT!)` strip, the feature bands (image URL vs `--plain` fallback, correct hrefs), the trimmed welcome band (no worship cards / no "This Sunday" / no "Learn More"), and graceful degradation when no worship settings are set; the Connect `INSERT` executed against SQLite (published, hidden from nav, apostrophe escaping intact, idempotent re-run); and a `theme.css` guard that no new `--brand-*` custom property was introduced. All passing; `php -l` clean on every changed PHP file.

### **17.8.4 Owner follow-ups**
* The three feature-band photos only appear once **`bin/migrate-wp-images.php`** has run (it imports `Section-*.jpg`); until then the bands show as solid ink. The bands link correctly regardless.
* The Connect page body links to the ministry pages, which are still **drafts** — those links 404 until the #10 content migration publishes them. (Expected pre-launch.)
* If you want the "Connect" **nav** label itself clickable, point that dropdown parent at `/page/connect` in `/admin/menu`.
* Hero heading/sub-line are in **Settings → Homepage**; the service-strip times remain in **Settings → Worship times** (the `(SHOUT!)` suffix can now be dropped from the contemporary value since the strip labels it "Shout!").
