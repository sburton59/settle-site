<?php
/**
 * One event row for the list/day views. Mirrors the original month-page
 * details-list item markup. Runs in the including loop's scope.
 *
 * @var array   $ev  one CalendarEvent row (with override overlay columns)
 * @var Closure $e   escaping helper
 */
$featured = !empty($ev['effective_featured']);
$desc     = \Settle\CalendarFormat::cleanDescription($ev['description'] ?? null);
$notes    = trim((string)($ev['override_notes'] ?? ''));
$imgFile  = (string)($ev['override_image_filename'] ?? '');
$imgAlt   = (string)($ev['override_image_alt'] ?? '');
$startDt  = new \DateTime((string)$ev['starts_at']);
?>
<li class="calendar-list__item<?= $featured ? ' calendar-list__item--featured' : '' ?>">
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
      <span class="calendar-list__time"><?= $e(\Settle\CalendarFormat::timeLabel($ev)) ?></span>
      <?php if (!empty($ev['location'])): ?>
        <span class="calendar-list__loc"><?= $e($ev['location']) ?></span>
      <?php endif; ?>
    </div>
    <?php if ($imgFile !== ''): ?>
      <img class="calendar-list__image" src="/uploads/<?= $e(ltrim($imgFile, '/')) ?>" alt="<?= $e($imgAlt) ?>">
    <?php endif; ?>
    <?php if ($notes !== ''): ?>
      <p class="calendar-list__notes"><?= $e($notes) ?></p>
    <?php endif; ?>
    <?php if ($desc !== ''): ?>
      <p class="calendar-list__desc"><?= nl2br($e($desc)) ?></p>
    <?php endif; ?>
  </div>
</li>
