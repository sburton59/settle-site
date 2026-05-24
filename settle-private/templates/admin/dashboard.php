<?php /** @var array $_user */ ?>
<h1>Welcome back, <?= htmlspecialchars($_user['display'], ENT_QUOTES) ?></h1>
<p>This is the admin dashboard. Use the menu on the left to update the website.</p>
<p class="muted">More widgets (recent posts, unread prayer requests, upcoming events) will appear here as those modules are built.</p>