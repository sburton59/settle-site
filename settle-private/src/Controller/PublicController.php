<?php
declare(strict_types=1);
namespace Settle\Controller;

use Settle\Auth;
use Settle\Features;
use Settle\Model\CalendarEvent;
use Settle\Model\Category;
use Settle\Model\Page;
use Settle\Model\Post;
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
    /** Posts per page on the blog listing and category archives. */
    private const BLOG_PER_PAGE = 9;

    public function home(): void
    {
        $about  = Page::findBySlug('about');
        $slides = Slideshow::active();

        // Upcoming-events widget data. Only populated when the calendar
        // feature is on; the homepage template guards on a non-empty list,
        // so a disabled or empty calendar simply renders no widget.
        $events = [];
        if (Features::enabled('calendar')) {
            $cfg    = $GLOBALS['settle_config']['google_calendar'] ?? [];
            $count  = (int)($cfg['homepage_count'] ?? 3);
            $events = CalendarEvent::upcoming($count, 90);
        }

        PublicView::render('public/home', [
            'about'  => $about,
            'slides' => $slides,
            'events' => $events,
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

    /**
     * Public calendar — a month grid plus a chronological list of that
     * month's events. The month is taken from ?ym=YYYY-MM (defaults to the
     * current month). All event data comes from the local cache via
     * CalendarEvent::forMonth(), which overlays website-only overrides;
     * Google is never contacted during a page view.
     */
    public function calendar(): void
    {
        // Parse ?ym=YYYY-MM, falling back to the current month. Clamp to a
        // sane range so a hand-edited URL can't wander off.
        $now   = new \DateTime('first day of this month');
        $year  = (int)$now->format('Y');
        $month = (int)$now->format('n');

        $ym = (string)($_GET['ym'] ?? '');
        if ($ym !== '' && preg_match('/^(\d{4})-(\d{1,2})$/', $ym, $m)) {
            $y  = (int)$m[1];
            $mo = (int)$m[2];
            if ($y >= 2000 && $y <= 2100 && $mo >= 1 && $mo <= 12) {
                $year  = $y;
                $month = $mo;
            }
        }

        $events = CalendarEvent::forMonth($year, $month);

        // Group events by Y-m-d for quick lookup while drawing the grid.
        // A multi-day event appears on each day it spans within the month.
        $eventsByDay = [];
        foreach ($events as $ev) {
            $start = new \DateTime((string)$ev['starts_at']);
            $end   = !empty($ev['ends_at']) ? new \DateTime((string)$ev['ends_at']) : clone $start;
            if ($end < $start) {
                $end = clone $start;
            }
            $cursor = (clone $start)->setTime(0, 0, 0);
            $last   = (clone $end)->setTime(0, 0, 0);
            $guard  = 0;
            while ($cursor <= $last && $guard++ < 366) {
                $eventsByDay[$cursor->format('Y-m-d')][] = $ev;
                $cursor->modify('+1 day');
            }
        }

        $current = (new \DateTime())->setDate($year, $month, 1)->setTime(0, 0, 0);
        $prev    = (clone $current)->modify('-1 month');
        $next    = (clone $current)->modify('+1 month');

        PublicView::render('public/calendar', [
            'page_title'    => 'Calendar',
            'cal_year'      => $year,
            'cal_month'     => $month,
            'cal_current'   => $current,
            'cal_prev_ym'   => $prev->format('Y-m'),
            'cal_next_ym'   => $next->format('Y-m'),
            'events'        => $events,
            'events_by_day' => $eventsByDay,
        ]);
    }

    /**
     * Public blog listing — published posts, newest first, paginated.
     * Also reused for category archives via the shared 'public/blog'
     * template (see blogCategory()).
     */
    public function blog(): void
    {
        $page    = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = self::BLOG_PER_PAGE;
        $offset  = ($page - 1) * $perPage;

        $total      = Post::publishedCount();
        $posts      = Post::publishedList($perPage, $offset);
        $totalPages = max(1, (int) ceil($total / $perPage));

        $this->attachCategories($posts);

        PublicView::render('public/blog', [
            'page_title'   => 'Blog',
            'posts'        => $posts,
            'current_page' => $page,
            'total_pages'  => $totalPages,
            'category'     => null,
            'base_path'    => '/blog',
            'all_categories' => Category::allForPicker(),
        ]);
    }

    /**
     * Public category archive — published posts in one category. 404s on an
     * unknown category slug. Renders through the same 'public/blog' template
     * with a category context.
     */
    public function blogCategory(array $params): void
    {
        $category = Category::findBySlug((string) $params['slug']);
        if (!$category) {
            http_response_code(404);
            echo 'Category not found.';
            return;
        }

        $page    = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = self::BLOG_PER_PAGE;
        $offset  = ($page - 1) * $perPage;

        $cid        = (int) $category['id'];
        $total      = Post::publishedCountByCategory($cid);
        $posts      = Post::publishedListByCategory($cid, $perPage, $offset);
        $totalPages = max(1, (int) ceil($total / $perPage));

        $this->attachCategories($posts);

        PublicView::render('public/blog', [
            'page_title'     => $category['name'] . ' — Blog',
            'posts'          => $posts,
            'current_page'   => $page,
            'total_pages'    => $totalPages,
            'category'       => $category,
            'base_path'      => '/blog/category/' . $category['slug'],
            'all_categories' => Category::allForPicker(),
        ]);
    }

    /**
     * Public single post. A published, past-dated post is shown to everyone.
     * A post that is NOT yet publicly visible (scheduled for the future, or a
     * draft/archived) is shown only to signed-in staff who are allowed to
     * preview it — the post's own author, or any editor — with a banner making
     * clear it isn't live yet. Everyone else (and anonymous visitors) gets 404.
     */
    public function post(array $params): void
    {
        $slug = (string) $params['slug'];

        $post      = Post::findBySlugPublished($slug); // live to the public
        $isPreview = false;

        if (!$post) {
            $candidate = Post::findBySlugAny($slug);
            if ($candidate !== null && $this->canPreview($candidate)) {
                $post      = $candidate;
                $isPreview = true;
            }
        }

        if (!$post) {
            http_response_code(404);
            echo 'Post not found.';
            return;
        }

        $post['categories'] = Post::categoriesFor((int) $post['id']);

        PublicView::render('public/post', [
            'page_title' => (string) $post['title'],
            'post'       => $post,
            'is_preview' => $isPreview,
        ]);
    }

    /**
     * May the current viewer preview a not-yet-live post? Only signed-in
     * staff: any editor+, or the post's own author. Mirrors the in-code
     * ownership rule used in the admin (PostController).
     */
    private function canPreview(array $post): bool
    {
        if (!Auth::check()) {
            return false;
        }
        if (Auth::hasRole('editor')) {
            return true;
        }
        return (int) $post['author_id'] === (int) ($_SESSION['user_id'] ?? 0);
    }

    /**
     * Attach each post's category list (name/slug pairs) for the listing
     * chips. The per-page count is small (BLOG_PER_PAGE), so the extra
     * lookups are cheap and keep the listing query simple.
     *
     * @param array<int, array> $posts passed by reference
     */
    private function attachCategories(array &$posts): void
    {
        foreach ($posts as &$p) {
            $p['categories'] = Post::categoriesFor((int) $p['id']);
        }
        unset($p);
    }
}
