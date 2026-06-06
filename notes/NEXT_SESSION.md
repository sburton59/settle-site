# NEXT SESSION — Settle Memorial UMC

**Read `PROJECT_HANDOFF.md` (v2.8) first.** Then clone fresh per §13.7
(`git clone --depth 1`, never raw CDN) and cross-check file sizes against
`urls-details.txt` before proposing changes.

---

## Just shipped (v2.8): Roadmap #8a — Calendar display enhancements

Delivered in `settle-roadmap8a-calendar-views.zip` (+ updated handoff):

- **Month grid redesigned** as week rows with **spanning multi-day / all-day
  bars** (server-computed lanes, cross-week split, no JS), single-day timed
  events as in-cell **time + title** entries, and **"+N More"** overflow
  (cap 3/cell) -> day view.
- **List view** `GET /calendar/list` — paginated upcoming events (25/page).
- **Day view** `GET /calendar/day/{date}` — one day's events + prev/next-day
  + back-to-month; invalid date -> `/calendar`.
- **Subscribe links** (Google + webcal/iCal), auto-hidden when the calendar
  id is unset.
- New `\Settle\CalendarFormat` helper; homepage cards now show the start-end
  range and link to the day view.
- **No schema change, no migration, no config change.**
- Validated: 32-assertion data harness + 22-assertion render harness, all pass.

### Deploy note
Plain file drop -> commit/push -> `git pull` on the server. **Nothing else to
run.** After pulling, spot-check `/calendar` (a multi-day event should draw
as one bar across its days), `/calendar/list`, and a `/calendar/day/...`.

---

## Next up: Roadmap #8b — Dashboard enrichment

A richer admin "Welcome back" landing page (`/admin`,
`DashboardController::index()` + `templates/admin/dashboard.php`). Low-risk
quick win; **propose first** as always.

Likely contents to scope with Steve (don't build until approved):
- **At-a-glance counts** — published vs. draft posts, upcoming events,
  pending prayer requests, staff entries, pages, media items.
- **Recent activity** — last few audit-log rows (the v2.6
  `\Settle\AuditLog::query()` read side already exists — reuse it, admin-only).
- **Quick links** — "New post", "Add event override", "Review prayer
  requests", etc.
- **Keep the v2.7 limiter health banner** (`rate_limiter_ok`) — it already
  lives on the dashboard; fold it into the new layout rather than dropping it.

Open questions to raise:
- Which counts matter most to Steve day-to-day?
- Should the activity feed be admin-only (audit data) while counts show for
  editors too? (Dashboard is currently role-aware.)
- Any per-role differences (admin vs. editor vs. author landing)?

### (!) Possible queue-jump: #9.5 renovation follow-along
Time-sensitive ("a few months out"). If the renovation start date is
approaching when the session opens, **confirm with Steve whether #9.5 should
preempt #8b.** #9.5 = a blog category + a landing page tracking renovation
progress (builds on the existing blog + media surfaces).

---

## Workflow reminders
- **Proposal-first.** Read current sources, surface open questions, propose a
  full plan; generate code only after Steve says "approve / yes / go".
- **Deliver** as a repo-mirroring zip + `MANIFEST.txt` (NEW/REPLACE +
  verification recipe); `php -l` clean; SQLite/render harness with an
  assertion count before packaging.
- **Whole-file replacement** (Steve replaces files wholesale); give hand-edit
  snippets only for route lines / sidebar badges.
- **Windows case-collision (§8.2/§13):** distinct flat filenames, prefixes,
  `core.ignorecase false`.
- **Time discipline (§13.8):** PHP-bound `:now`/`:today`, never SQL `NOW()`.
- **No new `:root` brand values (§9):** reuse existing CSS custom properties.
- **Session close:** produce updated `PROJECT_HANDOFF.md` + a new
  `NEXT_SESSION.md`.

## Pre-launch (carry-forward, do near DNS cutover)
- `app.base_url` and `google_calendar.calendar_id` must reflect the live host.
- Repo goes private near completion; Steve then switches to a
  paste-the-session's-files workflow (flat project-knowledge namespace).
