<?php
/**
 * Contact public form.
 *
 * @var array   $errors     Field-keyed validation errors
 * @var array   $data       Echoed-back form values
 * @var bool    $success    True = show thank-you state
 * @var array   $settings   From PublicView
 * @var array   $menu_tree  From PublicView
 * @var Closure $e
 */

use Settle\Csrf;

$s = static function (string $key, string $default = '') use ($settings): string {
    return isset($settings[$key]) && $settings[$key] !== ''
        ? (string) $settings[$key]
        : $default;
};
?>

<section class="page-intro">
  <div class="container">
    <div class="eyebrow">Get in touch</div>
    <h1>Contact Us</h1>
  </div>
</section>

<section class="section">
  <div class="container">

    <?php if ($success): ?>
      <div class="form__success" style="max-width: 640px; margin-inline: auto; text-align: center;">
        <h2 style="margin-top: 0; color: inherit;">Thank you</h2>
        <p style="margin-bottom: 0;">
          Your message has been received. We'll be in touch soon.
        </p>
      </div>
      <div style="text-align: center; margin-top: 2rem;">
        <a class="btn btn--ghost" href="/">Return Home</a>
      </div>
    <?php else: ?>

      <div style="max-width: 640px; margin: 0 auto 2rem;">
        <?php if ($s('church_phone') !== '' || $s('church_address_line1') !== ''): ?>
          <div style="background: var(--bg-soft); border-radius: 8px; padding: 1.25rem 1.5rem; text-align: center; margin-bottom: 2rem;">
            <?php if ($s('church_phone') !== ''): ?>
              <div><strong>Call us:</strong> <a href="tel:<?= $e(preg_replace('/\D+/', '', $s('church_phone'))) ?>"><?= $e($s('church_phone')) ?></a></div>
            <?php endif; ?>
            <?php if ($s('church_office_hours') !== ''): ?>
              <div style="font-size: 0.9rem; color: var(--text-muted); margin-top: 0.25rem;"><?= $e($s('church_office_hours')) ?></div>
            <?php endif; ?>
            <?php if ($s('church_address_line1') !== ''): ?>
              <div style="margin-top: 0.5rem;"><strong>Visit:</strong> <?= $e($s('church_address_line1')) ?>, <?= $e($s('church_address_city')) ?>, <?= $e($s('church_address_state')) ?> <?= $e($s('church_address_zip')) ?></div>
            <?php endif; ?>
          </div>
        <?php endif; ?>
      </div>

      <form method="post" action="/contact" class="form" style="margin-inline: auto;" novalidate>
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
          <label class="form__label" for="cm_name">Your Name <span class="req">*</span></label>
          <input
            type="text"
            id="cm_name"
            name="sender_name"
            class="form__input"
            value="<?= $e($data['sender_name'] ?? '') ?>"
            maxlength="150"
            autocomplete="name"
            required
          >
          <?php if (!empty($errors['sender_name'])): ?>
            <div class="form__error" style="margin-top: 0.5rem;"><?= $e($errors['sender_name']) ?></div>
          <?php endif; ?>
        </div>

        <div class="form__field">
          <label class="form__label">Best way to reach you <span class="req">*</span></label>
          <div class="form__radio-group">
            <label class="form__radio-option">
              <input type="radio" name="reply_method" value="email" <?= ($data['reply_method'] ?? 'email') === 'email' ? 'checked' : '' ?>>
              <span>Email</span>
            </label>
            <label class="form__radio-option">
              <input type="radio" name="reply_method" value="phone" <?= ($data['reply_method'] ?? '') === 'phone' ? 'checked' : '' ?>>
              <span>Phone</span>
            </label>
            <label class="form__radio-option">
              <input type="radio" name="reply_method" value="either" <?= ($data['reply_method'] ?? '') === 'either' ? 'checked' : '' ?>>
              <span>Either</span>
            </label>
          </div>
          <?php if (!empty($errors['reply_method'])): ?>
            <div class="form__error" style="margin-top: 0.5rem;"><?= $e($errors['reply_method']) ?></div>
          <?php endif; ?>
        </div>

        <div class="form__field">
          <label class="form__label" for="cm_email">Email</label>
          <input
            type="email"
            id="cm_email"
            name="sender_email"
            class="form__input"
            value="<?= $e($data['sender_email'] ?? '') ?>"
            maxlength="190"
            autocomplete="email"
          >
          <?php if (!empty($errors['sender_email'])): ?>
            <div class="form__error" style="margin-top: 0.5rem;"><?= $e($errors['sender_email']) ?></div>
          <?php endif; ?>
        </div>

        <div class="form__field">
          <label class="form__label" for="cm_phone">Phone</label>
          <input
            type="tel"
            id="cm_phone"
            name="sender_phone"
            class="form__input"
            value="<?= $e($data['sender_phone'] ?? '') ?>"
            maxlength="50"
            autocomplete="tel"
          >
          <?php if (!empty($errors['sender_phone'])): ?>
            <div class="form__error" style="margin-top: 0.5rem;"><?= $e($errors['sender_phone']) ?></div>
          <?php endif; ?>
        </div>

        <div class="form__field">
          <label class="form__label" for="cm_message">Your Message <span class="req">*</span></label>
          <textarea
            id="cm_message"
            name="message_text"
            class="form__textarea"
            maxlength="5000"
            required
          ><?= $e($data['message_text'] ?? '') ?></textarea>
          <?php if (!empty($errors['message_text'])): ?>
            <div class="form__error" style="margin-top: 0.5rem;"><?= $e($errors['message_text']) ?></div>
          <?php endif; ?>
        </div>

        <div style="margin-top: 2rem;">
          <button type="submit" class="btn">Send Message</button>
        </div>
      </form>

    <?php endif; ?>

  </div>
</section>
