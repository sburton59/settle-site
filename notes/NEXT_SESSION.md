# NEXT SESSION — Settle Memorial UMC

**Read `PROJECT_HANDOFF.md` (v3.1) first**, then clone fresh per §13.7
(`git clone --depth 1`, never the raw CDN) and cross-check sizes against
`urls-details.txt` before proposing changes.

> Doc structure unchanged: living state in `PROJECT_HANDOFF.md`, full
> per-version narrative in `CHANGELOG.md` (newest first). Content-migration
> as-built detail is now **§17**.

---

## Just shipped (v3.1): Roadmap #10 — content migration (in progress)

Delivered as small, separately-validated batches; all pages staged as
**unpublished drafts** for review (never auto-published):

- **3-tier nav** + `sql/seed_pages.sql` — 21 draft pages + idempotent menu wiring.
- **Content seeds** (guarded `UPDATE`s; only fill still-placeholder drafts; safe
  to re-run): `seed_pages_content_2a.sql` (5 link pages), `…_2b1.sql` (6
  welcome/info pages), `…_2b2.sql` (10 Connect ministry pages).
- **`bin/migrate-wp-assets.php`** — one-time CLI: imports the pages' wp-content
  images/PDFs into the Media Library and rewires links/embeds. **Run BEFORE
  cutover.**
- **Media "Copy link"** button + a **nav-toggle CSS source-order fix**.

Harnesses: 43 / 33 / 7 / 28 / 40 / 15 assertions, all passing. See HANDOFF §17.

### Owner action items from v3.1 (not code — do on the server / in admin)
1. Run each content seed once in phpMyAdmin (if not already): `seed_pages.sql`
   then `seed_pages_content_2a / _2b1 / _2b2`.
2. **Run `php settle-private/bin/migrate-wp-assets.php` on the server BEFORE DNS
   cutover** (the old wp-content URLs die at launch).
3. **Review & publish** the 21 drafts in `/admin/pages`.
4. Point the **Give** page's Legacy-Giving-Guide link at your manual upload
   (Media → Copy link). Reconcile **201 vs 202** E. 4th. Confirm the current
   **Guest Survey** link. Add the current **Youth** director's contact.

---

## Next up — owner's call

**Default: the old→new URL redirect map (#10 finish).** Already approved in
principle. Goal: every live settleumc.com path (e.g. `/new/`, `/adult/`,
`/mission-service/`, `/sermon-series/`, `/this-sundays-bulletin/`, the `/news/`
employment URL, the deep `/connect/...` tree) 301s to its new home
(`/page/{slug}`, `/calendar`, `/blog`, external link, or a deliberate drop) so
inbound links/SEO survive cutover. Propose the mapping table + the mechanism
(likely `.htaccess` rules or a small router redirect table) before building.

Alternatives if you'd rather:
- **#9.5 renovation follow-along** — jumps ahead if the project start is near
  (blog category + a progress landing page; gets thumbnail cards for free).
- **#10b** the "more eye-catching home page" design sub-pass — wants a
  references conversation first.
- **Slideshow / staff-portrait / section-background image import** — the
  separate image pass deferred out of v3.1 (these live in the Slideshow/Staff
  admin surfaces, not page bodies).

Either way: **propose-first** — read current source, surface open questions,
propose a full plan, wait for "go".

---

## Roadmap tail (from §10)
finish #10 (run asset CLI → publish drafts → redirect map → #10b) + #13 gap
items → #9.5 (if timely) → #12 site search (deferred until content populated)
→ #12b searchable history page + photo archive → #14 user help doc → pre-launch
checklist → DNS cutover. #11 tests ongoing.

## Workflow reminders
- **Proposal-first**; code only after "approve / yes / go".
- Deliver one repo-mirroring **zip + MANIFEST.txt**; `php -l` clean; SQLite/
  render harness with an assertion count before packaging. Put a loud ⚠️ SQL
  reminder in the MANIFEST (seeds are easy to forget to run).
- **Whole-file replacement**; hand-edit snippets only for routes / sidebar.
- Content seeds: **guarded `UPDATE`** (only fill placeholder drafts), apostrophes
  doubled, pages left as drafts. Pages serve at **`/page/{slug}`** — slugs are
  **flat/hyphenated** (single path segment).
- Windows case-collision (§8.2/§13): distinct flat filenames, `core.ignorecase
  false`.
- Time discipline (§13.8): PHP-bound `:now`, never SQL `NOW()`.
- No new `:root` brand values (§9); reuse existing CSS custom properties; watch
  CSS source order (§13.17).
- `media.filename` → URL is `/uploads/` + `ltrim`, **no url-encoding** (§13.12).
- A CLI can't call `Upload::handle()` (HTTP-only) — reuse the public bits, mirror
  the rest (§13.18).
- **Session close:** update `PROJECT_HANDOFF.md`, add the version's entry to
  `CHANGELOG.md`, write the next `NEXT_SESSION.md`.

## Pre-launch (carry-forward)
- Run `bin/migrate-wp-assets.php` (pre-cutover) and `bin/thumbnail-backfill.php`
  against live uploads if content is migrated before thumbnails are backfilled.
- `app.base_url` and `google_calendar.calendar_id` must reflect the live host at
  DNS cutover (§8.7, §13.14).
- Publish the migrated draft pages; confirm every live-site nav destination has
  a home (page, link, or a deliberate drop) — tie this to the redirect map.
- Repo goes private near completion → switch to the paste-the-session's-files
  workflow.
