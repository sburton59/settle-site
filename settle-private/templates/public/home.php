<?php
/**
 * Homepage.
 *
 * @var array        $settings    From PublicView
 * @var array        $menu_tree   From PublicView
 * @var array|null   $about       From PublicController::home() — the about page row (for welcome text)
 * @var array        $slides      From PublicController::home() — active slideshow slides
 * @var Closure      $e           htmlspecialchars helper from View::render()
 */

// Settings accessor (same closure pattern as the layout).
$s = static function (string $key, string $default = '') use ($settings): string {
    return isset($settings[$key]) && $settings[$key] !== ''
        ? (string) $settings[$key]
        : $default;
};

$slides = $slides ?? [];
$hasSlides = $slides !== [];
?>

<?php if ($hasSlides): ?>
  <section class="hero" aria-label="Welcome">
    <div
      class="slideshow"
      data-slideshow
      data-interval="6000"
      role="region"
      aria-roledescription="carousel"
      aria-label="Highlights"
    >
      <?php foreach ($slides as $i => $slide): ?>
        <?php
          $bg = '';
          // The Slideshow::active() shape historically delivers media join
          // data either inlined (filename present on the row) or as a
          // nested key. We defensively check both.
          if (!empty($slide['filename'])) {
              $bg = '/uploads/' . ltrim((string) $slide['filename'], '/');
          } elseif (!empty($slide['media_filename'])) {
              $bg = '/uploads/' . ltrim((string) $slide['media_filename'], '/');
          } elseif (!empty($slide['url'])) {
              $bg = (string) $slide['url'];
          }
        ?>
        <div
          class="slideshow__slide<?= $i === 0 ? ' is-active' : '' ?>"
          data-slide-index="<?= (int) $i ?>"
          <?php if ($bg !== ''): ?>
            style="background-image: url('<?= $e($bg) ?>');"
          <?php endif; ?>
          aria-hidden="<?= $i === 0 ? 'false' : 'true' ?>"
        >
          <?php if (!empty($slide['caption'])): ?>
            <div class="slideshow__caption"><?= $e($slide['caption']) ?></div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>

      <?php if (count($slides) > 1): ?>
        <div class="slideshow__dots" data-slideshow-dots>
          <?php foreach ($slides as $i => $_): ?>
            <button
              type="button"
              class="slideshow__dot<?= $i === 0 ? ' is-active' : '' ?>"
              data-slide-target="<?= (int) $i ?>"
              aria-label="Show slide <?= (int) ($i + 1) ?>"
            ></button>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </section>

  <script src="/assets/js/slideshow.js" defer></script>
<?php else: ?>
  <!-- Empty-state hero: no slides seeded yet. -->
  <section class="hero hero--empty">
    <div class="container">
      <h1><?= $e($s('church_name', 'Welcome')) ?></h1>
      <?php if ($s('church_tagline') !== ''): ?>
        <p class="hero--empty__tagline"><?= $e($s('church_tagline')) ?></p>
      <?php endif; ?>
    </div>
  </section>
<?php endif; ?>

<?php
// Service-times band shows if at least one worship_* setting is populated.
$hasWorship = $s('worship_traditional') !== ''
            || $s('worship_contemporary') !== ''
            || $s('worship_sunday_school') !== '';
?>
<section class="section section--tight section--soft">
  <div class="container">

    <div class="home-welcome">
      <div class="eyebrow"><?= $e($s('church_short_name', 'Welcome')) ?></div>
      <h2><?= $e($s('homepage_welcome_heading', 'Welcome home')) ?></h2>
      <?php
        // Welcome lead falls back to the About page's first paragraph
        // if the dedicated homepage_welcome_lead setting is blank.
        $lead = $s('homepage_welcome_lead');
        if ($lead === '' && !empty($about['body_html'])) {
            // Strip HTML, take the first ~280 chars at a word boundary.
            $plain = trim(strip_tags((string) $about['body_html']));
            if ($plain !== '') {
                if (mb_strlen($plain) > 280) {
                    $cut = mb_substr($plain, 0, 280);
                    $lastSpace = mb_strrpos($cut, ' ');
                    if ($lastSpace !== false) {
                        $cut = mb_substr($cut, 0, $lastSpace);
                    }
                    $lead = $cut . '…';
                } else {
                    $lead = $plain;
                }
            }
        }
      ?>
      <?php if ($lead !== ''): ?>
        <p class="home-welcome__lead"><?= $e($lead) ?></p>
      <?php endif; ?>
      <div class="btn-row">
        <a class="btn" href="/page/about">Learn More</a>
        <a class="btn btn--ghost" href="/page/im-new">I'm New</a>
      </div>
    </div>

    <?php if ($hasWorship): ?>
      <div class="section-head section-head--sub">
        <div class="eyebrow">Join Us</div>
        <h3>This Sunday</h3>
      </div>
      <div class="worship-times">
        <?php if ($s('worship_sunday_school') !== ''): ?>
          <div class="worship-card">
            <h4 class="worship-card__service">Sunday School</h4>
            <div class="worship-card__time"><?= $e($s('worship_sunday_school')) ?></div>
          </div>
        <?php endif; ?>
        <?php if ($s('worship_traditional') !== ''): ?>
          <div class="worship-card">
            <h4 class="worship-card__service">Traditional Worship</h4>
            <div class="worship-card__time"><?= $e($s('worship_traditional')) ?></div>
          </div>
        <?php endif; ?>
        <?php if ($s('worship_contemporary') !== ''): ?>
          <div class="worship-card">
            <h4 class="worship-card__service">Contemporary Worship</h4>
            <div class="worship-card__time"><?= $e($s('worship_contemporary')) ?></div>
          </div>
        <?php endif; ?>
      </div>
    <?php endif; ?>

  </div>
</section>

<?php
// Upcoming-events widget. $events comes from PublicController::home()
// (empty unless the calendar feature is on and the cache has events).
$events = $events ?? [];
?>
<?php if ($events !== []): ?>
  <section class="section section--tight">
    <div class="container">
      <div class="section-head">
        <div class="eyebrow">Mark Your Calendar</div>
        <h2>Upcoming Events</h2>
      </div>
      <div class="event-grid">
        <?php foreach ($events as $ev): ?>
          <?php
            $featured = !empty($ev['effective_featured']);
            $startDt  = new \DateTime((string) $ev['starts_at']);
            $isAllDay = !empty($ev['is_all_day']);
            if ($isAllDay) {
                $timeStr = 'All day';
            } else {
                $timeStr = $startDt->format('g:i a');
            }
            // A website-only override image (set in /admin/calendar) shows as
            // the card background, with a readable dark overlay added via CSS.
            $ovrImg    = (string) ($ev['override_image_filename'] ?? '');
            $cardClass = 'event-card'
                . ($featured ? ' event-card--featured' : '')
                . ($ovrImg !== '' ? ' event-card--image' : '');
            $cardStyle = $ovrImg !== ''
                ? ' style="background-image:url(\'/uploads/' . rawurlencode($ovrImg) . '\')"'
                : '';
          ?>
          <a class="<?= $cardClass ?>"<?= $cardStyle ?> href="/calendar?ym=<?= $e($startDt->format('Y-m')) ?>#event-<?= (int) $ev['id'] ?>">
            <div class="event-card__date">
              <span class="event-card__mon"><?= $e($startDt->format('M')) ?></span>
              <span class="event-card__day"><?= $e($startDt->format('j')) ?></span>
            </div>
            <div class="event-card__body">
              <?php if ($featured): ?>
                <span class="event-card__badge">★ Featured</span>
              <?php endif; ?>
              <h3 class="event-card__title"><?= $e($ev['title']) ?></h3>
              <div class="event-card__meta">
                <span><?= $e($startDt->format('D, M j')) ?> · <?= $e($timeStr) ?></span>
                <?php if (!empty($ev['location'])): ?>
                  <span class="event-card__loc"><?= $e($ev['location']) ?></span>
                <?php endif; ?>
              </div>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
      <div class="btn-row">
        <a class="btn btn--ghost" href="/calendar">View Full Calendar</a>
      </div>
    </div>
  </section>
<?php endif; ?>

<section class="section section--tight section--brand">
  <div class="container">
    <div class="home-cta">
      <div class="eyebrow">We'd love to hear from you</div>
      <h2>Get in touch</h2>
      <p>Have a question? Looking for a place to plug in? Want us to pray for you?</p>
      <div class="btn-row">
        <a class="btn btn--on-dark" href="/contact">Contact Us</a>
        <a class="btn btn--on-dark" href="/prayer">Prayer Request</a>
      </div>
    </div>
  </div>
</section>
