<?php
/**
 * Public calendar — single day view (roadmap #8a). Reached from the month
 * grid (day numbers, event entries, "+N More") and homepage cards. Reuses
 * the shared event-item partial.
 *
 * @var \DateTime $cal_day   The day being displayed
 * @var array     $events    Events overlapping the day (chronological)
 * @var string    $prev_ymd  "Y-m-d" of the previous day
 * @var string    $next_ymd  "Y-m-d" of the next day
 * @var string    $month_ym  "Y-m" of this day's month (for "back to month")
 * @var array     $subscribe Subscribe links (may be empty)
 * @var string    $cal_view  'day'
 * @var array     $settings  From PublicView
 * @var array     $menu_tree From PublicView
 * @var Closure   $e         htmlspecialchars helper
 */
$events = $events ?? [];
?>
<section class="page-intro">
  <div class="container">
    <div class="eyebrow"><?= $e($cal_day->format('l')) ?></div>
    <h1><?= $e($cal_day->format('F j, Y')) ?></h1>
  </div>
</section>

<section class="section">
  <div class="container">

    <?php include __DIR__ . '/_calendar_toolbar.php'; ?>

    <div class="calendar-nav">
      <a class="btn btn--ghost calendar-nav__btn" href="/calendar/day/<?= $e($prev_ymd) ?>" rel="prev">
        <span aria-hidden="true">←</span> Prev day
      </a>
      <h2 class="calendar-nav__title"><?= $e($cal_day->format('D, M j')) ?></h2>
      <a class="btn btn--ghost calendar-nav__btn" href="/calendar/day/<?= $e($next_ymd) ?>" rel="next">
        Next day <span aria-hidden="true">→</span>
      </a>
    </div>

    <p class="calendar-day__back">
      <a href="/calendar?ym=<?= $e($month_ym) ?>">← Back to <?= $e($cal_day->format('F Y')) ?></a>
    </p>

    <?php if ($events === []): ?>
      <p class="calendar-empty">No events scheduled for this day.</p>
    <?php else: ?>
      <ol class="calendar-list">
        <?php foreach ($events as $ev): ?>
          <?php include __DIR__ . '/_calendar_event_item.php'; ?>
        <?php endforeach; ?>
      </ol>
    <?php endif; ?>

  </div>
</section>
