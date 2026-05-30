<?php
/**
 * Authentication layout (login screen and other pre-auth pages).
 *
 * Deliberately self-contained and decoupled from the public theme.
 * Before v1.7 the login screen rendered through the public layout.
 * When v1.7 added the themed public layout — which requires $settings
 * and $menu_tree, injected only by \Settle\PublicView::render() —
 * rendering login through the 'public' layout via
 * BaseController::render() left those undefined, and the desktop-nav
 * closure (typed `array $items`) fataled on null at <nav>, truncating
 * the page. This layout removes that coupling: it loads only admin.css
 * and depends on no injected site data, so future public-theme changes
 * cannot break login again.
 *
 * Receives from \Settle\View::render():
 *   string  $content — rendered template HTML (e.g. auth/login)
 *   Closure $e       — htmlspecialchars helper
 *
 * Card styling (.login-card, .btn-primary, .flash, .muted) lives in
 * admin.css. The page-centering rule below is inline to keep this
 * layout self-contained: it intentionally does NOT reuse the
 * `body.public` rule in admin.css, which now belongs to the public
 * theme, nor does it modify admin.css.
 *
 * @var string  $content
 * @var Closure $e
 */
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Sign In</title>
<link rel="stylesheet" href="/assets/css/admin.css">
<style>
  /* Page-centering for the login card. Mirrors the legacy body.public
     rule that admin.css used when login still rendered through the
     public layout. Scoped to body.auth so it is independent of the
     public theme. */
  body.auth {
    background: var(--gray-100);
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1em;
  }
</style>
</head>
<body class="auth">
<?= $content ?>
</body>
</html>
