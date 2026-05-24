<?php /** @var array|null $about */ ?>
<div style="max-width:900px; margin:2em auto; padding:1em;">
  <h1 style="color:var(--brand-red);">Settle Memorial United Methodist Church</h1>
  <p class="muted">Owensboro, Kentucky</p>

  <?php if ($about): ?>
    <div style="margin-top:2em;">
      <?= $about['body_html'] /* trusted: written by staff via admin */ ?>
      <p><a href="/page/about">Read more about us →</a></p>
    </div>
  <?php else: ?>
    <p>Welcome. The site is being set up.</p>
  <?php endif; ?>

  <p style="margin-top:3em;" class="muted">
    <a href="/admin">Staff login</a>
  </p>
</div>