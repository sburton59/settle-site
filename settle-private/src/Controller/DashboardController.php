<?php
declare(strict_types=1);
namespace Settle\Controller;

use Settle\Auth;
use Settle\AuditLog;
use Settle\Features;
use Settle\RateLimiter;
use Settle\Model\CalendarEvent;
use Settle\Model\ContactMessage;
use Settle\Model\Media;
use Settle\Model\Page;
use Settle\Model\Post;
use Settle\Model\PrayerRequest;
use Settle\Model\Staff;

/**
 * Admin dashboard / "Welcome back" landing (roadmap #8b).
 *
 * Role-aware and feature-gated. Authors see their own post summary + the
 * media count; editors additionally see triage counts (prayer, contact),
 * upcoming events, and page/staff counts plus recent prayer/contact items;
 * admins additionally see a recent audit-activity feed and the login
 * rate-limiter health probe. Every widget is guarded by its Features flag,
 * so a disabled module simply doesn't appear. All queries reuse existing
 * model methods (the only new one is Post::dashboardSummary, v8b).
 */
final class DashboardController extends BaseController
{
    public function index(): void
    {
        $user     = Auth::user() ?? ['id' => 0, 'role' => '', 'display' => ''];
        $viewerId = (int) $user['id'];
        $isEditor = Auth::hasRole('editor');
        $isAdmin  = Auth::hasRole('admin');

        $data = [
            'is_editor' => $isEditor,
            'is_admin'  => $isAdmin,
        ];

        // ---- Author and up: own/all posts + media ----
        if (Features::enabled('blog')) {
            $data['posts'] = Post::dashboardSummary($viewerId, $isEditor, 5);
        }
        if (Features::enabled('media')) {
            $data['media_total'] = (int) (Media::paginate(1, 1)['total'] ?? 0);
        }

        // ---- Editor and up: triage + content counts ----
        if ($isEditor) {
            if (Features::enabled('prayer')) {
                $data['prayer_counts'] = PrayerRequest::countByStatus();
                $data['prayer_recent'] = PrayerRequest::all('new', 5);
            }
            if (Features::enabled('contact')) {
                $data['contact_unread'] = ContactMessage::countUnread();
                $data['contact_recent'] = ContactMessage::all(true, 5);
            }
            if (Features::enabled('calendar')) {
                $data['events_upcoming'] = CalendarEvent::countUpcoming();
            }
            if (Features::enabled('pages')) {
                $data['pages_total'] = count(Page::all());
            }
            if (Features::enabled('staff')) {
                $data['staff_total'] = count(Staff::all());
            }
        }

        // ---- Admin only: recent audit activity + limiter health ----
        // (null = "not checked" for non-admins; the hot path fails open and
        // silently, so a broken limiter needs surfacing to someone who can
        // fix it. See the v2.7 addendum.)
        if ($isAdmin) {
            $data['audit_recent']    = AuditLog::query([], 8, 0);
            $data['rate_limiter_ok'] = RateLimiter::healthy();
        } else {
            $data['rate_limiter_ok'] = null;
        }

        $this->render('admin/dashboard', $data);
    }
}
