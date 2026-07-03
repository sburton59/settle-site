# NEXT SESSION — Settle Memorial UMC

**Read `PROJECT_HANDOFF.md` (v3.11) first**, then clone fresh per §13.7
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

## Just shipped (v3.11): Photo Albums (Flickr replacement)

Owner-requested gallery feature, built outside the roadmap sequence (same
pattern as Books) after the church described their current workflow: the
free tier of Flickr, ~40 albums, hundreds of photos each, needing
categorization and a filterable public page.

- **Schema (migration `0007`):** `photo_albums` + `album_media` mirror the
  categories/post_categories pattern. **A photo only shows in the public
  gallery once explicitly assigned to a published album** — logos, PDFs,
  and other Media Library files are never auto-included, satisfying the
  owner's requirement with no extra flag.
- **Roles (owner decision):** editor+ manage albums; **any author+ may
  assign any photo to any album** (not restricted to their own uploads —
  albums are event-based and span multiple photographers).
- **Public:** `/photos` (Flickr-style grid: cover, name, "Aug 2025" label
  from `event_date`, photo count, newest-first) + `/photos/{slug}`
  (paginated 40/page grid + a dependency-free click-to-enlarge lightbox).
- **Admin:** `/admin/albums` (editor+ CRUD, cover picker reusing the
  slideshow's media-id picker pattern); `/admin/media` gained an album
  filter, a caption/filename search box, and a multi-select "Add to
  album" bulk action (the main workflow for sorting a batch of event
  photos); the single-photo edit page gained an album checkbox fieldset.
- **`Media::paginate()`** gained optional `album_id`/`search` filters —
  needed regardless of the gallery, since the library is about to hold
  thousands of photos.
- Feature-flagged (`photo_albums`, depends on `media`); `/photos` in the
  menu URL picker; new CSS section "10. Photo Albums" in `theme.css`
  (reuses existing brand vars, no new `:root` values).
- Validated by a 53-assertion SQLite + render harness (not committed).
  `php -l` clean on every touched/new file.

Full as-built detail in `CHANGELOG.md` (v3.11).

### Deploy reminders (server)
1. `git pull`, then **run `sql/migrations/0007_add_photo_albums.sql`** in
   phpMyAdmin (new install skips this — `schema.sql` already has the
   tables). **Then Restart PHP in cPanel** (stale opcache).
2. Confirm `config.php`'s `features` array either omits `photo_albums`
   (fail-open default = on) or explicitly sets it `true`.
3. Verify: `/admin/albums` shows "New Album"; create one, add a photo or
   two from `/admin/media`'s new bulk-assign bar, publish it, confirm it
   appears at `/photos` with the right cover/date/count, and that
   `/photos/{slug}` paginates and the lightbox opens.
4. **Owner note:** the 40 existing Flickr albums are not migrated
   automatically — there's no Flickr export yet (confirmed with the
   owner). Photos will be re-uploaded and sorted into albums manually as
   time allows; not a pre-launch blocker.

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
fully populated), then **#12b** searchable history (photo-archive part is now
superseded by the Photo Albums feature; the searchable-history-book part is
still open). **Renovation follow-along (formerly #9.5) is dropped as a
feature** — it's a blog post now.

## Pre-launch (see `PRELAUNCH_CHECKLIST.md`)
Run both asset CLIs pre-cutover; publish the 21 drafts; reconcile 201 vs 202
E. 4th; confirm the Guest Survey link; add the current Youth director +
staff emails + Libby's title in `/admin/staff`; point the Give page's
Legacy-Giving-Guide.pdf link via Copy-link. **At launch:** flip `app.base_url`
+ the mail host to the live domain, then DNS cutover (§13.14).

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
