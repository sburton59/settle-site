<?php
/**
 * Prayer request public form.
 *
 * @var array   $errors     Field-keyed validation errors
 * @var array   $data       Echoed-back form values
 * @var bool    $success    True = show thank-you state
 * @var array   $settings   From PublicView
 * @var array   $menu_tree  From PublicView
 * @var Closure $e
 */

use Settle\Csrf;
?>

<section class="page-intro">
  <div class="container">
    <div class="eyebrow">We'd love to pray with you</div>
    <h1>Prayer Request</h1>
  </div>
</section>

<section class="section">
  <div class="container">

    <?php if ($success): ?>
      <div class="form__success" style="max-width: 640px; margin-inline: auto; text-align: center;">
        <h2 style="margin-top: 0; color: inherit;">Thank you</h2>
        <p style="margin-bottom: 0;">
          Your prayer request has been received. Our prayer team will be lifting you up.
        </p>
      </div>
      <div style="text-align: center; margin-top: 2rem;">
        <a class="btn btn--ghost" href="/">Return Home</a>
      </div>
    <?php else: ?>

      <p style="max-width: 640px; margin: 0 auto 2rem; text-align: center; color: var(--text-muted);">
        Share what's on your heart. We'd be honored to pray with you. Mark a request
        private to keep it between you and the prayer team.
      </p>

      <form method="post" action="/prayer" class="form" style="margin-inline: auto;" novalidate>
        <?= Csrf::field() ?>

        <!-- Honeypot field. Real users never see or fill it. -->
        <div class="form__honeypot" aria-hidden="true">
          <label>Website (leave blank)
            <input type="text" name="website" value="" tabindex="-1" autocomplete="off">
          </label>
        </div>

        <?php if (!empty($errors['_general'])): ?>
          <div class="form__error"><?= $e($errors['_general']) ?></div>
        <?php endif; ?>

        <div class="form__field">
          <label class="form__label" for="prq_name">Your Name <span style="color: var(--text-muted); font-weight: 400; text-transform: none; letter-spacing: 0; font-size: 0.75rem;">(optional)</span></label>
          <input
            type="text"
            id="prq_name"
            name="submitter_name"
            class="form__input"
            value="<?= $e($data['submitter_name'] ?? '') ?>"
            maxlength="150"
            autocomplete="name"
          >
          <?php if (!empty($errors['submitter_name'])): ?>
            <div class="form__error" style="margin-top: 0.5rem;"><?= $e($errors['submitter_name']) ?></div>
          <?php endif; ?>
        </div>

        <div class="form__field">
          <label class="form__label" for="prq_email">Email <span style="color: var(--text-muted); font-weight: 400; text-transform: none; letter-spacing: 0; font-size: 0.75rem;">(optional)</span></label>
          <input
            type="email"
            id="prq_email"
            name="submitter_email"
            class="form__input"
            value="<?= $e($data['submitter_email'] ?? '') ?>"
            maxlength="190"
            autocomplete="email"
          >
          <div class="form__hint">If you'd like a personal follow-up. We won't share it.</div>
          <?php if (!empty($errors['submitter_email'])): ?>
            <div class="form__error" style="margin-top: 0.5rem;"><?= $e($errors['submitter_email']) ?></div>
          <?php endif; ?>
        </div>

        <div class="form__field">
          <label class="form__label" for="prq_text">Your Prayer Request <span class="req">*</span></label>
          <textarea
            id="prq_text"
            name="request_text"
            class="form__textarea"
            maxlength="5000"
            required
          ><?= $e($data['request_text'] ?? '') ?></textarea>
          <?php if (!empty($errors['request_text'])): ?>
            <div class="form__error" style="margin-top: 0.5rem;"><?= $e($errors['request_text']) ?></div>
          <?php endif; ?>
        </div>

        <div class="form__field">
          <label class="form__radio-option" style="font-size: 0.95rem;">
            <input
              type="checkbox"
              name="is_private"
              value="1"
              <?= !empty($data['is_private']) ? 'checked' : '' ?>
            >
            <span>Keep this request private (visible only to the prayer team)</span>
          </label>
        </div>

        <div style="margin-top: 2rem;">
          <button type="submit" class="btn">Send Prayer Request</button>
        </div>
      </form>

    <?php endif; ?>

  </div>
</section>
