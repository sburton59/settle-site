# NEXT SESSION — Settle Memorial UMC

**Read `PROJECT_HANDOFF.md` (v3.0) first**, then clone fresh per §13.7
(`git clone --depth 1`, never the raw CDN) and cross-check sizes against
`urls-details.txt` before proposing changes.

> Doc structure unchanged since v2.9: living state in `PROJECT_HANDOFF.md`,
> full per-version narrative in `CHANGELOG.md` (newest first).

---

## Just shipped (v3.0): Roadmap #9 — Media thumbnails + multi-image upload

`settle-roadmap9-media-thumbnails.zip`. Two bundled deltas on the Media surface:

- **Thumbnails.** `\Settle\Upload` writes a <=600px long-edge thumbnail next to
  each uploaded image and records it in a new `media.thumbnail_filename` column
  (migration `0005`). The admin grid, the TinyMCE picker (preview only — the
  inserted URL stays full-size), and public blog cards render the thumbnail;
  `NULL` falls back to full-size (PDFs / pre-#9 rows unaffected). `Post`'s two
  public listing queries gained `featured_thumbnail`. New
  `bin/thumbnail-backfill.php` (idempotent) generates thumbnails for existing
  images — run once after the migration.
- **Multi / drag-and-drop upload.** `/admin/media` gains a drop zone (multi-file,
  per-file progress + per-file error) posting one file per request to a new JSON
  endpoint `POST /admin/media/upload-ajax` (`MediaController::uploadAjax`,
  X-CSRF-Token header). The single-file form is retained as the no-JS fallback.

1 new route (hand-edit), 1 migration, 1 backfill CLI, 10 files REPLACED.
Validated by a 29-assertion harness (GD thumbnail logic + SQLite Media model +
admin-grid/blog-card render), zero PHP notices, `php -l` + `node --check` clean.
Deploy: drop files → route hand-edit → push → `git pull` → apply `0005` →
run the backfill once.

---

## Next up — owner's call

**#9.5 Renovation follow-along (time-sensitive — confirm timing first).**
"Starting a renovation project in a few months… a way the congregation can
follow the work." Likely shape: a dedicated **blog category** ("Renovation")
plus a **landing page** pulling that category's posts (progress updates + photos
via the Media Library — which now produces thumbnails, so the landing page gets
light cards for free). Mostly reuses the blog + media surfaces. **Ask Steve at
session start whether the renovation start is near enough to do this now;** if
so it jumps ahead of #10.

Otherwise → **#10 Migration of existing content** (bulk-import current
settleumc.com text + images via Pages CRUD) **+ #10b home-page design sub-pass**
(the "more eye-catching home page" pass, building on #4c — wants a references
conversation before scoping) **+ #13 settleumc.com gap items** (Sermons with
Live/Traditional/Shout/Special-Services categories, Watch/livestream, Give link,
bulletin, newsletter, ministry pages — §16).

Either is **propose-first**: read current source, surface open questions, propose
a full plan, wait for "go".

---

## Roadmap tail (from §10)
#9.5 / #10 (+#10b) + #13 → #12 site search (deferred until content is populated)
→ #12b searchable history page + photo archive → #14 user help doc → pre-launch
checklist → DNS cutover. #11 tests is ongoing (harness set now: calendar, blog,
home-render, audit, rate-limiter, calendar #8a, dashboard #8b, **media #9**).

## Workflow reminders
- **Proposal-first**; code only after "approve / yes / go".
- Deliver one repo-mirroring **zip + MANIFEST.txt**; `php -l` clean; SQLite/
  render harness with an assertion count before packaging.
- **Whole-file replacement**; hand-edit snippets only for routes / sidebar.
- Windows case-collision (§8.2/§13): distinct flat filenames, prefixes,
  `core.ignorecase false`.
- Time discipline (§13.8): PHP-bound `:now`/`:today`, never SQL `NOW()`.
- No new `:root` brand values (§9): reuse existing CSS custom properties.
- `media.filename` → URL is `/uploads/` + `ltrim`, **no url-encoding** (§13.12);
  the new `thumbnail_filename` follows the same rule.
- For large PHP blocks, prefer a quoted-heredoc + line-splice over `str_replace`
  (§13.16).
- **Session close:** update `PROJECT_HANDOFF.md`, add the version's entry to
  `CHANGELOG.md`, and write the next `NEXT_SESSION.md`.

## Pre-launch (carry-forward)
- `app.base_url` and `google_calendar.calendar_id` must reflect the live host
  at DNS cutover (§8.7, §13.14).
- Repo goes private near completion → switch to the paste-the-session's-files
  workflow.
- Run `bin/thumbnail-backfill.php` against the live uploads if content is
  migrated before thumbnails are backfilled.
