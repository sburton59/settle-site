<?php
declare(strict_types=1);
namespace Settle\Controller;

use Settle\Model\CalendarOverride;
use Settle\Model\Media;
use Settle\AuditLog;

/**
 * Admin override editor for cached Google Calendar events (roadmap #4b).
 *
 * Hide and feature are authored as [hide] / [featured] tags in the Google
 * Calendar event description (synced into cache.is_hidden / is_featured).
 * This editor authors ONLY the two website-only fields that cannot live in
 * a calendar tag: an override image and a public note/blurb.
 *
 * Routes (editor+, gated by Features::enabled('calendar')):
 *   GET  /admin/calendar                       index()
 *   GET  /admin/calendar/{id}/edit             edit()
 *   POST /admin/calendar/{id}/override         save()
 *   POST /admin/calendar/{id}/override/delete  clear()
 *
 * {id} is the calendar_events_cache row id; google_event_id (the override
 * key) is resolved server-side. CSRF is router-enforced; writes are
 * audited (calendar.override.set / .clear) and stamp updated_by.
 */
final class CalendarOverrideController extends BaseController
{
    public function index(): void
    {
        $this->render('admin/calendar/index', [
            'events' => CalendarOverride::allEventsForAdmin(),
        ]);
    }

    public function edit(array $params): void
    {
        $event = CalendarOverride::findForAdmin((int)$params['id']);
        if (!$event) {
            http_response_code(404);
            echo 'Event not found.';
            return;
        }
        $this->render('admin/calendar/edit', [
            'event'  => $event,
            'errors' => [],
        ]);
    }

    public function save(array $params): void
    {
        $cacheId = (int)$params['id'];
        $event   = CalendarOverride::findForAdmin($cacheId);
        if (!$event) {
            http_response_code(404);
            echo 'Event not found.';
            return;
        }

        $imageId = (int)$this->input('override_image_id', 0);
        $imageId = $imageId > 0 ? $imageId : null;
        $notes   = trim((string)$this->input('notes', ''));
        $notes   = $notes === '' ? null : $notes;

        $errors = [];

        if ($imageId !== null) {
            $media = Media::find($imageId);
            if (!$media) {
                $errors['override_image_id'] = 'That image no longer exists in the Media Library.';
            } elseif (strpos((string)$media['mime_type'], 'image/') !== 0) {
                $errors['override_image_id'] = 'The override image must be an image file.';
            }
        }

        if ($notes !== null && mb_strlen($notes) > 500) {
            $errors['notes'] = 'The note must be 500 characters or fewer.';
        }

        if ($errors) {
            // Re-render with the submitted values so nothing is lost.
            $event['override_image_id'] = $imageId;
            $event['override_notes']    = $notes;
            $this->render('admin/calendar/edit', [
                'event'  => $event,
                'errors' => $errors,
            ]);
            return;
        }

        $userId = (int)($_SESSION['user_id'] ?? 0);
        CalendarOverride::upsert((string)$event['google_event_id'], $imageId, $notes, $userId);

        AuditLog::record('calendar.override.set', 'calendar_event', $cacheId, [
            'google_event_id' => (string)$event['google_event_id'],
            'image'           => $imageId !== null ? 'set' : 'cleared',
            'notes'           => $notes !== null ? 'set' : 'cleared',
        ]);

        $this->flash('success', 'Override saved.');
        $this->redirect('/admin/calendar');
    }

    public function clear(array $params): void
    {
        $cacheId = (int)$params['id'];
        $event   = CalendarOverride::findForAdmin($cacheId);
        if (!$event) {
            http_response_code(404);
            echo 'Event not found.';
            return;
        }

        CalendarOverride::clear((string)$event['google_event_id']);

        AuditLog::record('calendar.override.clear', 'calendar_event', $cacheId, [
            'google_event_id' => (string)$event['google_event_id'],
        ]);

        $this->flash('success', 'Override cleared.');
        $this->redirect('/admin/calendar');
    }
}
