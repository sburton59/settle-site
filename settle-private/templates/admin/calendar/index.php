<?php
/** @var array $events  Rows from CalendarOverride::allEventsForAdmin() */

$esc = static fn($s): string => htmlspecialchars((string)$s, ENT_QUOTES);

// Date/time label for a cached event row (all stored in church-local time).
$whenLabel = static function (array $ev): string {
    try {
        $start = new \DateTime((string)$ev['starts_at']);
    } catch (\Throwable $e) {
        return (string)$ev['starts_at'];
    }
    if (!empty($ev['is_all_day'])) {
        return $start->format('D, M j, Y') . ' (all day)';
    }
    return $start->format('D, M j, Y \a\t g:i A');
};
?>
<div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:1em;">
  <h1 style="margin:0;">Calendar</h1>
  <a href="/calendar" target="_blank" style="text-decoration:none;">View public calendar &rarr;</a>
</div>

<p class="muted" style="margin-bottom:1.5em; max-width:60ch;">
  Events come from the church Google Calendar and sync automatically. To
  <strong>feature</strong> an event on the homepage, add <code>[featured]</code> to its
  description in Google Calendar; to <strong>hide</strong> it from the website, add
  <code>[hide]</code>. Here you can add a website-only <strong>image</strong> and a short
  <strong>public note</strong> shown beneath the event on the calendar page.
</p>

<?php if (empty($events)): ?>
  <div style="background:#fff; padding:2em; text-align:center; border-radius:4px;">
    <p class="muted">No events are cached yet. Once the Google Calendar sync runs, events appear here.</p>
  </div>
<?php else: ?>
  <ul style="list-style:none; padding:0; margin:0; display:grid; gap:0.6em;">
    <?php foreach ($events as $ev): ?>
      <?php $isHidden = !empty($ev['is_hidden']); ?>
      <li style="background:#fff; border-radius:4px; box-shadow:0 1px 3px rgba(0,0,0,0.05);
                 padding:0.7em 0.9em; display:flex; align-items:center; gap:1em;
                 <?= $isHidden ? 'opacity:0.6;' : '' ?>">

        <div style="flex-grow:1; min-width:0;">
          <div style="font-weight:500; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
            <?= $esc($ev['title']) ?>
          </div>
          <div class="muted" style="font-size:0.85em;">
            <?= $esc($whenLabel($ev)) ?>
          </div>
        </div>

        <!-- Status badges (read-only; driven by the Google Calendar tags) -->
        <div style="display:flex; gap:0.4em; align-items:center; flex-shrink:0;">
          <?php if (!empty($ev['is_featured'])): ?>
            <span title="Tagged [featured] in Google Calendar"
                  style="font-size:0.72em; background:var(--brand-primary,#9E2A2B); color:#fff;
                         padding:0.15em 0.6em; border-radius:1em;">Featured</span>
          <?php endif; ?>
          <?php if ($isHidden): ?>
            <span title="Tagged [hide] in Google Calendar"
                  style="font-size:0.72em; background:#555; color:#fff;
                         padding:0.15em 0.6em; border-radius:1em;">Hidden</span>
          <?php endif; ?>
          <?php if (!empty($ev['override_image_id'])): ?>
            <span title="Has a website-only image"
                  style="font-size:0.72em; border:1px solid var(--gray-200,#ddd); color:#555;
                         padding:0.15em 0.6em; border-radius:1em;">Image</span>
          <?php endif; ?>
          <?php if (($ev['override_notes'] ?? '') !== ''): ?>
            <span title="Has a public note"
                  style="font-size:0.72em; border:1px solid var(--gray-200,#ddd); color:#555;
                         padding:0.15em 0.6em; border-radius:1em;">Note</span>
          <?php endif; ?>
        </div>

        <div style="flex-shrink:0;">
          <a href="/admin/calendar/<?= (int)$ev['id'] ?>/edit"
             style="text-decoration:none; padding:0.3em 0.6em;">Edit image &amp; note</a>
        </div>
      </li>
    <?php endforeach; ?>
  </ul>
<?php endif; ?>
