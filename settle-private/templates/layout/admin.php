<?php
/** @var string $content */
/** @var array|null $_user */

use Settle\Features;
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Settle Admin</title>
<link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body class="admin">
<aside class="sidebar">
  <div class="brand">Settle Admin</div>
  <nav>
    <a href="/admin">Dashboard</a>

    <?php if (Features::enabled('pages')): ?>
      <a href="/admin/pages">Pages</a>
    <?php endif; ?>

    <?php if (Features::enabled('menu')): ?>
      <a href="/admin/menu">Menu</a>
    <?php endif; ?>

    <?php if (Features::enabled('blog')): ?>
      <a href="/admin/posts">Blog Posts</a>
      <a href="/admin/categories">Categories</a>
    <?php endif; ?>

    <?php if (Features::enabled('media')): ?>
      <a href="/admin/media">Photos</a>
    <?php endif; ?>

    <?php if (Features::enabled('slideshow')): ?>
      <a href="/admin/slideshow">Homepage Slideshow</a>
    <?php endif; ?>

    <?php if (Features::enabled('staff')): ?>
      <a href="/admin/staff">Staff Directory</a>
    <?php endif; ?>

    <?php /*
      Calendar has no admin screen yet — events sync from Google Calendar
      via cron (bin/calendar-sync.php) and render on the public /calendar
      page. When the override-editor admin UI is built (deferred), restore
      a link here pointing at /admin/calendar, gated by
      Features::enabled('calendar').
    */ ?>

    <?php if (Features::enabled('prayer')): ?>
      <?php $_prayerCounts = \Settle\Model\PrayerRequest::countByStatus(); ?>
      <a href="/admin/prayer">
        Prayer Requests
        <?php if (!empty($_prayerCounts['new'])): ?>
          <span style="background:#9E2A2B; color:#fff; font-size:0.75em;
                       padding:0.1em 0.5em; border-radius:1em; margin-left:0.3em;">
            <?= (int)$_prayerCounts['new'] ?>
          </span>
        <?php endif; ?>
      </a>
    <?php endif; ?>

    <?php if (Features::enabled('contact')): ?>
      <?php $_contactUnread = \Settle\Model\ContactMessage::countUnread(); ?>
      <a href="/admin/contact">
        Contact Messages
        <?php if ($_contactUnread > 0): ?>
          <span style="background:#9E2A2B; color:#fff; font-size:0.75em;
                       padding:0.1em 0.5em; border-radius:1em; margin-left:0.3em;">
            <?= $_contactUnread ?>
          </span>
        <?php endif; ?>
      </a>
    <?php endif; ?>
  </nav>
  <div class="signin">
    Signed in as <strong><?= htmlspecialchars($_user['display'] ?? '', ENT_QUOTES) ?></strong>
    <br>
    <form method="post" action="/admin/logout" style="display:inline">
      <?= \Settle\Csrf::field() ?>
      <button type="submit" class="linklike">Sign out</button>
    </form>
  </div>
</aside>
<main class="content">
  <?php foreach (($_SESSION['_flash'] ?? []) as $k => $msg): ?>
    <div class="flash flash-<?= htmlspecialchars($k, ENT_QUOTES) ?>">
      <?= htmlspecialchars($msg, ENT_QUOTES) ?>
    </div>
  <?php endforeach; unset($_SESSION['_flash']); ?>
  <?= $content ?>
</main>
<script src="/assets/js/admin.js" defer></script>
</body>
</html>
