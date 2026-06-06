<?php
declare(strict_types=1);
namespace Settle\Controller;

use Settle\Auth;
use Settle\CalendarFormat;
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
    /** Max own-events shown in a month-grid cell before a "+N More" link. */
    private const CAL_MAX_PER_DAY = 3;

    /**
     * Public calendar — month grid with spanning multi-day bars (roadmap
     * #8a). Multi-day and all-day events render as horizontal bars across
     * the days they cover; single-day timed events render as time+title
     * entries inside the day cell. The full visible grid (including
     * spillover days from adjacent months) is fetched so bars that begin
     * in the previous month still draw correctly.
     */
    public function calendar(): void
    {
        // Parse ?ym=YYYY-MM, falling back to the current month. Clamp.
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

        $first     = (new \DateTime())->setDate($year, $month, 1)->setTime(0, 0, 0);
        $lastDay   = (clone $first)->modify('last day of this month');
        // Expand to whole weeks (Sun..Sat) so bars can span cleanly.
        $gridStart = (clone $first)->modify('-' . (int)$first->format('w') . ' days');
        $gridEnd   = (clone $lastDay)->modify('+' . (6 - (int)$lastDay->format('w')) . ' days');

        $events = CalendarEvent::forRange($gridStart->format('Y-m-d'), $gridEnd->format('Y-m-d'));
        $weeks  = $this->buildMonthWeeks($month, $gridStart, $gridEnd, $events);

        $prev = (clone $first)->modify('-1 month');
        $next = (clone $first)->modify('+1 month');

        PublicView::render('public/calendar', [
            'page_title'  => 'Calendar',
            'cal_current' => $first,
            'cal_prev_ym' => $prev->format('Y-m'),
            'cal_next_ym' => $next->format('Y-m'),
            'cal_weeks'   => $weeks,
            'has_events'  => $events !== [],
            'subscribe'   => $this->subscribeUrls(),
            'cal_view'    => 'month',
        ]);
    }

    /**
     * Build the month grid as an array of weeks. Each week carries its day
     * cells (single-day timed events + per-day overflow) and its
     * lane-assigned multi-day/all-day bars.
     *
     * @param array<int,array<string,mixed>> $events events overlapping the grid
     * @return array<int,array<string,mixed>>
     */
    private function buildMonthWeeks(int $month, \DateTime $gridStart, \DateTime $gridEnd, array $events): array
    {
        $todayYmd = (new \DateTime('today'))->format('Y-m-d');

        // Split into spanning "bars" (multi-day OR all-day) and in-cell
        // "singles" (single-day timed events).
        $bars         = [];
        $singlesByDay = [];
        foreach ($events as $ev) {
            $startDt = new \DateTime((string)$ev['starts_at']);
            $endRaw  = !empty($ev['ends_at']) ? new \DateTime((string)$ev['ends_at']) : clone $startDt;
            if ($endRaw < $startDt) {
                $endRaw = clone $startDt;
            }
            $startDate = (clone $startDt)->setTime(0, 0, 0);
            $endDate   = (clone $endRaw)->setTime(0, 0, 0);

            $isBar = !empty($ev['is_all_day'])
                  || ($startDate->format('Y-m-d') !== $endDate->format('Y-m-d'));
            if ($isBar) {
                $bars[] = ['ev' => $ev, 'start' => $startDate, 'end' => $endDate];
            } else {
                $singlesByDay[$startDate->format('Y-m-d')][] = $ev;
            }
        }

        $weeks  = [];
        $cursor = clone $gridStart;
        $guard  = 0;
        while ($cursor <= $gridEnd && $guard++ < 60) {
            $weekStart = clone $cursor;
            $weekEnd   = (clone $cursor)->modify('+6 days');

            $days = [];
            for ($i = 0; $i < 7; $i++) {
                $d   = (clone $weekStart)->modify("+{$i} days");
                $ymd = $d->format('Y-m-d');
                $days[] = [
                    'date'     => $ymd,
                    'day'      => (int)$d->format('j'),
                    'in_month' => ((int)$d->format('n') === $month),
                    'is_today' => ($ymd === $todayYmd),
                    'col'      => $i,
                    'singles'  => $singlesByDay[$ymd] ?? [],
                ];
            }

            // Bars overlapping this week, clipped to the week's columns.
            $weekBars = [];
            foreach ($bars as $b) {
                if ($b['end'] < $weekStart || $b['start'] > $weekEnd) {
                    continue;
                }
                $clipStart = $b['start'] < $weekStart ? clone $weekStart : clone $b['start'];
                $clipEnd   = $b['end']   > $weekEnd   ? clone $weekEnd   : clone $b['end'];
                $startCol  = (int)$clipStart->format('w');
                $endCol    = (int)$clipEnd->format('w');
                $weekBars[] = [
                    'ev'              => $b['ev'],
                    'start_col'       => $startCol,
                    'span'            => $endCol - $startCol + 1,
                    'continues_left'  => $b['start'] < $weekStart,
                    'continues_right' => $b['end']   > $weekEnd,
                    'link_ymd'        => $clipStart->format('Y-m-d'),
                ];
            }
            // Pack into lanes: earliest column first, longest span first.
            usort($weekBars, static function (array $a, array $b): int {
                return [$a['start_col'], -$a['span']] <=> [$b['start_col'], -$b['span']];
            });
            $laneEnds = []; // lane index => last occupied column
            foreach ($weekBars as &$wb) {
                $placed = false;
                foreach ($laneEnds as $li => $endC) {
                    if ($wb['start_col'] > $endC) {
                        $wb['lane']    = $li;
                        $laneEnds[$li] = $wb['start_col'] + $wb['span'] - 1;
                        $placed = true;
                        break;
                    }
                }
                if (!$placed) {
                    $li = count($laneEnds);
                    $wb['lane']    = $li;
                    $laneEnds[$li] = $wb['start_col'] + $wb['span'] - 1;
                }
            }
            unset($wb);

            // Per-day single overflow, accounting for bars passing through.
            foreach ($days as &$day) {
                $col = $day['col'];
                $barsThrough = 0;
                foreach ($weekBars as $wb) {
                    if ($col >= $wb['start_col'] && $col <= ($wb['start_col'] + $wb['span'] - 1)) {
                        $barsThrough++;
                    }
                }
                $budget = max(0, self::CAL_MAX_PER_DAY - $barsThrough);
                $day['shown']    = array_slice($day['singles'], 0, $budget);
                $day['overflow'] = max(0, count($day['singles']) - $budget);
            }
            unset($day);

            $weeks[] = [
                'days'       => $days,
                'bars'       => $weekBars,
                'lane_count' => count($laneEnds),
            ];

            $cursor->modify('+7 days');
        }

        return $weeks;
    }

    /**
     * Public calendar — list view (roadmap #8a). Upcoming (current-or-
     * future) events in chronological order, paginated like the blog.
     */
    public function calendarList(): void
    {
        $perPage = 25;
        $page    = max(1, (int)($_GET['p'] ?? 1));

        $total = CalendarEvent::countUpcoming();
        $pages = max(1, (int)ceil($total / $perPage));
        if ($page > $pages) {
            $page = $pages;
        }
        $offset = ($page - 1) * $perPage;
        $events = CalendarEvent::upcomingList($perPage, $offset);

        PublicView::render('public/calendar_list', [
            'page_title' => 'Upcoming Events',
            'events'     => $events,
            'page'       => $page,
            'pages'      => $pages,
            'total'      => $total,
            'per_page'   => $perPage,
            'subscribe'  => $this->subscribeUrls(),
            'cal_view'   => 'list',
        ]);
    }

    /**
     * Public calendar — single day view (roadmap #8a). Reached from the
     * month grid (day numbers, entries, "+N More") and homepage cards. An
     * invalid date redirects back to the month grid.
     *
     * @param array<string,string> $params route params ('date' => 'Y-m-d')
     */
    public function calendarDay(array $params): void
    {
        $date = (string)($params['date'] ?? '');
        if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $date, $m)
            || !checkdate((int)$m[2], (int)$m[3], (int)$m[1])) {
            $this->redirect('/calendar');
            return;
        }

        $d      = (new \DateTime())->setDate((int)$m[1], (int)$m[2], (int)$m[3])->setTime(0, 0, 0);
        $events = CalendarEvent::forDay($date);
        $prev   = (clone $d)->modify('-1 day');
        $next   = (clone $d)->modify('+1 day');

        PublicView::render('public/calendar_day', [
            'page_title' => $d->format('F j, Y'),
            'cal_day'    => $d,
            'events'     => $events,
            'prev_ymd'   => $prev->format('Y-m-d'),
            'next_ymd'   => $next->format('Y-m-d'),
            'month_ym'   => $d->format('Y-m'),
            'subscribe'  => $this->subscribeUrls(),
            'cal_view'   => 'day',
        ]);
    }

    /** Subscribe links for the configured public calendar (empty if unset). */
    private function subscribeUrls(): array
    {
        $cfg = $GLOBALS['settle_config']['google_calendar'] ?? [];
        return CalendarFormat::subscribeUrls((string)($cfg['calendar_id'] ?? ''));
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
