# Settle Memorial UMC Website — Next Session Starter

**Copy and paste the text in the box below as your first message in the new chat session.**
The `PROJECT_HANDOFF.md`, `README.md`, and the repo files are already attached to the project,
so Claude has them automatically.

---

```
We're picking back up on the Settle Memorial UMC website rebuild. Before doing
anything else, read PROJECT_HANDOFF.md end-to-end — especially §7 (current
status), §9 (coding conventions), §10 (roadmap), and §13 (file-handoff
pitfalls). I'm not going to repeat any of that here; it should be your
single source of truth.

Quick status snapshot since the last session ended (May 25):
- Staff Directory shipped end-to-end. Full CRUD with drag-to-reorder,
  optional photo with silhouette fallback, optional bio, card-grid public
  page at /staff with EmailObfuscator and PhoneFormatter wired in.
- Two new reusable helpers: \Settle\EmailObfuscator (XOR-hex email
  protection + JS reveal, replaces Cloudflare email obfuscation) and
  \Settle\PhoneFormatter (US phone display normalization).
- README and PROJECT_HANDOFF.md are both at v1.3 as of today.

What I want to tackle in this session: **Prayer Requests** (roadmap #1).
The proposal-promised features were: a public intake form where visitors
can submit a prayer request, optionally marking it private, and an admin
inbox for staff to view, mark as prayed, and archive submissions. The
schema table `prayer_requests` is already in place — review
settle-private/sql/schema.sql to see its columns before proposing
anything.

The build pattern I want you to follow is exactly the one PagesController
and StaffController already use:
- A new model in settle-private/src/Model/PrayerRequest.php
- A new controller in settle-private/src/Controller/PrayerRequestController.php
- New templates for the admin inbox (list + detail) and the public intake form
- New route registrations in public_html/Settle/index.php
- Wire any displayed contact info through EmailObfuscator (don't render raw
  mailto: links on public pages)

Before writing any code, please:
1. Review the conventions PROJECT_HANDOFF.md §9 — strict types, distinct
   PDO placeholders, CSRF on every POST, role-checked middleware, escaped
   output, no raw mailto: in public templates.
2. Ask any open questions about scope. For example: should the public form
   be accessible without login? Should it have a honeypot or CAPTCHA against
   spam? Do private prayer requests show different metadata to admins?
3. Then propose the implementation before producing files. I'll greenlight
   it (or push back) and then you'll generate downloadables.

For file delivery, use the lessons from §13: give each downloadable a
distinct flat filename (e.g. MODEL-PrayerRequest.php, ADMIN-prayer-index.php,
PUBLIC-prayer-form.php) so the same Windows case-collision trap doesn't
bite us again. I'll rename at the destination.

Ready when you are.
```

---

## Notes for your own reference

**The next task itself: Prayer Requests (roadmap #1)**

This is intentionally small — should fit in a single session. The structural pieces:

1. **`PrayerRequest` model** with the standard `all()`, `find()`, `create()`, `updateStatus()` (new → prayed → archived), `delete()` methods.
2. **`PrayerRequestController`** with `index()` for the admin inbox, `show()` for a single request detail view, status-change actions, plus `publicForm()` and `submit()` for the visitor-facing intake.
3. **Templates:**
   - `admin/prayer/index.php` — list with status filters (default to "new"), count badges
   - `admin/prayer/show.php` — detail view with privacy indicator, prayed/archive buttons
   - `public/prayer.php` — intake form with name (optional), email (optional), private checkbox, request text
4. **Routes** — public GET/POST for the form, admin GET/POST for the inbox actions.
5. **Sidebar nav** — confirm the existing "Prayer Requests" link in `admin.php` now works.

**What to think through ahead of next session if you want:**

- Should anonymous submissions be allowed (name/email both blank)?
- What anti-spam protection feels right at this scale — honeypot field, time-on-page check, or nothing at all and rely on admin moderation?
- Should "private" requests be email-notified to a specific pastor rather than appearing in the general inbox? (This would need email sending, which is roadmap #8 and not yet built — so probably "no, just flag them and show a private indicator in the inbox.")
- How long should requests be retained — forever, or auto-archive after 30/60/90 days?

You can decide these in-session with Claude. None of them need to be locked down before starting.

**After Prayer Requests, the natural follow-on is Contact Form (#2)** — same shape, different table. Then **Public theming (#3)** — the big visual pass that finally makes the site look like Settle Memorial rather than a bare admin scaffold.
