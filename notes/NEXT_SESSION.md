# NEXT SESSION — Settle Memorial UMC

**Read `PROJECT_HANDOFF.md` (v3.4) first**, then clone fresh per §13.7
(`git clone --depth 1`, never the raw CDN) and cross-check sizes against
`urls-details.txt` before proposing changes.

> Doc structure unchanged: living state in `PROJECT_HANDOFF.md`, full
> per-version narrative in `CHANGELOG.md` (newest first). Content-migration
> as-built detail is **§17**. Pre-launch owner tasks are collected in
> `PRELAUNCH_CHECKLIST.md`.

> **Context:** going live is close, and **users are now editing content**
> in the admin panel — so the live admin surfaces are in active use. Write
> the help doc to match what they're actually seeing, and avoid disruptive
> changes to admin templates.

---

## Just shipped (v3.4): §2.2 church-admin review fixes (Batches 1 & 2)

- **Batch 1 (no schema):** footer P.O.-Box emphasis; configurable Constant
  Contact newsletter link (`newsletter_signup_url`); mobile-menu scroll cap;
  explicit create-password guard; **no-JS staff email link** via
  `EmailObfuscator::mailtoLink()` (entity-encoded `mailto:`, "Email" label —
  the JS-decoder approach was abandoned after it dead-ended at `/staff#`);
  `PhoneFormatter::telHref()` now returns a real `tel:` URI.
- **Batch 2 (migration `0006`):** prayer-chain opt-in (`allow_prayer_chain`,
  forced off when private, surfaced to staff); multi-address notify for prayer
  & contact (`Mailer::parseRecipients()`, one send per address); `email_list`
  settings type; prayer-form repopulation fix (`data`→`values` key collision).

Full as-built detail in `CHANGELOG.md` (v3.4). Validated by SQLite / render /
DOM-shim harnesses (Batch 1: 19 + 32; Batch 2: 42), all passing; `php -l` clean.

### Deploy reminders for v3.4 (server / admin)
1. `git pull`. **Then Restart PHP in cPanel** — stale opcache was the likely
   reason a prior fix didn't take on the live site.
2. **Run migration `0006`** once in phpMyAdmin:
   `settle-private/sql/migrations/0006_add_prayer_chain_optin.sql` (idempotent).
   **Without it the prayer form errors on submit.**
3. Optional: `git rm public_html/Settle/assets/js/email-protect.js` (now unused).
4. Optional: in Settings, the "goes to" fields now take several addresses
   (one per line or comma-separated).

---

## Next up — two items, owner's stated priority

### 1. Old→new URL redirect map (#10, finish content migration)
Every live settleumc.com path (`/new/`, `/adult/`, `/mission-service/`,
`/sermon-series/`, `/this-sundays-bulletin/`, `/news/employment-position/`,
`/listings/staff/`, the deep `/connect/...` tree) should 301 to its new home so
inbound links / SEO survive cutover. The live nav tree was captured in the v3.2
scrape (§17.7) and `urls.txt`/`urls-details.txt`.
- **Bring the full mapping table for owner sign-off BEFORE writing any
  `.htaccess` 301s** (owner's explicit instruction). Propose the mechanism too
  (`.htaccess` rules vs. a small in-router redirect table) with the table.

### 2. Admin help doc (#14) — now scoped
A comprehensive help doc for the admin pages. Requirements (owner, this session):
- **One source of truth, rendered two ways:** contextual help **deep-linkable
  from each admin section**, AND **viewable as a single complete HTML doc**.
- **Per-role availability matrix** — clearly show which functions each user
  type (**admin / editor / author**) can use. Build the matrix from the
  **actual route + controller role gates** (read `public_html/Settle/index.php`
  route middleware and the in-code `Auth::hasRole()` checks) so it reflects what
  the code really enforces, not a guess. (See §3.4 role model; note the in-code
  per-post ownership pattern for authors.)
- **Print-friendly:** print CSS so it can be **printed in full or one section
  at a time** (HTML-first; browser print → PDF). Sensible page breaks per
  section.
- **Suggested approach:** inventory every admin function first (from the routes
  + admin layout nav + controllers), then propose the structure + the role
  matrix before building. Match the live admin UI users are already seeing.

---

## After those — the tail
**#13** gap items (Sermons categorisation, Watch/livestream embed, mobile
smart-banner), **#11** tests, **#12** site search (deferred until content is
fully populated), then **#12b** searchable history. **Renovation follow-along
(formerly #9.5) is dropped as a feature — it's a blog post now.**

## Pre-launch (see `PRELAUNCH_CHECKLIST.md`)
Run both asset CLIs pre-cutover; publish the 21 drafts; reconcile 201 vs 202
E. 4th; confirm the Guest Survey link; add the current Youth director +
staff emails + Libby's title in `/admin/staff`; point the Give page's
Legacy-Giving-Guide.pdf link via Copy-link. **At launch:** flip `app.base_url`
+ the mail host to the live domain, then DNS cutover (§13.14).
