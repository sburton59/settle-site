<?php
/**
 * Public calendar — list view (roadmap #8a). Upcoming events in
 * chronological order, paginated. Reuses the shared event-item partial and
 * the blog pagination markup/styles.
 *
 * @var array   $events    Upcoming CalendarEvent rows for this page
 * @var int     $page      Current page (1-based)
 * @var int     $pages     Total pages
 * @var int     $total     Total upcoming events
 * @var int     $per_page  Page size
 * @var array   $subscribe Subscribe links (may be empty)
 * @var string  $cal_view  'list'
 * @var array   $settings  From PublicView
 * @var array   $menu_tree From PublicView
 * @var Closure $e         htmlspecialchars helper
 */
$events = $events ?? [];
$page   = (int) ($page ?? 1);
$pages  = (int) ($pages ?? 1);
?>
<section class="page-intro">
  <div class="container">
    <div class="eyebrow">What's Happening</div>
    <h1>Upcoming Events</h1>
  </div>
</section>

<section class="section">
  <div class="container">

    <?php include __DIR__ . '/_calendar_toolbar.php'; ?>

    <?php if ($events === []): ?>
      <p class="calendar-empty">No upcoming events are scheduled right now. Please check back soon.</p>
    <?php else: ?>
      <ol class="calendar-list">
        <?php foreach ($events as $ev): ?>
          <?php include __DIR__ . '/_calendar_event_item.php'; ?>
        <?php endforeach; ?>
      </ol>

      <?php if ($pages > 1): ?>
        <nav class="blog-pagination" aria-label="Calendar pages">
          <?php if ($page > 1): ?>
            <a class="btn btn--ghost" href="/calendar/list?p=<?= (int) ($page - 1) ?>"><span aria-hidden="true">←</span> Sooner</a>
          <?php else: ?>
            <span class="btn btn--ghost is-disabled" aria-disabled="true"><span aria-hidden="true">←</span> Sooner</span>
          <?php endif; ?>

          <span class="blog-pagination__status">Page <?= (int) $page ?> of <?= (int) $pages ?></span>

          <?php if ($page < $pages): ?>
            <a class="btn btn--ghost" href="/calendar/list?p=<?= (int) ($page + 1) ?>">Later <span aria-hidden="true">→</span></a>
          <?php else: ?>
            <span class="btn btn--ghost is-disabled" aria-disabled="true">Later <span aria-hidden="true">→</span></span>
          <?php endif; ?>
        </nav>
      <?php endif; ?>
    <?php endif; ?>

  </div>
</section>
