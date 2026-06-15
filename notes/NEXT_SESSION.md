# NEXT SESSION — Settle Memorial UMC

**Read `PROJECT_HANDOFF.md` (v3.5) first**, then clone fresh per §13.7
(`git clone --depth 1`, never the raw CDN) and cross-check sizes against
`urls-details.txt` before proposing changes.

> Doc structure unchanged: living state in `PROJECT_HANDOFF.md`, full
> per-version narrative in `CHANGELOG.md` (newest first). Content-migration
> as-built detail is **§17**. Pre-launch owner tasks are collected in
> `PRELAUNCH_CHECKLIST.md`.

> **Context:** going live is close, and **users are now editing content**
> in the admin panel — so the live admin surfaces are in active use. Avoid
> disruptive changes to admin templates.

---

## Just shipped (v3.5): admin help doc (#14)

One source of truth (`\Settle\Help`) rendered two ways:
- **`/admin/help`** — full single-page doc: TOC, per-role capability matrix,
  per-section print links, print CSS (one section per page).
- **`/admin/help/{slug}`** — one section on its own, printable alone.

Auth-required, **no role gate** (everyone may read help); reached via a single
**"Help"** link in the admin sidebar. The per-role matrix (Author / Editor /
Administrator) is transcribed from the **actual route role gates + in-code
ownership / `hasRole()` checks** (not the sidebar). Includes the "you may see
links you can't use -> Forbidden" expectation note and an account/forgot-password
section. **Owner decision:** single sidebar link, not per-screen deep-links, to
avoid touching the in-use admin section templates.

Full as-built detail in `CHANGELOG.md` (v3.5). Validated by a 90-assertion
render + matrix-accuracy harness, all passing; `php -l` clean.

### Deploy reminders for v3.5 (server)
1. `git pull`. **Then Restart PHP in cPanel** (stale opcache).
2. **Hand-edit `public_html/Settle/index.php`** — this file is delivered as a
   snippet, not a whole-file replace. Add the `HelpController` import with the
   other `use Settle\Controller\...;` lines, and the two `/admin/help` routes
   right after the `/admin` dashboard route. See `MANIFEST.txt`.
3. No SQL this release — **no migration, no schema/config change.**
4. Verify: sign in, click **Help** in the sidebar; confirm the matrix renders
   and "Print just this section" opens a single-section page.

---

## Next up — old->new URL redirect map (#10, finish content migration)

Every live settleumc.com path (`/new/`, `/adult/`, `/mission-service/`,
`/sermon-series/`, `/this-sundays-bulletin/`, `/news/employment-position/`,
`/listings/staff/`, the deep `/connect/...` tree) should 301 to its new home so
inbound links / SEO survive cutover. The live nav tree was captured in the v3.2
scrape (§17.7) and `urls.txt` / `urls-details.txt`.

- **Bring the full mapping table for owner sign-off BEFORE writing any
  `.htaccess` 301s** (owner's explicit instruction). Propose the mechanism too
  (`.htaccess` rules vs. a small in-router redirect table) with the table.

---

## After that — the tail
**#13** gap items (Sermons categorisation, Watch/livestream embed, mobile
smart-banner), **#11** tests, **#12** site search (deferred until content is
fully populated), then **#12b** searchable history. **Renovation follow-along
(formerly #9.5) is dropped as a feature** — it's a blog post now.

## Pre-launch (see `PRELAUNCH_CHECKLIST.md`)
Run both asset CLIs pre-cutover; publish the 21 drafts; reconcile 201 vs 202
E. 4th; confirm the Guest Survey link; add the current Youth director +
staff emails + Libby's title in `/admin/staff`; point the Give page's
Legacy-Giving-Guide.pdf link via Copy-link. **At launch:** flip `app.base_url`
+ the mail host to the live domain, then DNS cutover (§13.14).
