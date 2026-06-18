<?php
/**
 * Library index for the books feature (GET /books).
 *
 * Rendered through \Settle\PublicView::render('public/books_index', [...]) so
 * the site $settings + $menu_tree (header, nav, footer) wrap it. Reuses the
 * shared book typography (books/_styles.php) so the index sits in the same
 * cream-paper world as the books themselves.
 *
 * Receives from BooksController::library():
 *   array<int, array{slug:string,title:string,subtitle:string,year:string,view:string,cover?:string}> $books
 *   string  $page_title
 *   Closure $e   htmlspecialchars helper (from View::render)
 *
 * $books comes from the server-side registry (never request input). All
 * values are escaped with $e() on output regardless, per §9. Each book's
 * 'cover' (if set) is an absolute URL to a static cover image used as the
 * button face; a book without a cover degrades to a bordered text card.
 *
 * @var array   $books
 * @var string  $page_title
 * @var Closure $e
 */

require __DIR__ . '/books/_styles.php';
?>
<article class="book">
  <div class="book__page book__library">

    <header class="book__cover">
      <p class="pre">Settle Memorial United Methodist Church</p>
      <h1>Library</h1>
      <p class="sub">Histories of the church and its people</p>
      <div class="orn">&#10086; &#10087; &#10086;</div>
    </header>

    <hr class="rule2">

    <p class="lead">Web editions of historical booklets from the life of Settle Memorial, set from the original printings. Choose a title to read.</p>

    <ul class="shelf">
      <?php foreach ($books as $b): ?>
      <?php $cover = (string) ($b['cover'] ?? ''); ?>
      <li>
        <a href="/books/<?= $e($b['slug']) ?>" class="book-link<?= $cover === '' ? ' no-cover' : '' ?>">
          <?php if ($cover !== ''): ?>
          <img class="cover" src="<?= $e($cover) ?>"
               alt="Cover of <?= $e($b['title']) ?><?= $b['subtitle'] !== '' ? ' — ' . $e($b['subtitle']) : '' ?>">
          <?php endif; ?>
          <span class="caption">
            <span class="stitle"><?= $e($b['title']) ?></span>
            <?php if ($b['subtitle'] !== ''): ?>
            <span class="ssub"><?= $e($b['subtitle']) ?></span>
            <?php endif; ?>
            <span class="syear"><?= $e($b['year']) ?></span>
          </span>
        </a>
      </li>
      <?php endforeach; ?>
    </ul>

  </div>
</article>
