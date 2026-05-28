<?php
/**
 * Generic public page renderer.
 *
 * @var array   $page       From PublicController::page()
 * @var array   $settings   From PublicView
 * @var array   $menu_tree  From PublicView
 * @var Closure $e
 */
?>

<section class="page-intro">
  <div class="container">
    <h1><?= $e($page['title']) ?></h1>
  </div>
</section>

<section class="section">
  <div class="container">
    <article class="prose" style="margin-inline: auto;">
      <?php
        // body_html is intentionally trusted (only authenticated editors
        // can write it — see PROJECT_HANDOFF.md §3.5).
        echo $page['body_html'] ?? '';
      ?>
    </article>
  </div>
</section>
