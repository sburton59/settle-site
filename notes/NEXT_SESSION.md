# NEXT SESSION — Settle Memorial UMC

**Read `PROJECT_HANDOFF.md` (v3.9) first**, then clone fresh per §13.7
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

## Just shipped (v3.9): cover-image buttons on the `/books` library

The library shelf now selects each book by its **scanned cover** instead of a
text card.

- Covers are **committed static assets** at
  `public_html/Settle/assets/img/books/{slug}.jpg` (served by Apache, off the
  Media Library — matching the Books feature's version-controlled, off-CMS
  stance), optimized to ~1000-px-tall progressive JPEG. Two shipped:
  `our-church.jpg`, `behind-the-open-door.jpg`.
- `BooksController::BOOKS` gained an optional **`cover`** key (absolute URL).
  `library()` already spreads the row, so it flows through unchanged.
- `books_index.php`: each shelf item is an `<a class="book-link">` with the
  cover `<img>` + a caption (title/subtitle/year, `$e`-escaped). A book with
  **no cover** degrades to the old bordered text card (`.book-link.no-cover`)
  — never a broken image.
- `books/_styles.php`: shelf restyled to a centered flex-wrap row, uniform
  cover **height** (300 px desktop / 220 px phone) with auto width so the two
  differing aspect ratios still line up; hover-lift + keyboard focus ring.

Validated by a **20-assertion** render harness (notices = failures), all
passing; `php -l` clean. Full as-built in `CHANGELOG.md` (v3.9).

### Deploy reminders for v3.9 (server)
1. `git pull`. **Then Restart PHP in cPanel** (stale opcache).
2. **No hand-edit, no SQL** — the v3.8 `/books` route is unchanged. Just make
   sure the new **binary cover images** under
   `public_html/Settle/assets/img/books/` actually land on the server (they're
   in the repo; confirm the pull brought them, not just the text files).
3. Verify: `/books` shows both covers as buttons; each opens its book; a
   book without a cover (none right now) would show a text card instead.
4. **Owner content edit (still pending):** link the new book and/or `/books`
   from the published History page — discoverability is not automatic.

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
