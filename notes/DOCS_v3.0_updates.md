# Doc updates for v3.0 (roadmap #9) — paste into project knowledge

Two files to update: `CHANGELOG.md` (prepend the entry) and
`PROJECT_HANDOFF.md` (apply the targeted edits). `NEXT_SESSION.md` is delivered
whole as a separate file.

================================================================
1) CHANGELOG.md  — prepend this block (newest first)
================================================================

## v3.0 — 2026-06-06 — Media thumbnails + multi-image upload (#9)

Bundled because both touch the `\Settle\Upload` / Media Library surface.

**Thumbnails.** `\Settle\Upload` now generates a small thumbnail variant
(`THUMB_DIMENSION = 600` px long edge) next to each uploaded image and records
its relative path in a new `media.thumbnail_filename` column (migration
`0005_add_media_thumbnail.sql`, idempotent; column also added to `schema.sql`).
Generation lives in a public `Upload::makeThumbnail()` (+ `Upload::thumbPath()`)
so the uploader and the backfill share one code path. Rules: a source already
<= 600px reuses itself as its own thumbnail (no second file); transparency is
preserved for PNG/GIF/WebP; PDFs and unreadable files yield `NULL`; generation
is best-effort and never fails the upload (a `NULL` thumbnail simply falls back
to the full-size image at every consumer). Consumers updated: the admin Photos
grid, the TinyMCE image picker (preview `<img>` only — `data-url`, i.e. what is
inserted into a post, stays the FULL-SIZE image), and the public blog cards
(`Post::publishedList` and `Post::publishedListByCategory` now select
`m.thumbnail_filename AS featured_thumbnail`; the single-post hero, editor, and
admin queries are unchanged). `Media::create()` persists the column;
`MediaController::destroy()` deletes the distinct thumbnail file too.

**Backfill.** New `bin/thumbnail-backfill.php` (CLI, idempotent) regenerates
thumbnails for image rows uploaded before this feature, via the same
`Upload::makeThumbnail()`. Backed by two model helpers
(`Media::imagesWithoutThumbnail()`, `Media::setThumbnail()`). Run once after the
migration; safe to re-run (only touches `NULL`-thumbnail image rows).

**Multi-file / drag-and-drop upload.** `/admin/media` gains a drag-and-drop drop
zone (`#media-uploader`, in `templates/admin/media/index.php`) that accepts
several files at once and uploads ONE file per request to a new JSON endpoint
`POST /admin/media/upload-ajax` (`MediaController::uploadAjax`), with per-file
progress bars and per-file error reporting so one bad file doesn't sink the
batch. The CSRF token is read from the retained single-file form's hidden
`_csrf` field and sent as an `X-CSRF-Token` header (router accepts either). The
original single-file form (`#media-simple-form`) is kept as the no-JavaScript
fallback and hidden once the enhanced uploader initializes. New uploader module
appended to `admin.js` (inert unless `#media-uploader` is present); `.uploader*`
styles added to `admin.css` reusing the existing `--brand-*` / `--gray-*` vars.

No change to auth/roles (the AJAX endpoint mirrors the existing form upload's
"author or higher"). Animated GIF thumbnails are first-frame-only (GD).

Validation: 29-assertion harness (GD thumbnail logic + SQLite Media model +
admin-grid & blog-card render), zero PHP notices; `php -l` clean on all PHP;
`admin.js` passes `node --check`.

Files: REPLACE `src/Upload.php`, `src/Model/Media.php`, `src/Model/Post.php`,
`src/Controller/MediaController.php`, `templates/admin/media/index.php`,
`templates/admin/media/picker.php`, `templates/public/blog.php`,
`sql/schema.sql`, `assets/js/admin.js`, `assets/css/admin.css`.
NEW `sql/migrations/0005_add_media_thumbnail.sql`, `bin/thumbnail-backfill.php`.
HAND-EDIT one route line in `public_html/Settle/index.php`.


================================================================
2) PROJECT_HANDOFF.md — targeted edits
================================================================

--- EDIT A: bump the document version header ---
FROM: **Document version:** 2.9 ...
TO:   **Document version:** 3.0 ...

--- EDIT B: prepend a row to the "Recent changes" table (top of doc) ---
Add as the new first data row:

| 3.0 | 2026-06-06 | Media thumbnails + multi-image / drag-&-drop upload (#9) | §5, §7, §10 |

--- EDIT C: §5 schema — the media + migrations notes ---
In the table row for `media`, append to the Purpose cell:
" (thumbnail_filename added v3.0)".
After the sentence listing migrations `0001`–`0004`, add:
"`0005_add_media_thumbnail.sql` (the `thumbnail_filename` column on `media` for
the v3.0 thumbnail variant)."
Optionally, in the prose, note: `media` now carries a nullable
`thumbnail_filename` (relative `YYYY/MM/<rand>_thumb.<ext>`, or equal to
`filename` when the original is already <= the thumbnail size, or `NULL` for
PDFs / not-yet-backfilled rows).

--- EDIT D: §6 codebase tour — annotate touched files ---
- `Upload.php` line: "… image resizing (GD); thumbnail variant generation (v3.0)"
- `MediaController` (add if you track it): "+ uploadAjax() multi-upload JSON
  endpoint (v3.0)"
- Under `bin/`, add: "thumbnail-backfill.php  ← one-time thumbnail backfill for
  pre-#9 images (v3.0)"
- Under `sql/migrations/`, add: "0005_add_media_thumbnail.sql  ← media
  thumbnail_filename (v3.0)"

--- EDIT E: §7 "Fully working end-to-end" — add a bullet ---
* ✅ **Media thumbnails + multi-image upload (v3.0, #9)** — `\Settle\Upload`
  generates a <=600px thumbnail per image (`media.thumbnail_filename`, migration
  `0005`); the admin grid, the editor picker (preview only), and public blog
  cards render it, falling back to full-size when `NULL`. `/admin/media` gains a
  drag-and-drop multi-file uploader (one-file-per-request JSON endpoint
  `uploadAjax`, per-file progress/error) with the single-file form retained as
  the no-JS fallback. `bin/thumbnail-backfill.php` backfills existing images.

Also REMOVE the now-resolved bullet under "Known design considerations":
"Image resizing on upload exists (long-edge 2000px) but no thumbnail variant
generation" — replace with:
"~~no thumbnail variant generation~~ DONE (v3.0, #9): <=600px thumbnails on
upload + a backfill CLI for legacy images."

--- EDIT F: §10 roadmap — mark #9 done ---
Replace the `| 9 | Media: thumbnail variants + multi-image … |` row with a
struck-through DONE row, e.g.:

| ~~9~~ | ~~Media: thumbnail variants + multi-image / drag-&-drop upload~~ | — | **DONE (v3.0).** <=600px thumbnail per image (`media.thumbnail_filename`, migration `0005`, shared `Upload::makeThumbnail`); admin grid + editor picker (preview only) + blog cards consume it, full-size fallback on NULL. `/admin/media` drag-and-drop multi-upload (per-file JSON endpoint `uploadAjax`, progress/error), single-file form kept as no-JS fallback. `bin/thumbnail-backfill.php` for legacy images. No auth/role change. |

And update the "Recommended sequence" sentence: append #9 to the done list and
make the "Next is …" clause read "#9.5 renovation follow-along (if its start is
near) otherwise #10 content migration (+#10b) + the #13 gap items."

--- EDIT G (optional): §11 open questions ---
No change required. (#9 introduced no new client decision.)
