<?php
/**
 * Public calendar — month grid + chronological details list.
 *
 * @var int       $cal_year       Year being displayed
 * @var int       $cal_month      Month being displayed (1-12)
 * @var \DateTime $cal_current    First day of the displayed month
 * @var string    $cal_prev_ym    "YYYY-MM" for the previous month
 * @var string    $cal_next_ym    "YYYY-MM" for the next month
 * @var array     $events         CalendarEvent::forMonth() rows (chronological)
 * @var array     $events_by_day  [ 'Y-m-d' => [event, ...] ] for grid cells
 * @var array     $settings       From PublicView
 * @var array     $menu_tree      From PublicView
 * @var Closure   $e              htmlspecialchars helper
 *
 * All event datetimes are already in the church's local timezone (the
 * sync layer converts before storing), so this template does no tz math.
 */

$events       = $events ?? [];
$events_by_day = $events_by_day ?? [];

// ---- small local formatting helpers -------------------------------------

// Strip the featured keyword from a description for display.
$cfg      = $GLOBALS['settle_config']['google_calendar'] ?? [];
$keyword  = (string)($cfg['featured_keyword'] ?? '[featured]');
$hiddenKw = (string)($cfg['hidden_keyword'] ?? '[hide]');
$cleanDesc = static function (?string $desc) use ($keyword, $hiddenKw): string {
    if ($desc === null || $desc === '') {
        return '';
    }
    // Case-insensitive removal of the feature/hide keyword tokens.
    foreach ([$keyword, $hiddenKw] as $kw) {
        if ($kw !== '') {
            $desc = preg_replace('/' . preg_quote($kw, '/') . '/i', '', $desc) ?? $desc;
        }
    }
    return trim($desc);
};

// Time label for an event (handles all-day, single, and ranged).
$timeLabel = static function (array $ev): string {
    $start = new \DateTime((string)$ev['starts_at']);
    $isAllDay = !empty($ev['is_all_day']);
    if ($isAllDay) {
        $end = !empty($ev['ends_at']) ? new \DateTime((string)$ev['ends_at']) : clone $start;
        if ($end->format('Y-m-d') !== $start->format('Y-m-d')) {
            return $start->format('M j') . ' – ' . $end->format('M j') . ' · All day';
        }
        return 'All day';
    }
    $label = $start->format('g:i a');
    if (!empty($ev['ends_at'])) {
        $end = new \DateTime((string)$ev['ends_at']);
        if ($end->format('Y-m-d') === $start->format('Y-m-d')) {
            $label .= ' – ' . $end->format('g:i a');
        } else {
            $label .= ' – ' . $end->format('M j, g:i a');
        }
    }
    return $label;
};

// Build the grid: leading blanks for the weekday the 1st falls on, then days.
$firstDow   = (int)$cal_current->format('w');          // 0 = Sunday
$daysInMon  = (int)$cal_current->format('t');
$todayYmd   = (new \DateTime('today'))->format('Y-m-d');
$monthLabel = $cal_current->format('F Y');

$weekdays = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
?>

<section class="page-intro">
  <div class="container">
    <div class="eyebrow">Events</div>
    <h1>Calendar</h1>
  </div>
</section>

<section class="section">
  <div class="container">

    <div class="calendar-nav">
      <a class="btn btn--ghost calendar-nav__btn" href="/calendar?ym=<?= $e($cal_prev_ym) ?>" rel="prev">
        <span aria-hidden="true">&larr;</span> Prev
      </a>
      <h2 class="calendar-nav__title"><?= $e($monthLabel) ?></h2>
      <a class="btn btn--ghost calendar-nav__btn" href="/calendar?ym=<?= $e($cal_next_ym) ?>" rel="next">
        Next <span aria-hidden="true">&rarr;</span>
      </a>
    </div>
    <p class="u-text-center" style="margin-top: 0.5rem;">
      <a href="/calendar">Today</a>
    </p>

    <?php if ($events === []): ?>
      <p class="calendar-empty">No events scheduled for <?= $e($monthLabel) ?>.</p>
    <?php endif; ?>

    <!-- Month grid -->
    <div class="calendar-grid" role="table" aria-label="<?= $e($monthLabel) ?>">
      <div class="calendar-grid__head" role="row">
        <?php foreach ($weekdays as $wd): ?>
          <div class="calendar-grid__weekday" role="columnheader">
            <span class="calendar-grid__weekday-short" aria-hidden="true"><?= $e(substr($wd, 0, 1)) ?></span>
            <span class="calendar-grid__weekday-full" aria-hidden="true"><?= $e($wd) ?></span>
            <span class="sr-only"><?= $e($wd) ?></span>
          </div>
        <?php endforeach; ?>
      </div>

      <div class="calendar-grid__body" role="rowgroup">
        <?php
          // Leading blanks.
          for ($b = 0; $b < $firstDow; $b++):
        ?>
          <div class="calendar-cell calendar-cell--empty" role="cell" aria-hidden="true"></div>
        <?php endfor; ?>

        <?php for ($day = 1; $day <= $daysInMon; $day++):
          $ymd     = sprintf('%04d-%02d-%02d', $cal_year, $cal_month, $day);
          $dayEvts = $events_by_day[$ymd] ?? [];
          $isToday = ($ymd === $todayYmd);
        ?>
          <div class="calendar-cell<?= $isToday ? ' calendar-cell--today' : '' ?>" role="cell">
            <div class="calendar-cell__date"<?= $isToday ? ' aria-label="Today"' : '' ?>>
              <?= (int)$day ?>
            </div>
            <?php if ($dayEvts !== []): ?>
              <ul class="calendar-cell__events">
                <?php foreach ($dayEvts as $ev): ?>
                  <?php $featured = !empty($ev['effective_featured']); ?>
                  <li>
                    <a class="calendar-chip<?= $featured ? ' calendar-chip--featured' : '' ?>"
                       href="#event-<?= (int)$ev['id'] ?>">
                      <?php if ($featured): ?><span class="calendar-chip__star" aria-hidden="true">★</span><?php endif; ?>
                      <span class="calendar-chip__title"><?= $e($ev['title']) ?></span>
                    </a>
                  </li>
                <?php endforeach; ?>
              </ul>
            <?php endif; ?>
          </div>
        <?php endfor; ?>
      </div>
    </div>

    <!-- Chronological details list (also the anchor targets for the chips) -->
    <?php if ($events !== []): ?>
      <h2 class="calendar-list__heading"><?= $e($monthLabel) ?> &middot; Details</h2>
      <ol class="calendar-list">
        <?php foreach ($events as $ev): ?>
          <?php
            $featured = !empty($ev['effective_featured']);
            $desc     = $cleanDesc($ev['description'] ?? null);
            $notes    = trim((string)($ev['override_notes'] ?? ''));
            $imgFile  = (string)($ev['override_image_filename'] ?? '');
            $imgAlt   = (string)($ev['override_image_alt'] ?? '');
            $startDt  = new \DateTime((string)$ev['starts_at']);
          ?>
          <li class="calendar-list__item<?= $featured ? ' calendar-list__item--featured' : '' ?>"
              id="event-<?= (int)$ev['id'] ?>">
            <div class="calendar-list__date" aria-hidden="true">
              <span class="calendar-list__dow"><?= $e($startDt->format('D')) ?></span>
              <span class="calendar-list__day"><?= $e($startDt->format('j')) ?></span>
              <span class="calendar-list__mon"><?= $e($startDt->format('M')) ?></span>
            </div>
            <div class="calendar-list__body">
              <h3 class="calendar-list__title">
                <?php if ($featured): ?><span class="calendar-list__star" aria-label="Featured">★</span> <?php endif; ?>
                <?= $e($ev['title']) ?>
              </h3>
              <div class="calendar-list__meta">
                <span class="calendar-list__time"><?= $e($timeLabel($ev)) ?></span>
                <?php if (!empty($ev['location'])): ?>
                  <span class="calendar-list__loc"><?= $e($ev['location']) ?></span>
                <?php endif; ?>
              </div>
              <?php if ($imgFile !== ''): ?>
                <img class="calendar-list__image"
                     src="/uploads/<?= $e(ltrim($imgFile, '/')) ?>"
                     alt="<?= $e($imgAlt) ?>">
              <?php endif; ?>
              <?php if ($notes !== ''): ?>
                <p class="calendar-list__notes"><?= $e($notes) ?></p>
              <?php endif; ?>
              <?php if ($desc !== ''): ?>
                <p class="calendar-list__desc"><?= nl2br($e($desc)) ?></p>
              <?php endif; ?>
            </div>
          </li>
        <?php endforeach; ?>
      </ol>
    <?php endif; ?>

  </div>
</section>
