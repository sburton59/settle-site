<?php
declare(strict_types=1);
namespace Settle\Controller;

use Settle\Features;
use Settle\Model\CalendarEvent;
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
}
