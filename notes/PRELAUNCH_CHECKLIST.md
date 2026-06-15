# Settle Memorial UMC — Pre-Launch Checklist

Owner action items to complete before (and at) DNS cutover. Compiled at v3.4.
Living detail is in `PROJECT_HANDOFF.md`; this is the actionable punch list.
Items marked **[BEFORE CUTOVER]** depend on the old WordPress site still being
live, or must be true before the public sees the new site.

---

## 1. Deploy the latest code
- [ ] `git pull` on the cPanel server.
- [ ] **Restart PHP** in cPanel (clears opcache — stale cache has masked
      deploys before).
- [ ] **Run migration `0006`** once in phpMyAdmin:
      `settle-private/sql/migrations/0006_add_prayer_chain_optin.sql`
      (idempotent; **the prayer form errors without it**).
- [ ] Re-run idempotent seeds if not already applied: `sql/seed_settings.sql`,
      `sql/seed_pages.sql` (existing values untouched).
- [ ] Optional cleanup: `git rm public_html/Settle/assets/js/email-protect.js`
      (now unused/orphaned).

## 2. Content migration **[BEFORE CUTOVER]**
- [ ] Run `php settle-private/bin/migrate-wp-assets.php` (page-body images/PDFs)
      while settleumc.com still serves the originals.
- [ ] Run `php settle-private/bin/migrate-wp-images.php` (slideshow, staff
      portraits, the three `Section-*.jpg` feature-band backgrounds).
- [ ] Review and **publish the 21 draft pages** in `/admin/pages` (they carry
      migrated content but are unpublished). Publishing lights up the Connect
      landing page's ministry links and the home feature-band targets.
- [ ] Build + apply the **old→new URL redirect map** (next dev session — the
      mapping table gets your sign-off before any `.htaccess`).

## 3. Content reconciliations & confirmations
- [ ] **201 vs 202 E. 4th St** — the live Directions page says 201, the footer
      says 202. Pick the correct one and make it consistent across pages.
- [ ] **Guest Survey link** — two different URLs existed on the live site;
      confirm the current one (both migrated pages currently use the "I'm New"
      one).
- [ ] **Give page** — point the `Legacy-Giving-Guide.pdf` link at your manual
      upload via the admin **Copy link** button (the migration CLI skips it
      because you uploaded it by hand).

## 4. Staff directory (`/admin/staff`)
- [ ] **Add staff emails** — they were seeded NULL (the old site obfuscated
      them, so they weren't recoverable by migration).
- [ ] **Libby Kassinger's title** — was NULL on the live site; fill it in.
- [ ] **Current Youth director** — the previously-named director is no longer
      staff; add the current person (interim contact currently routes to Jeff
      Keeley / Wesley Marcum).
- [ ] Optional: replace the 4 non-portrait landscape graphics (Alecia Meyer,
      Kim Massey, Chris Tolliver, Wesley Marcum) with portrait crops.

## 5. Settings (`/admin/settings`)
- [ ] **Constant Contact** — paste the mailing-list signup URL into
      "Social & apps → Mailing list signup" (footer link stays hidden until set).
- [ ] **Notify addresses** — "Contact form goes to" / "Prayer requests go to"
      now accept several addresses (one per line or comma-separated).
- [ ] Optional: drop the trailing `(SHOUT!)` from the Contemporary worship time
      now that the home service-times strip labels it "Shout!".
- [ ] Optional: hero heading/sub-line live in Settings → Homepage.
- [ ] Optional: point the "Connect" nav dropdown parent at `/page/connect` in
      `/admin/menu` if you want the label itself clickable.

## 6. At launch (DNS cutover)
- [ ] Flip **`app.base_url`** in `config.php` to the live `settleumc.com`
      (the password-reset link origin depends on it — §13.14).
- [ ] Flip the **mail host / SMTP** settings to the live domain's mailbox.
- [ ] Do the **DNS cutover**.
- [ ] Smoke-test after cutover: a prayer submission emails the team; the
      contact form emails; a staff "Email" link opens mail; phone links dial;
      the calendar shows events; login + password reset work.
