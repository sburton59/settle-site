# NEXT SESSION — Settle Memorial UMC

**Read `PROJECT_HANDOFF.md` (v3.8) first**, then clone fresh per §13.7
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

## Just shipped (v3.8): second book + the `/books` library index

Added a second cream-paper title — **_Behind the Open Door_ (1995)**, a history of
the **Open Door Sunday School Class** — at **`/books/behind-the-open-door`**, and,
now that a 2nd book exists, finally wired the deferred **`/books` library index**.

- New content fragment `templates/public/books/behind-the-open-door.php` (set from
  the booklet PDF; web-cleaned — hyphens rejoined, scannos + the "EPLOGUE" heading
  fixed, original folios v/1–34 kept; the 1950s Minstrel section is **verbatim record**).
- New structures (asterisk **anecdote** vignettes, project **sub-headings**, a Camp
  Loucon **deeds** list, a **leaders** block) are handled by **additive-only** classes
  appended to `books/_styles.php` — every new selector is scoped under a new class (or
  `h3.subsec` / `.book__library`, which book 1 lacks), so **_Our Church_ is byte-identical**.
- **`/books` index** = `BooksController::library()` (lists the same `BOOKS` registry) +
  `templates/public/books_index.php` (a "shelf" of cards, `$e`-escaped) + the literal
  **`GET /books`** route registered **before** `GET /books/{slug}`. Both public, no gate,
  not `Features`-flagged. Three internal-link-picker entries in `MenuController`.

Validated by a **34-assertion** render + wiring harness (notices = failures), all passing;
`php -l` clean. Full as-built detail in `CHANGELOG.md` (v3.8).

### Deploy reminders for v3.8 (server)
1. `git pull`. **Then Restart PHP in cPanel** (stale opcache).
2. **Hand-edit `public_html/Settle/index.php`** (delivered as a snippet, not a whole-file
   replace): add **one** `$router->get('/books', [BooksController::class, 'library']);`
   line **immediately above** the existing `$router->get('/books/{slug}', …)` line. The
   `BooksController` import is already present from v3.7. See `MANIFEST.txt`.
3. **No SQL this release** — no migration, no schema/config/settings change.
4. Verify: `/books` lists both titles; `/books/behind-the-open-door` renders the cream
   book inside the normal header/footer; `/books/our-church` is unchanged; unknown slug → 404.
5. **Owner content edit (manual):** link the new book (and/or `/books`) from the published
   **History page**, the way _Our Church_ was linked — discoverability is not automatic.

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
