<?php /** @var array $page */ ?>
<div style="max-width:900px; margin:2em auto; padding:1em;">
  <h1 style="color:var(--brand-red);"><?= htmlspecialchars($page['title'], ENT_QUOTES) ?></h1>
  <div><?= $page['body_html'] /* trusted */ ?></div>
  <p style="margin-top:3em;"><a href="/">← Home</a></p>
</div>