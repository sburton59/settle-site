<?php
/** @var array $staff */
?>
<div style="max-width:1100px; margin:2em auto; padding:1em;">
  <h1 style="color:var(--brand-red);">Our Staff</h1>

  <?php if (empty($staff)): ?>
    <p class="muted">Staff information is being updated. Please check back soon.</p>
  <?php else: ?>
    <div class="staff-grid">
      <?php foreach ($staff as $p): ?>
        <div class="staff-card">
          <div class="staff-photo">
            <?php if (!empty($p['photo_filename'])): ?>
              <img src="/uploads/<?= htmlspecialchars($p['photo_filename'], ENT_QUOTES) ?>"
                   alt="<?= htmlspecialchars($p['photo_alt'] ?: $p['full_name'], ENT_QUOTES) ?>"
                   loading="lazy">
            <?php else: ?>
              <img src="/assets/img/silhouette.svg" alt="" loading="lazy">
            <?php endif; ?>
          </div>

          <h2 class="staff-name"><?= htmlspecialchars($p['full_name'], ENT_QUOTES) ?></h2>

          <?php if (!empty($p['title'])): ?>
            <div class="staff-title"><?= htmlspecialchars($p['title'], ENT_QUOTES) ?></div>
          <?php endif; ?>

          <?php if (!empty($p['bio_html'])): ?>
            <div class="staff-bio">
              <?= $p['bio_html'] /* trusted: written by staff via admin */ ?>
            </div>
          <?php endif; ?>

          <?php if (!empty($p['email']) || !empty($p['phone'])): ?>
            <div class="staff-contact">
              <?php if (!empty($p['email'])): ?>
                <div><?= \Settle\EmailObfuscator::link($p['email']) ?></div>
              <?php endif; ?>
              <?php if (!empty($p['phone'])): ?>
                <div><a href="tel:<?= htmlspecialchars(preg_replace('/[^0-9+]/', '', $p['phone']), ENT_QUOTES) ?>">
                  <?= htmlspecialchars($p['phone'], ENT_QUOTES) ?>
                </a></div>
              <?php endif; ?>
            </div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <p style="margin-top:3em;"><a href="/">&larr; Home</a></p>
</div>
