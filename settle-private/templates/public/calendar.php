<?php
/**
 * Public calendar — month grid with spanning multi-day bars (roadmap #8a).
 *
 * Multi-day and all-day events render as horizontal bars spanning the days
 * they cover (one bar per week; an event crossing a week boundary is split
 * with a "continues" cue). Single-day timed events render as time+title
 * entries inside the day cell, capped at CAL_MAX_PER_DAY with a "+N More"
 * link to the day view.
 *
 * @var \DateTime $cal_current  First day of the displayed month
 * @var string    $cal_prev_ym  "YYYY-MM" for the previous month
 * @var string    $cal_next_ym  "YYYY-MM" for the next month
 * @var array     $cal_weeks    Week rows from PublicController::buildMonthWeeks()
 * @var bool      $has_events   Whether any events fall in the visible grid
 * @var array     $subscribe    Subscribe links (may be empty)
 * @var string    $cal_view     'month'
 * @var array     $settings     From PublicView
 * @var array     $menu_tree    From PublicView
 * @var Closure   $e            htmlspecialchars helper
 *
 * Event datetimes are already in the church's local timezone (converted at
 * sync), so this template does no timezone math.
 */
$cal_weeks = $cal_weeks ?? [];
$weekdays  = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
?>
<?php /* On phones, default to the list view (the month grid is cramped on a
         narrow screen). Progressive enhancement only: with JS off the grid
         still renders. An explicit "Month" tap carries ?view=month, which
         suppresses the redirect so a phone user can still force the grid. */ ?>
<script>
(function () {
  try {
    if (window.matchMedia && window.matchMedia('(max-width: 640px)').matches
        && location.search.indexOf('view=month') === -1) {
      location.replace('/calendar/list');
    }
  } catch (e) {}
})();
</script>
<section class="page-intro">
  <div class="container">
    <div class="eyebrow">What's Happening</div>
    <h1>Calendar</h1>
  </div>
</section>

<section class="section">
  <div class="container">

    <?php include __DIR__ . '/_calendar_toolbar.php'; ?>

    <div class="calendar-nav">
      <a class="btn btn--ghost calendar-nav__btn" href="/calendar?ym=<?= $e($cal_prev_ym) ?>" rel="prev">
        <span aria-hidden="true">←</span> Prev
      </a>
      <h2 class="calendar-nav__title"><?= $e($cal_current->format('F Y')) ?></h2>
      <a class="btn btn--ghost calendar-nav__btn" href="/calendar?ym=<?= $e($cal_next_ym) ?>" rel="next">
        Next <span aria-hidden="true">→</span>
      </a>
    </div>

    <?php if (!$has_events): ?>
      <p class="calendar-empty">No events scheduled for <?= $e($cal_current->format('F Y')) ?>.</p>
    <?php endif; ?>

    <div class="cal-month">
      <div class="cal-weekdays">
        <?php foreach ($weekdays as $wd): ?>
          <div class="cal-weekday">
            <span class="cal-weekday__full"><?= $e($wd) ?></span>
            <span class="cal-weekday__short" aria-hidden="true"><?= $e(substr($wd, 0, 1)) ?></span>
          </div>
        <?php endforeach; ?>
      </div>

      <?php foreach ($cal_weeks as $wk): ?>
        <div class="cal-week" style="--lanes: <?= (int) $wk['lane_count'] ?>;">

          <?php foreach ($wk['bars'] as $bar):
            $bf  = !empty($bar['ev']['effective_featured']);
            $cls = 'cal-bar'
                 . ($bf ? ' cal-bar--featured' : '')
                 . ($bar['continues_left'] ? ' cal-bar--cont-left' : '')
                 . ($bar['continues_right'] ? ' cal-bar--cont-right' : '');
          ?>
            <a class="<?= $cls ?>"
               style="--col: <?= (int) $bar['start_col'] ?>; --span: <?= (int) $bar['span'] ?>; --lane: <?= (int) $bar['lane'] ?>;"
               href="/calendar/day/<?= $e($bar['link_ymd']) ?>"
               title="<?= $e($bar['ev']['title']) ?>">
              <?php if ($bf): ?><span class="cal-bar__star" aria-hidden="true">★</span><?php endif; ?>
              <span class="cal-bar__title"><?= $e($bar['ev']['title']) ?></span>
            </a>
          <?php endforeach; ?>

          <div class="cal-week__cells">
            <?php foreach ($wk['days'] as $day):
              $cellCls = 'cal-cell'
                       . ($day['in_month'] ? '' : ' cal-cell--out')
                       . ($day['is_today'] ? ' cal-cell--today' : '');
            ?>
              <div class="<?= $cellCls ?>">
                <a class="cal-cell__num" href="/calendar/day/<?= $e($day['date']) ?>"><?= (int) $day['day'] ?></a>

                <?php if (!empty($day['shown'])): ?>
                  <ul class="cal-cell__list">
                    <?php foreach ($day['shown'] as $ev):
                      $ef = !empty($ev['effective_featured']);
                    ?>
                      <li>
                        <a class="cal-ev<?= $ef ? ' cal-ev--featured' : '' ?>" href="/calendar/day/<?= $e($day['date']) ?>">
                          <span class="cal-ev__time"><?= $e(\Settle\CalendarFormat::clockRange($ev)) ?></span>
                          <span class="cal-ev__title"><?= $e($ev['title']) ?></span>
                        </a>
                      </li>
                    <?php endforeach; ?>
                  </ul>
                <?php endif; ?>

                <?php if ($day['overflow'] > 0): ?>
                  <a class="cal-more" href="/calendar/day/<?= $e($day['date']) ?>">+ <?= (int) $day['overflow'] ?> More</a>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          </div>

        </div>
      <?php endforeach; ?>
    </div>

  </div>
</section>
