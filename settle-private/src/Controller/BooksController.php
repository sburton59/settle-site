<?php
declare(strict_types=1);

namespace Settle\Controller;

use Settle\PublicView;

/**
 * Public "books" feature — long-form historical reprints set as standalone
 * web editions (cream-paper book typography), distinct from the WYSIWYG Pages
 * surface so their hand-set markup never passes through TinyMCE.
 *
 * Content lives as template fragments under templates/public/books/{slug}.php
 * (a §9 owner decision: these are fixed reprints, version-controlled, with no
 * admin-edit surface). Each book is one row in self::BOOKS; adding a book is
 * one registry entry plus one content fragment — no route or schema change.
 *
 * Routing (public, no auth/role gate; not Features-flagged — like '/', it is
 * always on):
 *   GET /books/{slug}  -> show()      single book
 *
 * A '/books' library index is intentionally NOT wired yet (owner decision:
 * hold until a second book exists). When it lands it is a library() method,
 * one template, and one route line — the registry already supports it.
 *
 * Rendering goes through PublicView::render() so $settings + $menu_tree inject
 * (the §13.10 rule — never View::render(.., 'public') directly). Data keys
 * avoid 'data'/'template'/'layout' (the §13.9 EXTR_SKIP collision).
 *
 * @see PROJECT_HANDOFF.md §9, §13.9, §13.10
 */
final class BooksController extends BaseController
{
    /**
     * The book library. Keyed by URL slug.
     *
     *   title    — display/<title> name
     *   subtitle — shown on the book's own cover (in the content fragment)
     *   year     — publication/reprint year, for an eventual library index
     *   view     — content fragment under templates/public/, no extension
     *
     * @var array<string, array{title:string, subtitle:string, year:string, view:string}>
     */
    private const BOOKS = [
        'our-church' => [
            'title'    => 'Our Church',
            'subtitle' => "A Story of a Hundred Years' Service",
            'year'     => '1976',
            'view'     => 'books/our-church',
        ],
    ];

    /**
     * Single book. Unknown slug -> 404 (mirrors PublicController::post()).
     *
     * @param array{slug?:string} $params
     */
    public function show(array $params): void
    {
        $slug = (string) ($params['slug'] ?? '');
        $book = self::BOOKS[$slug] ?? null;

        if ($book === null) {
            http_response_code(404);
            echo 'Book not found.';
            return;
        }

        PublicView::render('public/book', [
            'page_title'   => $book['title'],
            'book'         => $book,
            'content_file' => $book['view'] . '.php',
        ]);
    }
}
