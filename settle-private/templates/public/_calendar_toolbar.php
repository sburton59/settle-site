<?php
/**
 * Shared calendar toolbar: Month/List view switcher + subscribe links.
 * Included by calendar.php, calendar_list.php, calendar_day.php — runs in
 * the including template's scope.
 *
 * @var string  $cal_view   'month' | 'list' | 'day'
 * @var array   $subscribe  ['google'=>..,'ics'=>..,'webcal'=>..] (empty if unset)
 * @var Closure $e          escaping helper
 */
$cal_view  = $cal_view ?? '';
$subscribe = $subscribe ?? ['google' => '', 'webcal' => ''];
?>
<div class="cal-toolbar">
  <nav class="cal-views" aria-label="Calendar views">
    <a class="cal-views__btn<?= $cal_view === 'month' || $cal_view === 'day' ? ' is-active' : '' ?>" href="/calendar">Month</a>
    <a class="cal-views__btn<?= $cal_view === 'list' ? ' is-active' : '' ?>" href="/calendar/list">List</a>
  </nav>
  <?php if (!empty($subscribe['google'])): ?>
    <div class="cal-subscribe">
      <a class="btn btn--ghost" href="<?= $e($subscribe['webcal']) ?>">Subscribe (iCal)</a>
      <a class="btn btn--ghost" href="<?= $e($subscribe['google']) ?>" target="_blank" rel="noopener">Add to Google</a>
    </div>
  <?php endif; ?>
</div>
