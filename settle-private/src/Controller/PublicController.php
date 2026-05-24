<?php
declare(strict_types=1);
namespace Settle\Controller;

use Settle\Model\Page;

/**
 * Public-facing pages. The real homepage will get fancier later
 * (slideshow, upcoming events, etc.); this is just enough to prove
 * the end-to-end render pipeline works.
 */
final class PublicController extends BaseController
{
    public function home(): void
    {
        $about = Page::findBySlug('about');
        $this->render('public/home', ['about' => $about], 'public');
    }

    public function page(array $params): void
    {
        $page = Page::findBySlug((string)$params['slug']);
        if (!$page) { http_response_code(404); echo 'Page not found.'; return; }
        $this->render('public/page', ['page' => $page], 'public');
    }
}