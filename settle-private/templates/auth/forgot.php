<?php
/**
 * Password-reset request form (roadmap #6b).
 * Rendered through the self-contained 'auth' layout (no $settings/$menu_tree).
 *
 * @var string|null $notice  generic "link on its way" message (info)
 * @var string|null $error   inline validation message (e.g. empty field)
 */
?>
<div class="login-card">
  <h1>Reset password</h1>
  <p class="muted">Enter your username or email and we'll send a reset link to the account's email address.</p>

  <?php if (!empty($notice)): ?>
    <div class="flash flash-info"><?= htmlspecialchars($notice, ENT_QUOTES) ?></div>
  <?php endif; ?>

  <?php if (!empty($error)): ?>
    <div class="flash flash-error"><?= htmlspecialchars($error, ENT_QUOTES) ?></div>
  <?php endif; ?>

  <form method="post" action="/admin/forgot">
    <?= \Settle\Csrf::field() ?>

    <label>Username or email
      <input type="text" name="identifier" autocomplete="username" autofocus required>
    </label>

    <button type="submit" class="btn-primary">Send reset link</button>
  </form>

  <p class="muted"><a href="/admin/login">Back to sign in</a></p>
</div>
