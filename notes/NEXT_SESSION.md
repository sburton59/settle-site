# NEXT SESSION — Settle Memorial UMC

**Read `PROJECT_HANDOFF.md` (v2.9) first**, then clone fresh per §13.7
(`git clone --depth 1`, never the raw CDN) and cross-check sizes against
`urls-details.txt` before proposing changes.

> **Doc structure changed (v2.9):** the handoff was trimmed — the long
> per-version "Changes in vX" narrative now lives in **`CHANGELOG.md`**
> (full history, newest first). `PROJECT_HANDOFF.md` keeps the living state
> (architecture, schema §5, codebase tour §6, what's-working §7, conventions
> §9, roadmap §10, lessons §13) plus a compact version table up top. If you
> need the *why/how* of a past version, read `CHANGELOG.md`; for current
> design rules, the handoff sections are authoritative.

---

## Just shipped (v2.9): Roadmap #8b — Dashboard enrichment

`settle-roadmap8b-dashboard.zip`. Role- & `Features`-gated `/admin` landing:
at-a-glance count cards, recent-activity panels (recent posts w/ state badges;
editor: new prayer + unread contact; admin: recent audit feed), quick actions.
Additive `Post::dashboardSummary`; private prayer redacted on the dashboard;
v2.7 limiter banner retained. No schema/route/config change; 4 files REPLACED.
Validated by a 30-assertion SQLite + render harness (all roles, zero PHP
notices). Plain file drop → commit/push → `git pull`.

(Also shipped this session: **#8a calendar display** — spanning-bar month grid +
list/day views + subscribe links, with the post-review refinements: list
default on phones, wrapping titles, month start–end times, 25vw list image.)

---

## Next up — owner's call between two

**#9.5 Renovation follow-along (time-sensitive — confirm timing first).**
"Starting a renovation project in a few months… a way the congregation can
follow the work." Likely shape: a dedicated **blog category** ("Renovation")
plus a **landing page** that pulls that category's posts (progress updates +
photos via the existing Media Library). Mostly reuses the blog + media
surfaces — small new code. **If the renovation start is near, this jumps ahead
of #9.** Ask Steve at session start.

**#9 Media thumbnails + multi-image upload (bundled).** Generate a thumbnail
variant on upload (the grid + blog cards currently use full-size images) and
add multi-file / drag-and-drop upload to `/admin/media`. Touches `\Settle\Upload`
(GD resize already exists for the 2000px long-edge) and the Media admin UI.
Watch: this is the first feature to write new files to `uploads/` in a new
shape — keep the `media.filename` URL convention (`/uploads/` + `ltrim`, no
url-encoding, §13.12).

Either is **propose-first**: read the current source, surface open questions,
propose a full plan, wait for "go".

---

## Roadmap tail (from §10)
#9.5 / #9 → #10 content migration (+ #10b home-page design sub-pass) + #13
settleumc.com gap items → #12 site search (deferred until content is populated)
→ #12b searchable history page → #14 user help doc → pre-launch checklist →
DNS cutover. #11 tests is ongoing (harness set now: calendar, blog, home-render,
audit, rate-limiter, calendar #8a, dashboard #8b).

## Workflow reminders
- **Proposal-first**; code only after "approve / yes / go".
- Deliver one repo-mirroring **zip + MANIFEST.txt**; `php -l` clean; SQLite/
  render harness with an assertion count before packaging.
- **Whole-file replacement**; hand-edit snippets only for routes / sidebar.
- Windows case-collision (§8.2/§13): distinct flat filenames, prefixes,
  `core.ignorecase false`.
- Time discipline (§13.8): PHP-bound `:now`/`:today`, never SQL `NOW()`.
- No new `:root` brand values (§9): reuse existing CSS custom properties.
- For large PHP blocks, prefer a quoted-heredoc + line-splice over `str_replace`
  (§13.16).
- **Session close:** update `PROJECT_HANDOFF.md`, add the version's entry to
  `CHANGELOG.md`, and write the next `NEXT_SESSION.md`.

## Pre-launch (carry-forward)
- `app.base_url` and `google_calendar.calendar_id` must reflect the live host
  at DNS cutover (§8.7, §13.14).
- Repo goes private near completion → switch to the paste-the-session's-files
  workflow.
