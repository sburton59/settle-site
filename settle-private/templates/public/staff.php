<?php
/**
 * Staff directory.
 *
 * @var array   $staff      From PublicController::staff() — visible staff in order
 * @var array   $settings   From PublicView
 * @var array   $menu_tree  From PublicView
 * @var Closure $e
 */

use Settle\EmailObfuscator;
use Settle\PhoneFormatter;
?>

<section class="page-intro">
  <div class="container">
    <div class="eyebrow">Meet</div>
    <h1>Our Staff</h1>
  </div>
</section>

<section class="section">
  <div class="container">

    <?php if ($staff === []): ?>
      <p style="text-align: center; color: var(--text-muted);">
        Staff directory coming soon.
      </p>
    <?php else: ?>
      <div class="staff-grid">
        <?php foreach ($staff as $member): ?>
          <?php
            $photoUrl = '';
            if (!empty($member['photo_filename'])) {
                $photoUrl = '/uploads/' . ltrim((string) $member['photo_filename'], '/');
            } elseif (!empty($member['photo_url'])) {
                $photoUrl = (string) $member['photo_url'];
            }
            if ($photoUrl === '') {
                $photoUrl = '/assets/img/silhouette.svg';
            }
          ?>
          <div class="staff-card">
            <div
              class="staff-card__photo"
              style="background-image: url('<?= $e($photoUrl) ?>');"
              role="img"
              aria-label="<?= $e($member['full_name']) ?>"
            ></div>
            <div class="staff-card__body">
              <h2 class="staff-card__name"><?= $e($member['full_name']) ?></h2>
              <?php if (!empty($member['title'])): ?>
                <div class="staff-card__title"><?= $e($member['title']) ?></div>
              <?php endif; ?>
              <?php if (!empty($member['bio_html'])): ?>
                <div class="staff-card__bio">
                  <?php
                    // bio_html is intentionally trusted (admin-authored).
                    echo $member['bio_html'];
                  ?>
                </div>
              <?php endif; ?>

              <?php
                $hasEmail = !empty($member['email']);
                $hasPhone = !empty($member['phone']);
              ?>
              <?php if ($hasEmail || $hasPhone): ?>
                <div class="staff-card__contact">
                  <?php if ($hasEmail): ?>
                    <?= EmailObfuscator::mailtoLink((string) $member['email'], 'Email') ?>
                  <?php endif; ?>
                  <?php if ($hasPhone): ?>
                    <a href="<?= $e(PhoneFormatter::telHref((string) $member['phone'])) ?>">
                      <?= $e(PhoneFormatter::formatUs((string) $member['phone'])) ?>
                    </a>
                  <?php endif; ?>
                </div>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

  </div>
</section>
