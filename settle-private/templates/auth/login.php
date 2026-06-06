<?php
/** @var string|null $error */
/** @var string $return */
?>
<div class="login-card">
  <h1>Settle Admin</h1>
  <p class="muted">Sign in to update the website.</p>

  <?php if (!empty($error)): ?>
    <div class="flash flash-error"><?= htmlspecialchars($error, ENT_QUOTES) ?></div>
  <?php endif; ?>

  <form method="post" action="/admin/login">
    <?= \Settle\Csrf::field() ?>
    <input type="hidden" name="return" value="<?= htmlspecialchars($return, ENT_QUOTES) ?>">

    <label>Username or email
      <input type="text" name="username" autocomplete="username" autofocus required>
    </label>

    <label>Password
      <input type="password" name="password" autocomplete="current-password" required>
    </label>

    <label class="checkbox">
      <input type="checkbox" name="remember" value="1">
      Keep me signed in for 30 days
    </label>

    <button type="submit" class="btn-primary">Sign In</button>
  </form>

  <p class="muted"><a href="/admin/forgot">Forgot your password?</a></p>
</div>