# NEXT SESSION — Settle Memorial UMC

**Read `PROJECT_HANDOFF.md` (v3.3) first**, then clone fresh per §13.7
(`git clone --depth 1`, never the raw CDN) and cross-check sizes against
`urls-details.txt` before proposing changes.

> Doc structure unchanged: living state in `PROJECT_HANDOFF.md`, full
> per-version narrative in `CHANGELOG.md` (newest first). Content-migration
> as-built detail is **§17** (image pass = **§17.7**, home redesign = **§17.8**).

---

## Just shipped (v3.3): home-page redesign (#10b)

The "more eye-catching home page" sub-pass. Scope agreed with the owner:
**option 2 (additive)** + the service-times strip from the option-3 mockup.
New home flow: **photo hero w/ text+CTA overlay → compact crimson service-times
strip → trimmed welcome band → three photo feature bands → Upcoming Events
(unchanged) → "Get in touch" CTA (unchanged)**.

- **Hero overlay** — eyebrow + heading + sub-line + two CTAs (`Plan Your Visit`
  → `/page/im-new`, `Watch Online` → `/page/watch`) on a dark scrim over the
  slideshow; `z-index:3` (dots stay clickable). Heading/sub-line editable in
  **Settings → Homepage** (`homepage_hero_heading`, `homepage_hero_subheading`).
- **Service-times strip** — crimson band from the `worship_*` settings; third
  service labeled **"Shout!"** with a trailing `(SHOUT!)` stripped for display
  only. The old "This Sunday" worship cards were removed from the welcome band.
- **Three feature bands** — I'm New / Grow in Faith / Worship With Us, backed by
  the v3.2-staged `Section-*.jpg` Library images (resolved by `original_name`
  via new `Media::findByOriginalNames()`); a missing image falls back to a solid
  ink band. Labels/links hardcoded.
- **Connect landing page** — new **published** `connect` page (seed_pages.sql)
  as the middle band's target; links on to the ministry pages.

REPLACE only (7 files): `home.php`, `theme.css`, `PublicController.php`,
`Media.php`, `SettingsController.php`, `seed_settings.sql`, `seed_pages.sql`.
No new files / schema / migration / route / config / `:root` change.
Harness: **49 assertions, all passing**; `php -l` clean. See HANDOFF §17.8.

### Owner action items from v3.3 (server / admin — not code)
1. Deploy: `git pull` on the server (and `chmod 755` the repo root if it's a
   fresh clone — cPanel defaults to 700 → 403).
2. **Re-run `sql/seed_settings.sql` and `sql/seed_pages.sql`** in phpMyAdmin —
   both are idempotent. seed_settings adds the two hero rows; seed_pages adds
   the published Connect page. (Existing rows are untouched.)
3. The three feature-band **photos appear only after
   `php settle-private/bin/migrate-wp-images.php` runs** (it imports the
   `Section-*.jpg` files). Until then the bands show as solid ink — the page
   is correct, just not yet photographic. Run it **before DNS cutover**.
4. The Connect page body links to ministry pages that are still **drafts** —
   those links 404 until #10 publishes them (expected pre-launch).
5. Optional: in `/admin/menu`, point the "Connect" dropdown parent at
   `/page/connect` if you want the nav label itself clickable.
6. Optional: in `/admin/settings` → Worship times, drop the `(SHOUT!)` suffix
   from the Contemporary value now that the strip labels it "Shout!".
7. Hero copy lives in `/admin/settings` → Homepage — edit the heading/sub-line
   there anytime.

---

## Next up — owner's call

**First: confirm #9.5 timing.** The **renovation follow-along** (a blog
category + a progress landing page; gets thumbnail cards for free from #9) is
**time-sensitive** — it should land before the renovation begins. **Confirm the
start date at the top of the session.** If it's near, do #9.5 next.

If #9.5 isn't yet timely, **finish #10 (content migration):**
- **Run the asset CLIs pre-cutover** (owner action, but verify): both
  `bin/migrate-wp-assets.php` (page-body images/PDFs, v3.1) and
  `bin/migrate-wp-images.php` (slideshow / portraits / section bgs, v3.2) must
  run while settleumc.com still serves the originals.
- **Review & publish the 21 draft pages** through `/admin/pages` (they carry
  migrated content but are `is_published=0`). Publishing them also lights up the
  Connect landing page's ministry links and the hero/band targets.
- **Build the old→new URL redirect map** — every live settleumc.com path
  (`/new/`, `/adult/`, `/mission-service/`, `/sermon-series/`,
  `/this-sundays-bulletin/`, `/news/employment-position/`, `/listings/staff/`,
  the deep `/connect/...` tree) 301s to its new home so inbound links/SEO
  survive cutover. The live nav tree was captured in the v3.2 scrape (§17.7).
  Propose the mapping table + mechanism (`.htaccess` vs. a small router redirect
  table) before building.

Then the tail: **#13** gap items (Sermons categorisation, Watch/livestream
embed, mobile smart-banner), **#11** tests, **#12** site search (deliberately
deferred until the site is fully populated), then **#12b** searchable history,
**#14** help doc, and the pre-launch checklist.

**At launch:** flip `app.base_url` + the mail host to the live domain, and do
the DNS cutover. (The reset-link origin and password-reset emails depend on
`app.base_url` — §13.14.)
