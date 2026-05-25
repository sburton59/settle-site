# **Settle Memorial UMC Website Modernization — Project Handoff**

**Document version:** 1.2 **Date prepared:** May 24, 2026 **Purpose:** This document brings a new contributor (human or AI) fully up to speed on the project so work can continue without losing context.

**Changes in v1.2:** Homepage Slideshow shipped (drag-to-reorder admin). WYSIWYG editor (TinyMCE) shipped for Pages, with Media Library integration (browse-and-pick + drag-drop upload) and a Save-and-Preview workflow. Router now accepts CSRF tokens from an `X-CSRF-Token` header for JS-driven endpoints (in addition to the standard `_csrf` form field).

**Changes in v1.1:** Media Library shipped (moved from roadmap to working features). GitHub repo set up at `https://github.com/sburton59/settle-site`. Server now uses a symlink-based deploy from a GitHub clone — see §8 for the new workflow.

---

## **1\. Executive Summary**

Settle Memorial United Methodist Church (Owensboro, Kentucky) is replacing its existing WordPress site at **settleumc.com** with a custom-built PHP/MySQL application. The owner of this project has been engaged to design and build the replacement. The motivations are explicit and were stated in the original proposal: WordPress's attack surface, the licensing cost of premium plugins/themes, and the maintenance burden of a heavily customized install.

The new site is being built from scratch — no framework, no WordPress, no CMS dependency — using plain PHP 8.1+ and MySQL/MariaDB. The end result must be (a) secure, (b) inexpensive to maintain, and (c) operable by non-technical church staff through a clean admin panel.

Current status as of handoff: **Pages CRUD, Media Library, WYSIWYG editor, and Homepage Slideshow all working end-to-end.** A staff member can log in, edit pages with a real WYSIWYG editor, upload and manage images, and manage the homepage slideshow including reordering by drag-and-drop. Remaining proposal features (multi-author blog, Google Calendar, staff directory, prayer requests, contact form, public-side theming) are designed and database-modeled but not yet implemented. The roadmap is in §10.

---

## **2\. Original Proposal (Verbatim Recap)**

The original proposal commits the project to delivering:

* **Standard church information pages** (Home, About, Staff, Sermons, Contact, etc.) editable through a simple admin panel  
* **Photo management** — a media library and a rotating homepage slideshow that staff can refresh by uploading new images  
* **Multi-author staff blog** — several staff members can log in, write, edit, and publish posts independently, with inline images  
* **Google Calendar integration** — events live in a single Google Calendar; the website auto-syncs and displays the full calendar. Events tagged inside Google Calendar are featured on the homepage's "Upcoming Events" section. *One place to manage events; the website updates itself.*  
* **Secure password-protected admin panel** with individual staff logins  
* **No recurring software licensing fees**; church owns 100% of the code

These five capabilities are the contractual scope. The architectural choices below all flow from them.

---

## **3\. Architectural Decisions and Rationale**

### **3.1 No framework**

We use plain PHP with a tiny custom router, a PDO database wrapper, and PHP-as-templates. No Laravel, no Symfony, no Composer dependencies. Rationale:

* The proposal explicitly emphasizes eliminating third-party software risk. A framework reintroduces that risk class (Laravel security advisories, Composer supply-chain attacks, etc.).  
* The feature set is small enough that a framework would be overkill.  
* The total custom code is \~1500 lines and entirely auditable by one person in an afternoon.  
* Hosting is shared cPanel; Composer-based workflows add friction.

If the project later outgrows this approach, swapping to Slim or Laravel is feasible — the architecture is already MVC-shaped.

### **3.2 Two-tier directory layout (private code outside public\_html)**

The site lives in **two folders**:

* `public_html/Settle/` — the web-accessible folder. Contains ONLY the front controller (`index.php`), `.htaccess`, static assets, and the user-uploads directory.  
* `settle-private/` — sibling to `public_html/`, **outside the web root**. Contains all PHP source, templates, config (including DB password), logs, and SQL.

This is the standard modern-PHP layout. If Apache ever misconfigures and serves PHP as plain text, the database password remains physically unreachable from any URL.

A fallback "everything inside public\_html with .htaccess deny rules" layout was considered and rejected because it's strictly weaker.

### **3.3 Google Calendar as the single source of truth for events**

Events are NOT stored in the local database as the source of truth. They live in Google Calendar — the tool staff already know — and are pulled into a local cache table every 15 minutes. A separate `calendar_event_overrides` table holds website-only adjustments (force-feature, hide, attach custom image) without ever modifying the Google event.

The "featured event" mechanism is convention-based: staff add `[featured]` to the event title (or `#featured` on a line in the description). The sync job detects the tag, sets `is_featured = 1` in the cache, and strips the marker before display.

### **3.4 Three-tier role model**

`admin` \> `editor` \> `author`. Admins manage users and settings. Editors manage all content (pages, posts, photos, slideshow, staff, calendar overrides). Authors can write their own blog posts and upload to the media library, but cannot edit others' content.

The router enforces this with per-route `auth` and `role` middleware options.

### **3.5 Security baseline**

* Argon2id password hashing with auto-rehashing on login  
* Session-based auth with `session_regenerate_id(true)` on login  
* CSRF tokens on every POST (`hash_equals` comparison)  
* Strict cookie flags (`HttpOnly`, `SameSite=Lax`, `Secure` when HTTPS)  
* All DB access uses prepared statements; emulation disabled  
* HTML output is escaped through a template-local `e()` helper; HTML stored in `body_html` columns is trusted because only authenticated staff can write it  
* `.htaccess` blocks PHP execution in the uploads folder  
* Security headers (`X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`, `Permissions-Policy`)

### **3.6 Deployment via GitHub clone + symlinks**

The server does **not** host the canonical copy of the code. Instead:

* A clone of the GitHub repo lives at `~/settle-site-repo/`
* The Apache document root paths (`~/public_html/Settle/` and `~/settle-private/`) are **symlinks** into that clone
* Deploying a change is one command on the server: `cd ~/settle-site-repo && git pull`

This setup means:

* GitHub is always the source of truth. The server is provably identical to a specific commit.
* Rollback is `git checkout <commit>` — fast and reliable.
* No file-copying step that can go wrong or leave the server in a half-updated state.
* The deploy never touches the live config or uploaded files (which are gitignored and live alongside the repo).

A few non-obvious requirements that come with this layout:

* The clone directory must be `chmod 755` so Apache (running as a different user) can traverse into it. cPanel's Git Version Control creates new clones with `700` permissions by default, which causes a `403 Forbidden` until fixed.
* `settle-private/config/config.php` must be created on the server after clone (it's gitignored — copy `config.example.php` and fill in real credentials).
* Uploaded files live in `~/settle-site-repo/public_html/Settle/uploads/` (resolved through the symlink). They survive `git pull` because git only modifies tracked files.
* Line endings: `.gitattributes` enforces LF everywhere via `* text=auto eol=lf`. Don't bypass this — mixed CRLF/LF files in the repo cause confusing diffs and false "modified" status.

---

## **4\. Content & Asset Inventory**

A complete inventory was extracted from the existing settleumc.com WordPress site and lives in our chat history. The key assets:

* **Brand colors:** Deep crimson red (`#9E2A2B`) for header/nav; dusty rose accent in the script wordmark  
* **Logo:** `https://settleumc.com/wp-content/uploads/Settle-UMC-Logo.png`  
* **Favicon (32x32):** `https://settleumc.com/wp-content/uploads/cropped-Favicon-32x32.png`  
* **Apple touch icon (180x180):** `https://settleumc.com/wp-content/uploads/cropped-Favicon-180x180.png`  
* **21 homepage slideshow photos** — file list documented separately  
* **3 section background images** (Im-New.jpg, Faith-Development.jpg, Worship.jpg)  
* **9 staff portrait photos** — verified full-resolution URLs  
* **All page text content** — extracted and provided as copy-ready prose (About, I'm New, Sundays, Directions, Weekly Schedule, Children, Youth, Adult, Preschool, PDO, Watch, Give, Missions, Mission Partners/Outreach, Newsletter, Prayer Request, Roadrunners, Connect, Contact, Staff)  
* **11-person staff directory** with titles and emails  
* **Contact info:** (270) 684-4226; P.O. Box 1756, Owensboro, KY 42302; physical address 201 (or 202 — needs confirmation) E. 4th Street, Owensboro, KY 42303  
* **Social:** facebook.com/SettleMem, instagram.com/shoutatsettle, YouTube @settlememorialunitedmethod5839  
* **Mobile apps:** iOS `id1639009037`, Android `com.redpixelstudios.settleumc`

A PowerShell/bash download script for all these images was provided in the chat and should be re-run when assets are ready to be loaded into the new site.

**Known data discrepancies to confirm with the church:**

* Physical address: footer says 202 E. 4th, directions page says 201 E. 4th  
* Youth ministry contact: current Youth page says Cindy Palacios; staff listing shows Jeff Keeley and Wesley Marcum

---

## **5\. Database Schema Overview**

Twelve tables in MySQL 8 / MariaDB 10.5+, InnoDB engine, utf8mb4. Full DDL is in `settle-private/sql/schema.sql`.

| Table | Purpose |
| ----- | ----- |
| `users` | Admin/editor/author logins |
| `media` | Uploaded photos (file metadata \+ alt text) |
| `pages` | Static informational pages (About, Sundays, etc.) |
| `posts` | Multi-author blog entries |
| `post_media` | Junction for inline post images |
| `slideshow_slides` | Homepage rotating slideshow |
| `staff` | Staff directory cards |
| `calendar_events_cache` | Local cache of Google Calendar events |
| `calendar_event_overrides` | Website-only event adjustments |
| `settings` | Key/value site config (worship times, address, social links, Google Calendar ID, etc.) |
| `prayer_requests` | Submissions from the prayer request form |
| `contact_messages` | Submissions from the contact form |
| `sessions` | (optional, currently unused — PHP file sessions handle this) |
| `audit_log` | "Who did what when" trail |

Foreign keys are enforced. `ON DELETE` policies are chosen carefully (RESTRICT for owned content, SET NULL for optional images, CASCADE only where dependent rows can't exist standalone).

---

## **6\. Codebase Tour**

Directory layout (Option 1 from the architecture discussion — private code outside public\_html):

public\_html/Settle/                    ← Apache document root for settleumc.com  
├── index.php                          ← Front controller; routes all requests  
├── .htaccess                          ← URL rewrite \+ security headers  
├── assets/  
│   ├── css/admin.css                  ← Starter stylesheet (minimal, brand-aligned)  
│   └── js/admin.js                    ← Small UX helpers (auto-dismiss flash, unsaved warning)  
└── uploads/                           ← User-uploaded photos  
    └── .htaccess                      ← Blocks PHP execution in uploads

settle-private/                        ← Outside web root; not URL-accessible  
├── src/  
│   ├── bootstrap.php                  ← Loads config, sets up sessions, autoloader, DB  
│   ├── Database.php                   ← PDO singleton wrapper  
│   ├── Router.php                     ← Regex router with auth/role/CSRF middleware  
│   ├── Auth.php                       ← Login, logout, role checks (Argon2id)  
│   ├── Csrf.php                       ← Token generation and verification  
│   ├── View.php                       ← PHP-as-template renderer with layouts  
│   ├── Upload.php                     ← Upload validation, MIME detection, image resizing (GD)  
│   ├── Controller/  
│   │   ├── BaseController.php         ← Shared render/redirect/flash/input helpers  
│   │   ├── AuthController.php         ← Login/logout endpoints  
│   │   ├── DashboardController.php    ← Admin home screen  
│   │   ├── PagesController.php        ← Pages CRUD (fully implemented) + Save-and-Preview  
│   │   ├── MediaController.php        ← Media Library CRUD + picker iframe + editor-upload (fully implemented)  
│   │   ├── SlideshowController.php    ← Homepage slideshow CRUD + reorder + toggle (fully implemented)  
│   │   └── PublicController.php       ← Public homepage and /page/{slug}  
│   └── Model/  
│       ├── User.php                   ← User lookup, password updates, last-login touch  
│       ├── Page.php                   ← Page CRUD with slug uniqueness  
│       ├── Media.php                  ← Media library DB queries + pagination  
│       └── Slideshow.php              ← Slideshow CRUD + transactional reorder  
├── templates/  
│   ├── layout/  
│   │   ├── admin.php                  ← Sidebar \+ content shell  
│   │   └── public.php                 ← Public-facing shell (minimal so far)  
│   ├── auth/login.php  
│   ├── admin/  
│   │   ├── dashboard.php              ← Stub welcome screen  
│   │   ├── pages/  
│   │   │   ├── index.php              ← Pages list with hide/show toggle  
│   │   │   └── edit.php               ← Pages create/edit form, TinyMCE-powered  
│   │   ├── media/  
│   │   │   ├── index.php              ← Library grid + upload form, paginated  
│   │   │   ├── edit.php               ← Image preview + metadata form + delete  
│   │   │   └── picker.php             ← Iframe modal — used by TinyMCE and slideshow editor  
│   │   └── slideshow/  
│   │       ├── index.php              ← Drag-to-reorder list (SortableJS)  
│   │       └── edit.php               ← Add/edit form with image picker modal  
│   └── public/  
│       ├── home.php                   ← Stub homepage rendering the About page body  
│       └── page.php                   ← Generic public page renderer  
├── config/  
│   ├── config.php                     ← DB credentials, app name, session lifetimes (gitignored)  
│   ├── config.example.php             ← Template for config.php (committed)  
│   └── .gitkeep  
├── storage/  
│   ├── uploads/                       ← (Reserved; current uploads live in public\_html/Settle/uploads/)  
│   └── logs/                          ← PHP error log destination  
└── sql/  
    ├── schema.sql                     ← Full schema (all 12 tables)  
    └── seed.sql                       ← Initial admin user \+ sample pages

The router is the central nervous system. It loads middleware (auth, role), enforces CSRF on POSTs, and dispatches to controllers. New features are added by registering routes in `public_html/Settle/index.php` and writing the corresponding controller \+ model \+ template.

---

## **7\. What's Working vs. What's Stub/Missing**

### **Fully working end-to-end**

* ✅ Web-server routing (Apache `.htaccess` → `index.php` → router)  
* ✅ Database connection and PDO singleton  
* ✅ Session-based authentication (login, logout, "remember me," role checks)  
* ✅ CSRF protection on all POST forms  
* ✅ Admin layout shell with sidebar navigation  
* ✅ Login screen with error messaging  
* ✅ Dashboard placeholder  
* ✅ **Pages: full CRUD** — list, create, edit, save, hide/show. Validates slug uniqueness and format. Auto-updates `updated_by` and `updated_at`.  
* ✅ **WYSIWYG editor on Pages** — TinyMCE 7 loaded from jsdelivr CDN (open-source build, no API key). Toolbar: undo/redo, blocks (paragraph/H2/H3/quote), bold/italic, lists, link, image, Media Library picker, code. Drag-and-drop image upload routed through Media Library. Paste from Word/Google Docs is style-stripped. Save-and-Preview button opens the public page in a new tab after save.  
* ✅ **Media Library: full CRUD** — upload, browse (paginated grid), edit metadata (alt text + caption), delete. Server-side MIME detection, 10MB cap, auto-resize images down to 2000px on long edge using GD. Files stored under `uploads/YYYY/MM/<random>.<ext>`. PDF supported alongside JPEG/PNG/GIF/WebP. Authors can only delete their own uploads. Picker iframe template reused across TinyMCE and Slideshow editor.  
* ✅ **Homepage Slideshow: full admin CRUD** — add slide (pick from Media Library), edit (caption, link URL, active toggle), drag-to-reorder via SortableJS, delete. Reorder commits in a single transaction so partial failures don't leave the order half-applied. **Note:** admin side complete; public-side rendering will be added with the public theming work (item #4 on the roadmap).  
* ✅ Public-facing page rendering at `/page/{slug}`  
* ✅ Stub homepage that renders the About page body

### **Designed but not implemented in code**

These tables exist in the schema and screens were wireframed in the admin panel design, but no controllers, models, or templates exist yet:

* ⏳ **Blog Posts** (multi-author, with featured images and inline media). Schema: `posts`, `post_media`. Highest priority — the proposal calls this out specifically.  
* ⏳ **Staff Directory** management (the public-facing staff page exists conceptually but no admin UI yet). Schema: `staff`.  
* ⏳ **Google Calendar Integration** (sync job, calendar page, upcoming-events widget). Schema: `calendar_events_cache`, `calendar_event_overrides`. Requires a Google Calendar API key and either a cron job or scheduled-task hook.  
* ⏳ **Prayer Requests** (intake form \+ admin inbox). Schema: `prayer_requests`.  
* ⏳ **Contact Messages** (intake form \+ admin inbox). Schema: `contact_messages`.  
* ⏳ **Settings UI** (Settings table exists; needs an admin screen). Schema: `settings`.  
* ⏳ **User Management UI** (admin-only; add/remove users, reset passwords).  
* ⏳ **Activity Log viewer** (admin-only). Schema: `audit_log`. Note: nothing currently writes to `audit_log` — that needs hooking into the relevant controller actions when implemented.  
* ⏳ **Public-facing site theming** — the public templates are deliberately minimal. The real site needs the brand red, the script logo, the rotating slideshow (the data is now manageable in admin; the public render is still TODO), the upcoming-events strip, the footer with address and social links.

### **Known design considerations not yet addressed**

* No automated tests yet. PHPUnit would be the obvious choice for unit tests; for end-to-end browser tests Playwright is recommended.  
* No automated backup strategy documented.  
* Image resizing on upload exists (long edge capped at 2000px) but there's no thumbnail variant generation yet. The library currently uses the full-size image as the thumbnail in the grid, which is wasteful for very large images even after the 2000px cap.  
* No rate-limiting on login (the proposal-era plan mentions 5 attempts/15min lockout but it's not enforced yet).  
* No email sending configured (needed for password resets, prayer team notifications, contact form forwarding).

---

## **8\. Install & Deploy Workflow**

The site is hosted on cPanel shared hosting and deployed from GitHub via a clone-and-symlink pattern (see §3.6 for the architectural rationale).

### **8.1 First-time server install**

For a fresh setup on a new server (or recovering from disaster):

1. **Create the database.** cPanel → MySQL Databases. Create database `settleumc` and user `settleumc_app` (cPanel prefixes both with your account name). Grant ALL PRIVILEGES.  
2. **Clone the GitHub repo via cPanel.** Files → Git Version Control → Create. Clone URL: `https://github.com/sburton59/settle-site.git`. Repository Path: `~/settle-site-repo`. This creates the clone at `/home/<account>/settle-site-repo/`.  
3. **Open up directory permissions.** cPanel clones default to `chmod 700` which Apache can't traverse. SSH in and run `chmod 755 ~/settle-site-repo`. (Without this you'll get 403 Forbidden on every page.)  
4. **Create config.php.** Copy `~/settle-site-repo/settle-private/config/config.example.php` to `~/settle-site-repo/settle-private/config/config.php` and fill in the real DB credentials. Set `chmod 0640` for safety. This file is gitignored and never deployed automatically.  
5. **Import the schema.** phpMyAdmin → select database → Import → upload `~/settle-site-repo/settle-private/sql/schema.sql`.  
6. **Seed the first admin user.** Either import `seed.sql` if present, or insert manually. Generate a password hash with `php -r "echo password_hash('your-password', PASSWORD_ARGON2ID), PHP_EOL;"` and insert into `users` with `is_active=1, role='admin'`.  
7. **Create symlinks so Apache reads from the clone.** SSH in:
   ```bash
   # Move any pre-existing live folders aside
   mv ~/public_html/Settle ~/public_html/Settle.old   # if it exists
   mv ~/settle-private ~/settle-private.old           # if it exists
   # Create symlinks
   ln -s ~/settle-site-repo/public_html/Settle ~/public_html/Settle
   ln -s ~/settle-site-repo/settle-private ~/settle-private
   ```  
8. **Verify storage directories exist and are writable.** `~/settle-site-repo/settle-private/storage/logs/` must be writable for PHP error logging. `~/settle-site-repo/public_html/Settle/uploads/` must be writable for the Media Library.  
9. **Point the domain.** cPanel → Domains. Set the addon-domain document root to `public_html/Settle/` (which is now a symlink to the clone). Verify HTTPS is set up via Let's Encrypt.  
10. **Visit `https://yourdomain/admin/login`**, sign in, change the seeded password.

### **8.2 Deploy workflow (after first install)**

The day-to-day rhythm:

1. **Locally on Windows:** edit code in your repo clone (e.g. `C:\Projects\settle-site\`). Commit and push via GitHub Desktop. Pushing is a separate step from committing — make sure the "Push origin" button shows zero pending commits when you're done.  
2. **On the server (cPanel Terminal):**
   ```bash
   cd ~/settle-site-repo && git pull
   ```  
3. That's it. The symlinks resolve to the freshly-pulled code; the next page request serves the updated version.

### **8.3 Important workflow rules**

* **Never edit code directly on the server.** The clone tracks GitHub; any local edits will conflict with the next `git pull`. If you need to make a quick fix on the server, do it locally instead, push, then pull.
* **GitHub Desktop's "Commit to main" does not push.** Pushing is a separate click. The push button at the top of GitHub Desktop shows pending commit count; verify it reads "Fetch origin" (no pending) when you're done.
* **Line endings:** the `.gitattributes` file enforces LF everywhere via `* text=auto eol=lf`. Don't paste files from non-git editors that strip this; if files drift to CRLF, the diff against the repo becomes a wall of false positives.
* **Files outside git:** `config.php` and everything in `uploads/`, `storage/logs/`, `storage/uploads/` is gitignored and lives only on the server. These survive `git pull` because git doesn't touch untracked files.

---

## **9\. Coding Conventions**

* **PHP version:** 8.1+ required (uses `enum`, named arguments, `readonly` properties potentially, `declare(strict_types=1)` at the top of every file).  
* **Strict types** are declared at the top of every PHP file.  
* **Namespace:** `Settle\` for all source. File paths under `src/` mirror the namespace exactly (PSR-4-ish).  
* **Naming:** `PascalCase` for classes, `camelCase` for methods, `snake_case` for database columns.  
* **No global state** except the `$GLOBALS['settle_config']` config and the `Database` singleton. Everything else is dependency-free.  
* **Templates** are `.php` files in `settle-private/templates/`. They receive `$data` as local variables via `extract()`. Always escape output with `htmlspecialchars` or the provided `e()` helper, except `body_html` columns which are intentionally trusted.  
* **CSRF token field** is rendered via `\Settle\Csrf::field()` inside every `<form method="post">`. For JS-driven endpoints that POST JSON (e.g. the slideshow reorder), the token is sent in the `X-CSRF-Token` header instead — the Router accepts either source.  
* **Redirects** go through `BaseController::redirect()` to ensure consistent exit behavior.  
* **Database writes** always set `updated_by` to the current `$_SESSION['user_id']` for audit trail purposes.

---

## **10\. Prioritized Roadmap**

In rough order of value-to-effort. Time estimates assume a single developer (or AI-assisted human) working focused sessions.

| Priority | Feature | Est. Effort | Why |
| ----- | ----- | ----- | ----- |
| 1 | **Staff Directory CRUD \+ public page** | 1 day | Replaces existing site's staff page. Uses Media Library for photos. |
| 2 | **Prayer Requests form \+ admin inbox** | 0.5 day | Simple, proposal-promised. |
| 3 | **Contact form \+ admin inbox** | 0.5 day | Simple, proposal-promised. |
| 4 | **Public theming** (real homepage, header, footer, brand styles, slideshow render) | 2–3 days | Site needs to actually look like Settle Memorial. Public-side slideshow renders here. |
| 5 | **Google Calendar sync \+ display** | 2 days | Proposal-promised. Includes API setup, sync job, calendar page, upcoming-events widget. |
| 6 | **Blog Posts (multi-author CRUD \+ public listing)** | 2 days | Proposal-promised. Uses Media Library for inline + featured images. |
| 7 | **Settings UI** | 0.5 day | Removes the need to edit DB directly. |
| 8 | **User management UI** | 0.5 day | Currently requires DB edits. |
| 9 | **Email sending** (password reset, notifications) | 1 day | Needed for production. |
| 10 | **Audit log hooks \+ viewer** | 0.5 day | Wire `audit_log` table into write paths. |
| 11 | **Rate-limit login attempts** | 0.25 day | Simple security hardening. |
| 12 | **Thumbnail variants for Media Library** | 0.5 day | Currently grid uses full-size images. Generate 300px thumb on upload. |
| 13 | **Migration of existing content** | 1–2 days | Bulk-import current settleumc.com text \+ images into the new system. |
| 14 | **Tests** | ongoing | PHPUnit for models/controllers; Playwright for end-to-end. |

**Recommended sequence for the next session:** Staff Directory (#1) → Prayer Requests (#2) → Contact form (#3) → Public theming (#4). This finishes off all the admin features before doing the public-side visual work in one focused pass. Public theming will be where the slideshow finally renders for visitors.

---

## **11\. Open Questions for the Client**

These should be answered by the church before — or during — the next round of work:

1. **Which physical address is correct** — 201 or 202 E. 4th Street?  
2. **Who currently runs the youth ministry?** The current site lists conflicting names.  
3. **What's the church's Google Calendar ID?** Needed for the sync integration.  
4. **Will the church create a Google Cloud project \+ service account** for the Calendar API, or use an API key tied to a staff account?  
5. **What email provider** will the site use for transactional email (password resets, contact form forwards, prayer team notifications)? Options: cPanel's built-in mail, SendGrid free tier, Postmark, Mailgun.  
6. **Who will be the initial admins?** Confirmed assumption is Mark Dickinson and Alecia Meyer; confirm before seeding production.  
7. **Domain switchover plan:** Will we do a parallel deploy on a staging subdomain (e.g., `new.settleumc.com`) and cut over with a DNS swap, or build in place? Strong recommendation: parallel.  
8. **Mobile app dependency:** The existing site references iOS and Android apps from Red Pixel Studios. Are those staying? They may have their own integration with the old WordPress site that needs untangling.  
9. **Sermons archive:** The existing Sermons page is essentially a long list of YouTube video links going back to 2024\. Should the new site pull this dynamically from the YouTube channel, or maintain a manual list?  
10. **Newsletter archive:** Same question — the "Tidings" page links to \~2 years of weekly PDFs. Auto-list a folder, or manual?

---

## **12\. How to Use This Document in a New Claude Session**

When starting a new project session with this document loaded:

* Ask the new Claude to **read this document first** before any code work.  
* Source code lives at `https://github.com/sburton59/settle-site` (public repo). Claude can sometimes read files from GitHub directly via URL, but it's unreliable due to GitHub's robots.txt; the practical pattern is: when a bug surfaces, paste the specific file from the stack trace into chat.  
* For incremental work, the most effective prompt format is: *"I want to implement feature X from §10's roadmap. Please review the existing codebase to understand the conventions in `PagesController` and `Page` model (or `MediaController` for upload patterns), then propose the implementation."*  
* Always have Claude follow the conventions in §9 — strict types, prepared statements (with **distinct** named placeholders even when binding the same value — PDO with emulation disabled forbids reusing names), CSRF on POSTs, role-checked middleware, escaped output.  
* When making schema changes, update `schema.sql` AND create a migration script in a new `settle-private/sql/migrations/` folder so production can be upgraded incrementally rather than re-imported from scratch.  
* When delivering new files, hand them off as artifacts (downloadable). The user saves them locally in `C:\Projects\settle-site\`, commits via GitHub Desktop, pushes, and deploys with `git pull` on the server — no manual file copying onto the server.

