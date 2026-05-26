<?php
/** @var array $errors Field-keyed validation errors (or []) */
/** @var array $data   Posted form values to redisplay (or blanks) */
/** @var bool  $success True when the submission was accepted */

$nameVal    = htmlspecialchars((string)($data['sender_name']  ?? ''), ENT_QUOTES);
$emailVal   = htmlspecialchars((string)($data['sender_email'] ?? ''), ENT_QUOTES);
$phoneVal   = htmlspecialchars((string)($data['sender_phone'] ?? ''), ENT_QUOTES);
$messageVal = htmlspecialchars((string)($data['message_text'] ?? ''), ENT_QUOTES);
$reply      = (string)($data['reply_method'] ?? 'email');
if (!in_array($reply, ['email', 'phone', 'either'], true)) {
    $reply = 'email';
}
?>
<div style="max-width:640px; margin:2em auto; padding:1em;">
  <h1>Contact Us</h1>

  <?php if ($success): ?>
    <div style="background:#d4edda; color:#155724; padding:1.5em;
                border-radius:4px; margin-bottom:1em;">
      <p style="margin:0;">
        <strong>Thank you.</strong> Your message has been received, and a
        member of our staff will be in touch with you soon.
      </p>
    </div>
    <p>
      <a href="/">Return to the homepage</a> ·
      <a href="/contact">Send another message</a>
    </p>

  <?php else: ?>
    <p style="color:#555; line-height:1.5;">
      Have a question, a comment, or want to learn more about Settle Memorial?
      Use the form below and we'll get back to you. You can also reach the
      church office at <strong>(270) 684-4226</strong>.
    </p>

    <form method="post" action="/contact" novalidate
          style="background:#fff; padding:1.5em; border-radius:4px;
                 box-shadow:0 1px 3px rgba(0,0,0,0.05); margin-top:1em;">

      <?= \Settle\Csrf::field() ?>

      <!--
        Honeypot field. Real browsers don't see it, but bots that
        auto-fill every input will. Server treats any non-empty value
        as a silent drop. Position-absolute keeps it out of layout,
        tabindex=-1 keeps it out of tab order, autocomplete=off keeps
        browsers from helpfully prefilling it.
      -->
      <div style="position:absolute; left:-9999px; top:auto;
                  width:1px; height:1px; overflow:hidden;"
           aria-hidden="true">
        <label for="website">Website (leave blank)</label>
        <input type="text" id="website" name="website" value=""
               tabindex="-1" autocomplete="off">
      </div>

      <!-- Name -->
      <div style="margin-bottom:1em;">
        <label for="sender_name" style="display:block; font-weight:500; margin-bottom:0.3em;">
          Your name <span style="color:var(--error);">*</span>
        </label>
        <input type="text" id="sender_name" name="sender_name"
               value="<?= $nameVal ?>"
               maxlength="150" required
               style="width:100%; padding:0.5em; box-sizing:border-box;
                      border:1px solid #ccc; border-radius:3px;">
        <?php if (!empty($errors['sender_name'])): ?>
          <div style="color:var(--error); font-size:0.9em; margin-top:0.3em;">
            <?= htmlspecialchars($errors['sender_name'], ENT_QUOTES) ?>
          </div>
        <?php endif; ?>
      </div>

      <!-- Email -->
      <div style="margin-bottom:1em;">
        <label for="sender_email" style="display:block; font-weight:500; margin-bottom:0.3em;">
          Email address
        </label>
        <input type="email" id="sender_email" name="sender_email"
               value="<?= $emailVal ?>"
               maxlength="190"
               style="width:100%; padding:0.5em; box-sizing:border-box;
                      border:1px solid #ccc; border-radius:3px;">
        <?php if (!empty($errors['sender_email'])): ?>
          <div style="color:var(--error); font-size:0.9em; margin-top:0.3em;">
            <?= htmlspecialchars($errors['sender_email'], ENT_QUOTES) ?>
          </div>
        <?php endif; ?>
      </div>

      <!-- Phone -->
      <div style="margin-bottom:1em;">
        <label for="sender_phone" style="display:block; font-weight:500; margin-bottom:0.3em;">
          Phone number
        </label>
        <input type="tel" id="sender_phone" name="sender_phone"
               value="<?= $phoneVal ?>"
               maxlength="50"
               style="width:100%; padding:0.5em; box-sizing:border-box;
                      border:1px solid #ccc; border-radius:3px;">
        <?php if (!empty($errors['sender_phone'])): ?>
          <div style="color:var(--error); font-size:0.9em; margin-top:0.3em;">
            <?= htmlspecialchars($errors['sender_phone'], ENT_QUOTES) ?>
          </div>
        <?php endif; ?>
      </div>

      <!-- Reply method -->
      <fieldset style="margin-bottom:1em; padding:0.8em 1em;
                       background:#f8f8f8; border:1px solid #e5e5e5;
                       border-radius:3px;">
        <legend style="font-weight:500; padding:0 0.4em;">
          How should we get back to you?
        </legend>
        <label style="display:block; margin:0.3em 0; cursor:pointer;">
          <input type="radio" name="reply_method" value="email"
                 <?= $reply === 'email' ? 'checked' : '' ?>>
          By email
        </label>
        <label style="display:block; margin:0.3em 0; cursor:pointer;">
          <input type="radio" name="reply_method" value="phone"
                 <?= $reply === 'phone' ? 'checked' : '' ?>>
          By phone
        </label>
        <label style="display:block; margin:0.3em 0; cursor:pointer;">
          <input type="radio" name="reply_method" value="either"
                 <?= $reply === 'either' ? 'checked' : '' ?>>
          Either is fine
        </label>
        <?php if (!empty($errors['reply_method'])): ?>
          <div style="color:var(--error); font-size:0.9em; margin-top:0.3em;">
            <?= htmlspecialchars($errors['reply_method'], ENT_QUOTES) ?>
          </div>
        <?php endif; ?>
      </fieldset>

      <!-- Message body -->
      <div style="margin-bottom:1em;">
        <label for="message_text" style="display:block; font-weight:500; margin-bottom:0.3em;">
          Your message <span style="color:var(--error);">*</span>
        </label>
        <textarea id="message_text" name="message_text" rows="8"
                  maxlength="5000" required
                  style="width:100%; padding:0.5em; box-sizing:border-box;
                         border:1px solid #ccc; border-radius:3px;
                         font-family:inherit; font-size:1em; resize:vertical;"
        ><?= $messageVal ?></textarea>
        <?php if (!empty($errors['message_text'])): ?>
          <div style="color:var(--error); font-size:0.9em; margin-top:0.3em;">
            <?= htmlspecialchars($errors['message_text'], ENT_QUOTES) ?>
          </div>
        <?php endif; ?>
      </div>

      <!-- Submit -->
      <button type="submit" class="btn-primary"
              style="padding:0.7em 1.5em; font-size:1em; cursor:pointer;">
        Send message
      </button>
    </form>

  <?php endif; ?>
</div>
