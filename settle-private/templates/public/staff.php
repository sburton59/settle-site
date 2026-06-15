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
            $hasBio = !empty($member['bio_html']);
          ?>
          <div
            class="staff-card"
            data-staff-name="<?= $e($member['full_name']) ?>"
            data-staff-title="<?= $e($member['title'] ?? '') ?>"
            data-staff-photo="<?= $e($photoUrl) ?>"
          >
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

              <?php if ($hasBio): ?>
                <div class="staff-card__bio">
                  <?php
                    // bio_html is intentionally trusted (admin-authored).
                    echo $member['bio_html'];
                  ?>
                </div>
                <div class="staff-card__bio-full" hidden><?php echo $member['bio_html']; ?></div>
                <button
                  type="button"
                  class="staff-card__more"
                  aria-haspopup="dialog"
                  hidden
                >Read more<span class="visually-hidden"> about <?= $e($member['full_name']) ?></span></button>
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

      <!--
        Single shared bio modal. Populated client-side from the clicked card's
        data-* attributes, hidden full-bio node, and contact block. Inert and
        display:none until staff-modal.js opens it; degrades gracefully (the
        full bio is already present in each card's .staff-card__bio-full node,
        and the "Read more" buttons stay hidden) when JS is unavailable.
      -->
      <div class="staff-modal" id="staff-modal" hidden>
        <div class="staff-modal__backdrop" data-staff-modal-close></div>
        <div
          class="staff-modal__dialog"
          role="dialog"
          aria-modal="true"
          aria-labelledby="staff-modal-name"
          tabindex="-1"
        >
          <button
            type="button"
            class="staff-modal__close"
            data-staff-modal-close
            aria-label="Close"
          >&times;</button>
          <div class="staff-modal__photo" id="staff-modal-photo" role="img" aria-label=""></div>
          <div class="staff-modal__body">
            <h2 class="staff-modal__name" id="staff-modal-name"></h2>
            <div class="staff-modal__title" id="staff-modal-title" hidden></div>
            <div class="staff-modal__bio" id="staff-modal-bio"></div>
            <div class="staff-modal__contact" id="staff-modal-contact" hidden></div>
          </div>
        </div>
      </div>

      <script src="/assets/js/staff-modal.js" defer></script>
    <?php endif; ?>

  </div>
</section>
