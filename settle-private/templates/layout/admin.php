<?php
/** @var string $content */
/** @var array|null $_user */
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
    <a href="/admin/pages">Pages</a>
    <a href="/admin/posts">Blog Posts</a>
    <a href="/admin/media">Photos</a>
    <a href="/admin/slideshow">Homepage Slideshow</a>
    <a href="/admin/staff">Staff Directory</a>
    <a href="/admin/calendar">Calendar &amp; Events</a>
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
	<?php $contactUnread = \Settle\Model\ContactMessage::countUnread(); ?>
	<a href="/admin/contact">
	  Contact Messages
	  <?php if ($contactUnread > 0): ?>
		<span class="nav-badge"><?= $contactUnread ?></span>
	  <?php endif; ?>
	</a>
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