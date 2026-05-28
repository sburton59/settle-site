<?php
declare(strict_types=1);
namespace Settle\Controller;

use Settle\Model\Page;
use Settle\Model\Slideshow;
use Settle\Model\Staff;
use Settle\PublicView;

/**
 * Public-facing pages.
 *
 * All public rendering goes through \Settle\PublicView::render(), which
 * wraps View::render() and injects $settings and $menu_tree into the
 * template scope. This keeps the controllers thin and ensures every
 * public template has the data the public layout (templates/layout/
 * public.php) expects.
 */
final class PublicController extends BaseController
{
    public function home(): void
    {
        $about  = Page::findBySlug('about');
        $slides = Slideshow::active();

        PublicView::render('public/home', [
            'about'  => $about,
            'slides' => $slides,
        ]);
    }

    public function page(array $params): void
    {
        $page = Page::findBySlug((string)$params['slug']);
        if (!$page) {
            http_response_code(404);
            echo 'Page not found.';
            return;
        }

        PublicView::render('public/page', [
            'page'       => $page,
            'page_title' => (string)$page['title'],
        ]);
    }

    public function staff(): void
    {
        $staff = Staff::allVisible();

        PublicView::render('public/staff', [
            'staff'      => $staff,
            'page_title' => 'Our Staff',
        ]);
    }
}
