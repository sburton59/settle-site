<?php
/**
 * Set-a-new-password form (roadmap #6b). Three states:
 *   $done  === true   reset succeeded — show confirmation + Sign in link
 *   $valid === true   token is good   — show the new-password form
 *   $valid === false  token bad/expired/used — show a friendly dead-end
 *
 * Rendered through the self-contained 'auth' layout.
 *
 * @var bool        $valid
 * @var bool        $done
 * @var string      $token   raw token, re-posted in a hidden field
 * @var string|null $error   inline validation message
 */
?>
<div class="login-card">

  <?php if (!empty($done)): ?>
    <h1>Password updated</h1>
    <div class="flash flash-success">Your password has been reset.</div>
    <p><a href="/admin/login" class="btn-primary">Sign in</a></p>

  <?php elseif (empty($valid)): ?>
    <h1>Link expired</h1>
    <p class="muted">This reset link is invalid, already used, or has expired. Reset links are good for 15 minutes.</p>
    <p><a href="/admin/forgot">Request a new link</a></p>

  <?php else: ?>
    <h1>Choose a new password</h1>

    <?php if (!empty($error)): ?>
      <div class="flash flash-error"><?= htmlspecialchars($error, ENT_QUOTES) ?></div>
    <?php endif; ?>

    <form method="post" action="/admin/reset">
      <?= \Settle\Csrf::field() ?>
      <input type="hidden" name="token" value="<?= htmlspecialchars($token, ENT_QUOTES) ?>">

      <label>New password
        <input type="password" name="password" autocomplete="new-password" autofocus required minlength="12">
      </label>

      <label>Confirm new password
        <input type="password" name="password_confirm" autocomplete="new-password" required minlength="12">
      </label>

      <p class="muted">Use at least 12 characters.</p>

      <button type="submit" class="btn-primary">Set new password</button>
    </form>
  <?php endif; ?>

</div>
