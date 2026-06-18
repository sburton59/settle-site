<?php
/**
 * Shared book chrome.
 *
 * Rendered through \Settle\PublicView::render('public/book', [...]) so the
 * site $settings + $menu_tree (header, nav, footer) wrap it. Its only job is
 * to pull in the shared book CSS once and then include the per-book content
 * fragment named by the registry — so every book template stays pure content
 * and the typography lives in exactly one place (books/_styles.php).
 *
 * Receives from BooksController::show():
 *   array  $book          registry meta: title, subtitle, year, view
 *   string $content_file  path under templates/public/ of the content
 *                         fragment, e.g. 'books/our-church.php'
 *   string $page_title    <title> text (set by the controller)
 *   Closure $e            htmlspecialchars helper (from View::render)
 *
 * $content_file is a server-controlled constant from the registry — never
 * request input — so including it is safe. The included fragment is trusted,
 * hand-authored HTML and is emitted as-is (the body_html convention, §9).
 *
 * @var array   $book
 * @var string  $content_file
 * @var string  $page_title
 * @var Closure $e
 */

require __DIR__ . '/books/_styles.php';
require __DIR__ . '/' . $content_file;
