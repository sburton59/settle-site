# NEXT SESSION — Settle Memorial UMC

**Read `PROJECT_HANDOFF.md` (v3.2) first**, then clone fresh per §13.7
(`git clone --depth 1`, never the raw CDN) and cross-check sizes against
`urls-details.txt` before proposing changes.

> Doc structure unchanged: living state in `PROJECT_HANDOFF.md`, full
> per-version narrative in `CHANGELOG.md` (newest first). Content-migration
> as-built detail is **§17** (image pass = **§17.7**).

---

## Just shipped (v3.2): image pass — slideshow / staff portraits / section bgs

The image assets deferred out of v3.1 (§17.5), now done as a **seed + a sibling
import CLI** (they live in the Slideshow/Staff admin surfaces + Media Library,
not in page bodies). Source filenames were scraped from the live site and
owner-approved.

- **`sql/seed_staff.sql`** — 11 staff rows in live order, `sort_order` 10–110,
  `is_visible=1`, titles verbatim from the live roster, **emails NULL** (live
  site obfuscates them), Libby Kassinger title NULL. Idempotent
  `INSERT … WHERE NOT EXISTS (full_name)`. **Run FIRST.**
- **`bin/migrate-wp-images.php`** — sibling to `migrate-wp-assets.php`: imports
  21 slideshow JPGs (→ `Slideshow::create()` rows, in order, no captions),
  attaches 9 staff portraits (guarded `UPDATE … WHERE photo_media_id IS NULL`),
  and imports 3 section bgs to the Library only (staged for #10b). Jeff Keeley
  & Lori Roach have no live photo → silhouette fallback. **Run BEFORE cutover.**

Harness: 33 assertions, all passing. No schema/code/route/template change. See
HANDOFF §17.7.

### Owner action items from v3.2 (not code — do on the server / in admin)
1. Run `sql/seed_staff.sql` in phpMyAdmin (creates the 11 staff rows).
2. **Run `php settle-private/bin/migrate-wp-images.php` BEFORE DNS cutover**
   (the wp-content URLs die at launch). Run `migrate-wp-assets.php` too if you
   haven't (the page-body images/PDFs from v3.1).
3. In `/admin/staff`: **add each staff email** (seeded NULL) and **Libby
   Kassinger's title**. The 9 portraits include 4 landscape graphics (Alecia,
   Kim, Chris, Wesley) — swap for real headshots when you have them.
4. In `/admin/slideshow`: the 21 slides import **active** — curate/reorder.

---

## Next up — owner's call

**Default: the old→new URL redirect map (#10 finish).** Already approved in
principle. Goal: every live settleumc.com path (e.g. `/new/`, `/adult/`,
`/mission-service/`, `/sermon-series/`, `/this-sundays-bulletin/`, the
`/news/employment-position/` URL, `/listings/staff/`, the deep `/connect/...`
tree) 301s to its new home (`/page/{slug}`, `/staff`, `/calendar`, `/blog`,
external link, or a deliberate drop) so inbound links/SEO survive cutover.
The full live nav tree was captured this session (see the scrape in §17.7 /
the v3.2 chat) — propose the mapping table + the mechanism (likely `.htaccess`
rules or a small router redirect table) before building.

Alternatives if you'd rather:
- **#9.5 renovation follow-along** — jumps ahead if the project start is near
  (blog category + a progress landing page; gets thumbnail cards for free).
- **#10b** the "more eye-catching home page" design sub-pass — wants a
  references conversation first; the 3 section backgrounds are staged in the
  Library for it.

Either way: **propose-first** — read current source, surface open questions,
propose a full plan, wait for "go".

---

## Roadmap tail (from §10)
finish #10 (run both asset CLIs → publish drafts → redirect map → #10b) + #13
gap items → #9.5 (if timely) → #12 site search (deferred until content
populated) → #12b searchable history page + photo archive → #14 user help doc
→ pre-launch checklist → DNS cutover. #11 tests ongoing.

## Workflow reminders
- **Proposal-first**; code only after "approve / yes / go".
- Deliver one repo-mirroring **zip + MANIFEST.txt**; `php -l` clean; SQLite/
  render harness with an assertion count before packaging. Put a loud ⚠️ SQL /
  run-order reminder in the MANIFEST (seeds + CLIs are easy to forget to run).
- **Whole-file replacement**; hand-edit snippets only for routes / sidebar.
- Seeds: **guarded** (`INSERT … WHERE NOT EXISTS` / guarded `UPDATE`),
  apostrophes doubled, idempotent. Pages serve at **`/page/{slug}`** (flat slugs).
- A CLI can't call `Upload::handle()` (HTTP-only) — reuse the public bits,
  mirror the rest (§13.18).
- Time discipline (§13.8): PHP-bound `:now`, never SQL `NOW()`.
- No new `:root` brand values (§9); reuse existing CSS custom properties; watch
  CSS source order (§13.17).
- `media.filename` → URL is `/uploads/` + `ltrim`, **no url-encoding** (§13.12).
- Windows case-collision (§8.2/§13): distinct flat filenames, `core.ignorecase
  false`.
- **Session close:** update `PROJECT_HANDOFF.md`, add the version's entry to
  `CHANGELOG.md`, write the next `NEXT_SESSION.md`.

## Pre-launch (carry-forward)
- Run `bin/migrate-wp-assets.php` AND `bin/migrate-wp-images.php` (both
  pre-cutover); run `bin/thumbnail-backfill.php` if content lands before
  thumbnails are backfilled.
- `app.base_url` and `google_calendar.calendar_id` must reflect the live host at
  DNS cutover (§8.7, §13.14).
- Publish the migrated draft pages; confirm every live-site nav destination has
  a home (page, link, or a deliberate drop) — tie this to the redirect map.
- Add staff emails (+ Libby's title) in `/admin/staff`; curate the slideshow.
- Repo goes private near completion → switch to the paste-the-session's-files
  workflow.
