<?php
/**
 * Admin audit-log viewer (roadmap #7) — read-only.
 *
 * @var callable $e            HTML-escape helper (from View::render)
 * @var array    $rows         audit rows (id, user_id, actor_name, action, entity_type, entity_id, details, ip_address, created_at)
 * @var string[] $actions      distinct exact actions present in the log
 * @var string[] $prefixes     distinct action prefixes (segment before the first dot)
 * @var string[] $entityTypes  distinct entity types present in the log
 * @var array    $actors       all users (id, username, email, display_name, role, ...)
 * @var array    $selected     sticky filter values (action, entity_type, user_id, date_from, date_to)
 * @var array    $baseQuery    active filters as a query map (for pager links)
 * @var array    $pagination   page, per_page, total, total_pages, from, to
 *
 * NB: data is passed under these safe keys — never 'data'/'template'/'layout',
 * which collide with View::render's own parameters under EXTR_SKIP (§13.9).
 */

$roleLabel = ['admin' => 'Administrator', 'editor' => 'Editor', 'author' => 'Author'];

$fmtDt = static function (?string $dt): string {
    if (!$dt) return '';
    $ts = strtotime($dt);
    return $ts ? date('M j, Y g:i:s a', $ts) : (string)$dt;
};

// Compact, escaped key→value rendering of the decoded JSON details column.
$fmtDetails = static function (?string $json) use ($e): string {
    if ($json === null || $json === '') {
        return '<span class="muted">&mdash;</span>';
    }
    $decoded = json_decode($json, true);
    if (!is_array($decoded) || $decoded === []) {
        // Not an object/array (or empty) — show the raw text, escaped.
        return '<span class="muted">' . $e($json) . '</span>';
    }
    $parts = [];
    foreach ($decoded as $k => $v) {
        if (is_array($v)) {
            $v = implode(', ', array_map(static fn($x) => is_scalar($x) ? (string)$x : json_encode($x), $v));
        } elseif (is_bool($v)) {
            $v = $v ? 'true' : 'false';
        } elseif ($v === null) {
            $v = 'null';
        }
        $parts[] = '<span class="kv"><span class="kv-k">' . $e((string)$k)
                 . '</span>: ' . $e((string)$v) . '</span>';
    }
    return implode(' ', $parts);
};

// Build a pager URL carrying the active filters + a page number.
$pageUrl = static function (int $p) use ($baseQuery): string {
    $q = $baseQuery;
    $q['page'] = $p;
    return '/admin/audit?' . http_build_query($q);
};

$pg = $pagination;
?>
<style>
/* Scoped helpers for the audit viewer (admin-only screen). */
.audit-filters { background:#fff; border-radius:4px; padding:1em 1.25em; margin-bottom:1.25em; }
.audit-filters .row { display:flex; flex-wrap:wrap; gap:1em; align-items:flex-end; }
.audit-filters .field { display:flex; flex-direction:column; gap:0.25em; }
.audit-filters label { font-size:0.8em; font-weight:600; color:#555; }
.audit-filters select, .audit-filters input[type="date"] { padding:0.35em 0.5em; }
.audit-filters .actions { display:flex; gap:0.5em; align-items:center; }
table.list td.details { max-width:32em; }
table.list .kv { display:inline-block; margin-right:0.75em; white-space:nowrap; font-size:0.92em; }
table.list .kv-k { color:#555; font-weight:600; }
.audit-meta { color:#777; font-size:0.9em; margin:0 0 1em; }
.pager { display:flex; gap:0.5em; align-items:center; margin-top:1.25em; flex-wrap:wrap; }
.pager a, .pager span.cur { padding:0.35em 0.7em; text-decoration:none; border-radius:3px; }
.pager a { background:#fff; }
.pager span.cur { background:var(--brand-primary, #9E2A2B); color:#fff; }
.pager span.disabled { padding:0.35em 0.7em; color:#bbb; }
.mono { font-family:ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; font-size:0.92em; }
</style>

<h1 style="margin:0 0 0.5em;">Audit Log</h1>
<p class="audit-meta">
  A read-only record of security-relevant actions. Times are shown as recorded
  (server clock). Anonymous or system actions &mdash; and actions by a since-deleted
  user &mdash; show no actor.
</p>

<form method="get" action="/admin/audit" class="audit-filters">
  <div class="row">
    <div class="field">
      <label for="f-action">Action</label>
      <select name="action" id="f-action">
        <option value="">All actions</option>
        <?php if (!empty($prefixes)): ?>
          <optgroup label="Action groups">
            <?php foreach ($prefixes as $p): $val = $p . '.*'; ?>
              <option value="<?= $e($val) ?>"<?= $selected['action'] === $val ? ' selected' : '' ?>>
                All <?= $e($p) ?>.* actions
              </option>
            <?php endforeach; ?>
          </optgroup>
        <?php endif; ?>
        <?php if (!empty($actions)): ?>
          <optgroup label="Specific actions">
            <?php foreach ($actions as $a): ?>
              <option value="<?= $e($a) ?>"<?= $selected['action'] === $a ? ' selected' : '' ?>>
                <?= $e($a) ?>
              </option>
            <?php endforeach; ?>
          </optgroup>
        <?php endif; ?>
      </select>
    </div>

    <div class="field">
      <label for="f-entity">Entity type</label>
      <select name="entity_type" id="f-entity">
        <option value="">All types</option>
        <?php foreach ($entityTypes as $t): ?>
          <option value="<?= $e($t) ?>"<?= $selected['entity_type'] === $t ? ' selected' : '' ?>>
            <?= $e($t) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="field">
      <label for="f-user">Actor</label>
      <select name="user_id" id="f-user">
        <option value="">Anyone</option>
        <?php foreach ($actors as $u): $uid = (string)(int)$u['id']; ?>
          <option value="<?= $e($uid) ?>"<?= $selected['user_id'] === $uid ? ' selected' : '' ?>>
            <?= $e($u['display_name'] !== '' ? $u['display_name'] : $u['username']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="field">
      <label for="f-from">From</label>
      <input type="date" name="date_from" id="f-from" value="<?= $e($selected['date_from']) ?>">
    </div>

    <div class="field">
      <label for="f-to">To</label>
      <input type="date" name="date_to" id="f-to" value="<?= $e($selected['date_to']) ?>">
    </div>

    <div class="field actions">
      <button type="submit" class="btn-primary">Apply</button>
      <?php if (!empty($baseQuery)): ?>
        <a href="/admin/audit">Clear</a>
      <?php endif; ?>
    </div>
  </div>
</form>

<?php if ($pg['total'] === 0): ?>
  <div style="background:#fff; padding:2em; text-align:center; border-radius:4px;">
    <p class="muted">
      <?= empty($baseQuery)
            ? 'No audit entries yet.'
            : 'No audit entries match these filters.' ?>
    </p>
  </div>
<?php else: ?>
  <p class="audit-meta">
    Showing <?= (int)$pg['from'] ?>&ndash;<?= (int)$pg['to'] ?> of <?= (int)$pg['total'] ?> entries.
  </p>

  <table class="list">
    <thead>
      <tr>
        <th>When</th>
        <th>Actor</th>
        <th>Action</th>
        <th>Entity</th>
        <th>Details</th>
        <th>IP</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
      <tr>
        <td class="mono" style="white-space:nowrap;"><?= $e($fmtDt($r['created_at'] ?? null)) ?></td>
        <td>
          <?php if (!empty($r['actor_name'])): ?>
            <?= $e($r['actor_name']) ?>
          <?php else: ?>
            <span class="muted" title="Anonymous or system action, or a since-deleted user">&mdash;</span>
          <?php endif; ?>
        </td>
        <td class="mono"><?= $e($r['action']) ?></td>
        <td>
          <?= $e($r['entity_type']) ?><?php
            if ($r['entity_id'] !== null && $r['entity_id'] !== '') {
              echo ' <span class="muted">#' . $e((string)(int)$r['entity_id']) . '</span>';
            }
          ?>
        </td>
        <td class="details"><?= $fmtDetails($r['details'] ?? null) ?></td>
        <td class="mono muted"><?= $e($r['ip_address'] ?? '') ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>

  <?php if ($pg['total_pages'] > 1): ?>
    <nav class="pager" aria-label="Audit log pages">
      <?php if ($pg['page'] > 1): ?>
        <a href="<?= $e($pageUrl($pg['page'] - 1)) ?>">&larr; Prev</a>
      <?php else: ?>
        <span class="disabled">&larr; Prev</span>
      <?php endif; ?>

      <span class="muted">Page <?= (int)$pg['page'] ?> of <?= (int)$pg['total_pages'] ?></span>

      <?php if ($pg['page'] < $pg['total_pages']): ?>
        <a href="<?= $e($pageUrl($pg['page'] + 1)) ?>">Next &rarr;</a>
      <?php else: ?>
        <span class="disabled">Next &rarr;</span>
      <?php endif; ?>
    </nav>
  <?php endif; ?>
<?php endif; ?>
