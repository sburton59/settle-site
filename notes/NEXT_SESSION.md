# NEXT SESSION — Settle Memorial UMC

**Read `PROJECT_HANDOFF.md` (v3.7) first**, then clone fresh per §13.7
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

## Just shipped (v3.7): church-history Books feature

A small public **`/books/{slug}`** feature serving long-form historical reprints as
standalone **cream-paper book editions**, kept off the Pages/TinyMCE surface so the
hand-set markup is never mangled. First title: the 1976 reprint
*Our Church — A Story of a Hundred Years' Service* at **`/books/our-church`**
(OCR-cleaned; original folios 3–28 kept in the margin; rosters as structured lists).

- **`BooksController`** = a `BOOKS` registry + `show()` (unknown slug → 404, like
  `PublicController::post()`); renders via `PublicView::render('public/book', …)`.
- Shared **`book.php`** chrome `require`s shared **`books/_styles.php`** once + `include`s the
  per-book content fragment — each book file is pure, trusted content.
- Content as **template fragments** under `templates/public/books/` (owner: fixed reprints,
  no admin-edit surface). Route is **public, no gate, not `Features`-flagged** (always-on).
- **`/books` library index deferred** until a 2nd book exists (registry already supports it).
- One-line `MenuController::buildUrlRegistry()` entry so the URL shows in the admin
  link-picker; public entry is the link on the (published) History page.

Validated by a **29-assertion** dispatch + render harness (notices = failures), all passing;
`php -l` clean. Full as-built detail in `CHANGELOG.md` (v3.7).

### Deploy reminders for v3.7 (server)
1. `git pull`. **Then Restart PHP in cPanel** (stale opcache).
2. **Hand-edit `public_html/Settle/index.php`** (delivered as a snippet, not a whole-file
   replace): add the `BooksController` import with the other `use Settle\Controller\…;`
   lines, and one `GET /books/{slug}` route mirroring the `/blog/{slug}` line (public, no
   gate). See `MANIFEST.txt`.
3. **No SQL this release** — no migration, no schema/config/settings change.
4. Verify: visit `/books/our-church` (renders the cream book inside the normal site
   header/footer); confirm the History page link resolves; an unknown slug → 404.

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
