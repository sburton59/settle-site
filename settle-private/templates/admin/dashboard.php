<?php
/**
 * @var array      $_user
 * @var bool|null  $rate_limiter_ok  true = healthy, false = storage broken,
 *                                    null = not checked (non-admin viewer)
 */
$rate_limiter_ok = $rate_limiter_ok ?? null;
?>
<h1>Welcome back, <?= htmlspecialchars($_user['display'], ENT_QUOTES) ?></h1>

<?php if ($rate_limiter_ok === false): ?>
  <div class="flash flash-warning">
    <strong>Login protection is not active.</strong>
    The login rate-limiter can&rsquo;t reach its <code>login_attempts</code> table,
    so repeated failed sign-ins are <em>not</em> being throttled. Apply
    <code>sql/migrations/0004_add_login_attempts.sql</code> to the database, then
    reload this page. See <code>storage/logs/php-error.log</code> for details.
  </div>
<?php endif; ?>

<p>This is the admin dashboard. Use the menu on the left to update the website.</p>
<p class="muted">More widgets (recent posts, unread prayer requests, upcoming events) will appear here as those modules are built.</p>
