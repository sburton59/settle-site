<?php
/**
 * Homepage.
 *
 * @var array        $settings           From PublicView
 * @var array        $menu_tree          From PublicView
 * @var array|null   $about              From PublicController::home() — about page row (welcome text)
 * @var array        $slides             From PublicController::home() — active slideshow slides
 * @var array        $events             From PublicController::home() — upcoming events (may be empty)
 * @var array        $sectionBackgrounds From PublicController::home() — feature-band images, keyed by original_name
 * @var Closure      $e                  htmlspecialchars helper from View::render()
 */

// Settings accessor (same closure pattern as the layout).
$s = static function (string $key, string $default = '') use ($settings): string {
    return isset($settings[$key]) && $settings[$key] !== ''
        ? (string) $settings[$key]
        : $default;
};

$slides    = $slides ?? [];
$hasSlides = $slides !== [];

// Hero overlay copy — editable in /admin/settings (Homepage group), with
// sensible fallbacks so the hero is never blank on a fresh install.
$heroHeading = $s('homepage_hero_heading', 'A place for you here');
$heroSub     = $s('homepage_hero_subheading', "Whether you are 2 or 102, we'd love to have you join us this Sunday.");
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

    <div class="hero__overlay">
      <div class="hero__overlay-inner">
        <div class="eyebrow">Welcome</div>
        <h1><?= $e($heroHeading) ?></h1>
        <?php if ($heroSub !== ''): ?>
          <p class="hero__sub"><?= $e($heroSub) ?></p>
        <?php endif; ?>
        <div class="btn-row">
          <a class="btn" href="/page/im-new">Plan Your Visit</a>
          <a class="btn btn--on-dark" href="/page/watch">Watch Online</a>
        </div>
      </div>
    </div>
  </section>

  <script src="/assets/js/slideshow.js" defer></script>
<?php else: ?>
  <!-- Empty-state hero: no slides seeded yet. Same copy/CTAs as the
       slideshow hero, on the plain brand band. -->
  <section class="hero hero--empty">
    <div class="container hero__overlay-inner">
      <div class="eyebrow">Welcome</div>
      <h1><?= $e($heroHeading) ?></h1>
      <?php if ($heroSub !== ''): ?>
        <p class="hero__sub"><?= $e($heroSub) ?></p>
      <?php endif; ?>
      <div class="btn-row">
        <a class="btn btn--on-dark" href="/page/im-new">Plan Your Visit</a>
        <a class="btn btn--on-dark" href="/page/watch">Watch Online</a>
      </div>
    </div>
  </section>
<?php endif; ?>

<?php
// Compact worship-times strip, directly under the hero. Times come from
// the worship_* settings; only populated services render. The contemporary
// service has its own "Shout!" label here, so a trailing "(SHOUT!)" the
// stored time may carry is dropped for display only (the setting is left
// untouched, and the footer still shows it).
$schoolTime  = $s('worship_sunday_school');
$tradTime    = $s('worship_traditional');
$contemp     = $s('worship_contemporary');
$contempTime = preg_replace('/\s*\((?:shout!?)\)\s*$/i', '', $contemp);
$contempTime = is_string($contempTime) ? trim($contempTime) : $contemp;

$services = [];
if ($schoolTime !== '') { $services[] = ['Sunday School', $schoolTime]; }
if ($tradTime   !== '') { $services[] = ['Traditional',   $tradTime];   }
if ($contemp    !== '') { $services[] = ['Shout!',        $contempTime]; }
?>
<?php if ($services !== []): ?>
  <section class="service-strip" aria-label="Worship times">
    <div class="container">
      <div class="service-strip__inner">
        <?php foreach ($services as [$svcName, $svcTime]): ?>
          <div class="service-strip__item">
            <span class="service-strip__name"><?= $e($svcName) ?></span>
            <span class="service-strip__time"><?= $e($svcTime) ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
<?php endif; ?>

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
    </div>
  </div>
</section>

<?php
// Three photo "doorway" bands into key areas. Backgrounds come from the
// staged Media Library assets (resolved by original_name in the
// controller); a missing image degrades to a solid band via
// .feature-band--plain. Labels/links are intentionally hardcoded.
$sectionBackgrounds = $sectionBackgrounds ?? [];
$featureBands = [
    ['title' => "I'm New",         'cta' => 'Start here',               'href' => '/page/im-new',  'asset' => 'Section-Im-New.jpg'],
    ['title' => 'Grow in Faith',   'cta' => 'Children, youth & adults', 'href' => '/page/connect', 'asset' => 'Section-Faith-Development.jpg'],
    ['title' => 'Worship With Us', 'cta' => 'Plan your Sunday',         'href' => '/page/sundays', 'asset' => 'Section-Worship.jpg'],
];
?>
<section class="feature-bands" aria-label="Find your place">
  <?php foreach ($featureBands as $band): ?>
    <?php
      $row    = $sectionBackgrounds[$band['asset']] ?? null;
      $imgUrl = '';
      if ($row !== null && !empty($row['filename'])) {
          // Same URL convention as the slideshow / event cards: keep the
          // path separators as real slashes — do NOT url-encode them.
          $imgUrl = '/uploads/' . ltrim((string) $row['filename'], '/');
      }
      $bandClass = 'feature-band' . ($imgUrl === '' ? ' feature-band--plain' : '');
      $bandStyle = $imgUrl !== ''
          ? ' style="background-image:url(\'' . $e($imgUrl) . '\')"'
          : '';
    ?>
    <a class="<?= $bandClass ?>"<?= $bandStyle ?> href="<?= $e($band['href']) ?>">
      <span class="feature-band__scrim" aria-hidden="true"></span>
      <span class="feature-band__content">
        <span class="feature-band__title"><?= $e($band['title']) ?></span>
        <span class="feature-band__cta"><?= $e($band['cta']) ?> <span aria-hidden="true">&rarr;</span></span>
      </span>
    </a>
  <?php endforeach; ?>
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
            $timeStr  = \Settle\CalendarFormat::clockRange($ev);
            // A website-only override image (set in /admin/calendar) shows as
            // the card background, with a readable dark overlay added via CSS.
            // media.filename is a relative path (uploads/YYYY/MM/<rand>.<ext>),
            // so build the URL exactly like the slideshow — keep the slashes as
            // real path separators (do NOT url-encode them).
            $ovrImg    = (string) ($ev['override_image_filename'] ?? '');
            $ovrImgUrl = $ovrImg !== '' ? '/uploads/' . ltrim($ovrImg, '/') : '';
            $cardClass = 'event-card'
                . ($featured ? ' event-card--featured' : '')
                . ($ovrImgUrl !== '' ? ' event-card--image' : '');
            $cardStyle = $ovrImgUrl !== ''
                ? ' style="background-image:url(\'' . $e($ovrImgUrl) . '\')"'
                : '';
          ?>
          <a class="<?= $cardClass ?>"<?= $cardStyle ?> href="/calendar/day/<?= $e($startDt->format('Y-m-d')) ?>">
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
