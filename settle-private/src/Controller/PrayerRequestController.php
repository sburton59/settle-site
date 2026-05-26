<?php
declare(strict_types=1);

namespace Settle\Controller;

use Settle\Auth;
use Settle\AuditLog;
use Settle\Csrf;
use Settle\Model\PrayerRequest;

/**
 * Prayer Requests — public intake form + admin inbox.
 *
 * Public-side anti-spam: invisible honeypot field ('website') plus a
 * minimum-time-on-page check (form must be on screen at least 3 seconds
 * before submit). Both bot-detection signals trigger a silent drop —
 * the bot or fast-typist still sees a generic success page so we don't
 * teach them what tripped the filter.
 *
 * Privacy: 'is_private' submissions still land in the same inbox, but
 * the request text is role-gated. Editors and admins can reveal;
 * authors see the row metadata only (no body, no reveal button).
 */
final class PrayerRequestController extends BaseController
{
    /** Min seconds between form render and submit. */
    private const MIN_FORM_SECONDS = 3;

    /** Honeypot field name. Real users never see or fill it. */
    private const HONEYPOT_FIELD = 'website';

    /** Session key used to stamp form render time. */
    private const FORM_STAMP_KEY = '_prayer_form_rendered_at';

    // -------------------------------------------------------------------
    // PUBLIC SIDE
    // -------------------------------------------------------------------

    /**
     * GET /prayer — show the intake form.
     */
    public function publicForm(): void
    {
        // Stamp the render time for the time-on-page check.
        $_SESSION[self::FORM_STAMP_KEY] = time();

        $this->renderPublic('public/prayer', [
            'errors'   => [],
            'data'     => ['submitter_name' => '', 'submitter_email' => '',
                           'request_text' => '', 'is_private' => 0],
            'success'  => false,
        ]);
    }

    /**
     * POST /prayer — handle a submission.
     */
    public function submit(): void
    {
        $data = [
            'submitter_name'  => trim((string)$this->input('submitter_name', '')),
            'submitter_email' => trim((string)$this->input('submitter_email', '')),
            'request_text'    => trim((string)$this->input('request_text', '')),
            'is_private'      => $this->input('is_private') ? 1 : 0,
        ];

        // --- Anti-spam: honeypot ---
        // A real browser never sees this field. If it's non-empty, a bot
        // filled it. Silently succeed without writing to the DB.
        $honeypot = (string)$this->input(self::HONEYPOT_FIELD, '');
        if ($honeypot !== '') {
            error_log('Prayer form honeypot tripped from ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
            $this->renderPublicSuccess();
            return;
        }

        // --- Anti-spam: time-on-page ---
        // The form must have been rendered at least MIN_FORM_SECONDS ago.
        // Missing stamp = form was POSTed without ever being GETted = bot.
        $stamp = $_SESSION[self::FORM_STAMP_KEY] ?? null;
        if (!is_int($stamp) || (time() - $stamp) < self::MIN_FORM_SECONDS) {
            error_log('Prayer form time check failed (stamp=' .
                var_export($stamp, true) . ') from ' .
                ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
            $this->renderPublicSuccess();
            return;
        }

        // --- Real validation ---
        $errors = $this->validatePublic($data);
        if ($errors) {
            // Re-stamp so the user can fix errors and resubmit without
            // tripping the time check immediately.
            $_SESSION[self::FORM_STAMP_KEY] = time();
            $this->renderPublic('public/prayer', [
                'errors'  => $errors,
                'data'    => $data,
                'success' => false,
            ]);
            return;
        }

        // Save.
        $id = PrayerRequest::create(array_merge($data, [
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
        ]));

        // Clear stamp so a refresh doesn't accidentally resubmit.
        unset($_SESSION[self::FORM_STAMP_KEY]);

        AuditLog::record(
            'prayer.submitted',
            'prayer_request',
            $id,
            ['is_private' => (int)$data['is_private']]
        );

        $this->renderPublicSuccess();
    }

    /**
     * Render the success page. Shared by the spam-drop path and the
     * real-submit path so they're observationally identical.
     */
    private function renderPublicSuccess(): void
    {
        $this->renderPublic('public/prayer', [
            'errors'  => [],
            'data'    => ['submitter_name' => '', 'submitter_email' => '',
                          'request_text' => '', 'is_private' => 0],
            'success' => true,
        ]);
    }

    /**
     * Public side uses the public.php layout instead of admin.php.
     */
    private function renderPublic(string $template, array $data): void
    {
        // We bypass the base render() because that defaults to the admin
        // layout. The View renderer accepts a layout override.
        \Settle\View::render($template, $data, 'public');
    }

    private function validatePublic(array $data): array
    {
        $errors = [];

        if ($data['request_text'] === '') {
            $errors['request_text'] = 'Please enter your prayer request.';
        } elseif (mb_strlen($data['request_text']) > 5000) {
            $errors['request_text'] = 'That\'s quite long — please keep it under 5,000 characters.';
        }

        if ($data['submitter_name'] !== '' && mb_strlen($data['submitter_name']) > 150) {
            $errors['submitter_name'] = 'Name must be 150 characters or fewer.';
        }

        if ($data['submitter_email'] !== '') {
            if (!filter_var($data['submitter_email'], FILTER_VALIDATE_EMAIL)) {
                $errors['submitter_email'] = 'That doesn\'t look like a valid email address.';
            } elseif (mb_strlen($data['submitter_email']) > 190) {
                $errors['submitter_email'] = 'Email must be 190 characters or fewer.';
            }
        }

        return $errors;
    }

    // -------------------------------------------------------------------
    // ADMIN SIDE
    // -------------------------------------------------------------------

    /**
     * GET /admin/prayer — inbox list, optionally filtered by status.
     */
    public function index(): void
    {
        $status = (string)$this->input('status', 'new');
        if (!in_array($status, PrayerRequest::STATUSES, true) && $status !== 'all') {
            $status = 'new';
        }

        $filterForQuery = $status === 'all' ? null : $status;
        $requests = PrayerRequest::all($filterForQuery, 200, 0);
        $counts   = PrayerRequest::countByStatus();

        $this->render('admin/prayer/index', [
            'requests'    => $requests,
            'counts'      => $counts,
            'status'      => $status,
            'canReveal'   => Auth::hasRole('editor'),
        ]);
    }

    /**
     * GET /admin/prayer/{id} — single-request detail view.
     */
    public function show(array $params): void
    {
        $id = (int)$params['id'];
        $request = PrayerRequest::find($id);
        if (!$request) {
            http_response_code(404);
            echo 'Prayer request not found.';
            return;
        }

        $canReveal = Auth::hasRole('editor');

        // If this is a private request and the viewer is allowed to reveal,
        // log the action server-side once the user submits the reveal — but
        // the reveal is a client-side toggle, so we log on the GET of the
        // detail page when the body would otherwise be visible. That matches
        // "this user saw the private text" most accurately.
        //
        // Authors land here too (the page renders), but for them the body
        // is NOT placed into the HTML at all, so there's nothing to log.
        if ((int)$request['is_private'] === 1 && $canReveal) {
            AuditLog::record(
                'prayer.reveal',
                'prayer_request',
                $id,
                ['viewer_role' => $_SESSION['user_role'] ?? 'unknown']
            );
        }

        $this->render('admin/prayer/show', [
            'r'         => $request,
            'canReveal' => $canReveal,
        ]);
    }

    /**
     * POST /admin/prayer/{id}/status — change status.
     */
    public function updateStatus(array $params): void
    {
        $id = (int)$params['id'];
        $request = PrayerRequest::find($id);
        if (!$request) {
            http_response_code(404);
            echo 'Prayer request not found.';
            return;
        }

        $new = (string)$this->input('status', '');
        if (!in_array($new, PrayerRequest::STATUSES, true)) {
            $this->flash('error', 'Invalid status.');
            $this->redirect('/admin/prayer/' . $id);
            return;
        }

        $old = (string)$request['status'];
        if ($new === $old) {
            $this->redirect('/admin/prayer/' . $id);
            return;
        }

        PrayerRequest::updateStatus($id, $new);

        AuditLog::record(
            'prayer.status_change',
            'prayer_request',
            $id,
            ['from' => $old, 'to' => $new]
        );

        $this->flash('success', match ($new) {
            'prayed'   => 'Marked as prayed.',
            'archived' => 'Archived.',
            'new'      => 'Returned to inbox.',
            default    => 'Status updated.',
        });
        $this->redirect('/admin/prayer/' . $id);
    }

    /**
     * POST /admin/prayer/{id}/delete — permanent removal (admin only).
     * The route registration enforces the admin role; this is a defense-
     * in-depth check.
     */
    public function destroy(array $params): void
    {
        if (!Auth::hasRole('admin')) {
            http_response_code(403);
            echo 'Forbidden.';
            return;
        }

        $id = (int)$params['id'];
        $existed = PrayerRequest::delete($id);

        if ($existed) {
            AuditLog::record('prayer.delete', 'prayer_request', $id, []);
            $this->flash('success', 'Prayer request deleted.');
        } else {
            $this->flash('error', 'Prayer request not found.');
        }
        $this->redirect('/admin/prayer');
    }
}
