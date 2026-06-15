<?php
declare(strict_types=1);

namespace Settle\Controller;

use Settle\Auth;
use Settle\AuditLog;
use Settle\Mailer;
use Settle\Settings;
use Settle\Model\ContactMessage;

/**
 * Contact Messages — public intake form + admin inbox.
 *
 * Public-side anti-spam mirrors PrayerRequestController exactly:
 * invisible honeypot field ('website') plus a minimum-time-on-page
 * check (form must be on screen at least 3 seconds before submit).
 * Both bot-detection signals trigger a silent drop — the bot or
 * fast-typist still sees a generic success page so we don't teach
 * them what tripped the filter.
 *
 * The admin inbox is auth-only (no editor gate) since contact
 * messages have no privacy flag. Hard delete is admin-only.
 *
 * Email forwarding (roadmap #6): new messages are forwarded to the
 * address in settings key `contact_notify_to` via \Settle\Mailer.
 * Sends are best-effort — failures are swallowed inside the mailer so
 * the message is still saved and the visitor still sees success.
 */
final class ContactMessageController extends BaseController
{
    /** Min seconds between form render and submit. */
    private const MIN_FORM_SECONDS = 3;

    /** Honeypot field name. Real users never see or fill it. */
    private const HONEYPOT_FIELD = 'website';

    /**
     * Session key used to stamp form render time. Distinct from the
     * prayer form's stamp so a visitor can use both forms in one
     * session without interference.
     */
    private const FORM_STAMP_KEY = '_contact_form_rendered_at';

    // -------------------------------------------------------------------
    //  PUBLIC SIDE
    // -------------------------------------------------------------------

    /**
     * GET /contact — show the intake form.
     */
    public function publicForm(): void
    {
        $_SESSION[self::FORM_STAMP_KEY] = time();

        $this->renderPublic('public/contact', [
            'errors'  => [],
            'data'    => $this->blankFormData(),
            'success' => false,
        ]);
    }

    /**
     * POST /contact — handle a submission.
     */
    public function submit(): void
    {
        $data = [
            'sender_name'  => trim((string)$this->input('sender_name', '')),
            'sender_email' => trim((string)$this->input('sender_email', '')),
            'sender_phone' => trim((string)$this->input('sender_phone', '')),
            'reply_method' => (string)$this->input('reply_method', 'email'),
            'message_text' => trim((string)$this->input('message_text', '')),
        ];

        // --- Anti-spam: honeypot ---
        $honeypot = (string)$this->input(self::HONEYPOT_FIELD, '');
        if ($honeypot !== '') {
            error_log('Contact form honeypot tripped from ' .
                ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
            $this->renderPublicSuccess();
            return;
        }

        // --- Anti-spam: time-on-page ---
        $stamp = $_SESSION[self::FORM_STAMP_KEY] ?? null;
        if (!is_int($stamp) || (time() - $stamp) < self::MIN_FORM_SECONDS) {
            error_log('Contact form time check failed (stamp=' .
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
            $this->renderPublic('public/contact', [
                'errors'  => $errors,
                'data'    => $data,
                'success' => false,
            ]);
            return;
        }

        // Save.
        $id = ContactMessage::create(array_merge($data, [
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
        ]));

        // Clear stamp so a refresh doesn't accidentally resubmit.
        unset($_SESSION[self::FORM_STAMP_KEY]);

        AuditLog::record(
            'contact.submitted',
            'contact_message',
            $id,
            ['reply_method' => $data['reply_method']]
        );

        // Roadmap #6: forward to the configured staff inbox. Mailer
        // failures are swallowed inside notifyStaff(), so a mail problem
        // never changes what the visitor sees.
        $this->notifyStaff($id, $data);

        $this->renderPublicSuccess();
    }

    /**
     * Forward a new contact message to the configured staff inbox.
     *
     * The destination lives in the `settings` table (key
     * `contact_notify_to`) so staff can change it later without a
     * deploy. If it's unset, we simply don't send — the message is
     * already saved to the admin inbox regardless.
     *
     * When the visitor asked to be reached by email and gave a valid
     * address, that address becomes the Reply-To, so staff can reply
     * straight to the sender. The address is validated (which rejects
     * header-injection attempts); all other visitor-supplied text stays
     * in the body, never in a header.
     */
    private function notifyStaff(int $id, array $data): void
    {
        $to = Settings::get('contact_notify_to');
        if ($to === null || $to === '') {
            return;
        }

        $church   = Settings::get('church_name', 'the church');
        $baseUrl  = rtrim((string)($GLOBALS['settle_config']['app']['base_url'] ?? ''), '/');
        $adminUrl = $baseUrl . '/admin/contact/' . $id;

        $subject = 'New contact message · ' . $church;

        $lines   = [];
        $lines[] = 'A new message was submitted through the website contact form.';
        $lines[] = '';
        $lines[] = 'Name:            ' . $data['sender_name'];
        $lines[] = 'Email:           ' . ($data['sender_email'] !== '' ? $data['sender_email'] : '(not provided)');
        $lines[] = 'Phone:           ' . ($data['sender_phone'] !== '' ? $data['sender_phone'] : '(not provided)');
        $lines[] = 'Preferred reply: ' . $data['reply_method'];
        $lines[] = '';
        $lines[] = 'Message:';
        $lines[] = $data['message_text'];
        $lines[] = '';
        $lines[] = '-------------------------------------------';
        $lines[] = 'View in the admin panel: ' . $adminUrl;
        $body    = implode("\n", $lines);

        $opts = [];
        if (in_array($data['reply_method'], ['email', 'either'], true)
            && filter_var($data['sender_email'], FILTER_VALIDATE_EMAIL)) {
            $opts['reply_to'] = $data['sender_email'];
        }

        // contact_notify_to may hold several addresses (commas/newlines);
        // send one message per address. Mailer::send() validates each.
        $recipients = Mailer::parseRecipients($to);
        $sent = 0;
        foreach ($recipients as $addr) {
            if (Mailer::send($addr, $subject, $body, $opts)) {
                $sent++;
            }
        }

        AuditLog::record(
            'contact.notified',
            'contact_message',
            $id,
            ['recipients' => count($recipients), 'notified' => $sent]
        );
    }

    /**
     * Render the success page. Shared by the spam-drop path and the
     * real-submit path so they're observationally identical.
     */
    private function renderPublicSuccess(): void
    {
        $this->renderPublic('public/contact', [
            'errors'  => [],
            'data'    => $this->blankFormData(),
            'success' => true,
        ]);
    }

    /**
     * Public side renders through PublicView, which wraps View::render()
     * with the public layout AND injects the $settings map and $menu_tree
     * that the layout requires. Calling View::render() directly here was
     * the cause of the blank-page bug: the public layout's nav renderer
     * received a null $menu_tree and threw a TypeError (fatal under
     * display_errors=off). A default page title is supplied; a caller-
     * provided 'page_title' would win via the union operator.
     */
    private function renderPublic(string $template, array $data): void
    {
        $data += ['page_title' => 'Contact Us'];
        \Settle\PublicView::render($template, $data);
    }

    private function blankFormData(): array
    {
        return [
            'sender_name'  => '',
            'sender_email' => '',
            'sender_phone' => '',
            'reply_method' => 'email',
            'message_text' => '',
        ];
    }

    /**
     * Validate the submitted form. Conditional required-field rules
     * depend on reply_method:
     *   - 'email'  → sender_email required and must validate
     *   - 'phone'  → sender_phone required (≥10 digits)
     *   - 'either' → at least one of email/phone must be supplied
     */
    private function validatePublic(array $data): array
    {
        $errors = [];

        // Name.
        if ($data['sender_name'] === '') {
            $errors['sender_name'] = 'Please tell us your name.';
        } elseif (mb_strlen($data['sender_name']) > 150) {
            $errors['sender_name'] = 'Name must be 150 characters or fewer.';
        }

        // Reply method.
        if (!in_array($data['reply_method'], ContactMessage::REPLY_METHODS, true)) {
            $errors['reply_method'] = 'Please choose how we should reach you.';
            // Normalize for downstream checks so they don't compound.
            $data['reply_method'] = 'email';
        }

        // Email.
        $emailGiven = $data['sender_email'] !== '';
        $emailValid = $emailGiven && filter_var($data['sender_email'], FILTER_VALIDATE_EMAIL);
        if ($emailGiven && !$emailValid) {
            $errors['sender_email'] = 'That doesn\'t look like a valid email address.';
        } elseif ($emailGiven && mb_strlen($data['sender_email']) > 190) {
            $errors['sender_email'] = 'Email must be 190 characters or fewer.';
        }

        // Phone (digits only count toward the minimum).
        $phoneDigits = preg_replace('/\D+/', '', $data['sender_phone']) ?? '';
        $phoneGiven  = $data['sender_phone'] !== '';
        $phoneValid  = strlen($phoneDigits) >= 10;
        if ($phoneGiven && !$phoneValid) {
            $errors['sender_phone'] = 'Please enter a phone number with at least 10 digits.';
        } elseif ($phoneGiven && mb_strlen($data['sender_phone']) > 50) {
            $errors['sender_phone'] = 'Phone number must be 50 characters or fewer.';
        }

        // Conditional required-field rules driven by reply_method.
        switch ($data['reply_method']) {
            case 'email':
                if (!$emailGiven) {
                    $errors['sender_email'] = 'Email is required when you ask to be contacted by email.';
                }
                break;

            case 'phone':
                if (!$phoneGiven) {
                    $errors['sender_phone'] = 'Phone is required when you ask to be contacted by phone.';
                }
                break;

            case 'either':
                if (!$emailGiven && !$phoneGiven) {
                    // Surface the same prompt under both fields so it's hard to miss.
                    $errors['sender_email'] = 'Please provide an email address or phone number so we can reach you.';
                    $errors['sender_phone'] = 'Please provide an email address or phone number so we can reach you.';
                }
                break;
        }

        // Message body.
        if ($data['message_text'] === '') {
            $errors['message_text'] = 'Please enter a message.';
        } elseif (mb_strlen($data['message_text']) > 5000) {
            $errors['message_text'] = 'That\'s quite long — please keep it under 5,000 characters.';
        }

        return $errors;
    }

    // -------------------------------------------------------------------
    //  ADMIN SIDE
    // -------------------------------------------------------------------

    /**
     * GET /admin/contact — inbox list, optionally filtered by read state.
     * Filter values: 'unread' (default), 'read', 'all'.
     */
    public function index(): void
    {
        $filter = (string)$this->input('filter', 'unread');
        if (!in_array($filter, ['unread', 'read', 'all'], true)) {
            $filter = 'unread';
        }

        $unreadOnly = match ($filter) {
            'unread' => true,
            'read'   => false,
            default  => null,
        };

        $messages = ContactMessage::all($unreadOnly, 200, 0);
        $unread   = ContactMessage::countUnread();

        $this->render('admin/contact/index', [
            'messages' => $messages,
            'unread'   => $unread,
            'filter'   => $filter,
        ]);
    }

    /**
     * GET /admin/contact/{id} — detail view. Auto-marks read.
     */
    public function show(array $params): void
    {
        $id = (int)$params['id'];
        $message = ContactMessage::find($id);

        if (!$message) {
            http_response_code(404);
            echo 'Contact message not found.';
            return;
        }

        // Auto-mark read on first view, matching email-client behavior.
        // markRead() returns false if the row was already read, which is
        // how we know whether to write the audit entry.
        if ((int)$message['is_read'] === 0 && ContactMessage::markRead($id)) {
            $message['is_read'] = 1;
            AuditLog::record(
                'contact.read',
                'contact_message',
                $id,
                ['trigger' => 'auto_on_view']
            );
        }

        $this->render('admin/contact/show', [
            'm' => $message,
        ]);
    }

    /**
     * POST /admin/contact/{id}/read — explicit mark-as-read button.
     * Useful when staff have already viewed a message, marked it
     * unread, then changed their mind.
     */
    public function markRead(array $params): void
    {
        $id = (int)$params['id'];
        $message = ContactMessage::find($id);

        if (!$message) {
            http_response_code(404);
            echo 'Contact message not found.';
            return;
        }

        if ((int)$message['is_read'] === 0 && ContactMessage::markRead($id)) {
            AuditLog::record(
                'contact.read',
                'contact_message',
                $id,
                ['trigger' => 'manual']
            );
            $this->flash('success', 'Marked as read.');
        }

        $this->redirect('/admin/contact/' . $id);
    }

    /**
     * POST /admin/contact/{id}/unread — flip a read message back to unread.
     */
    public function markUnread(array $params): void
    {
        $id = (int)$params['id'];
        $message = ContactMessage::find($id);

        if (!$message) {
            http_response_code(404);
            echo 'Contact message not found.';
            return;
        }

        if ((int)$message['is_read'] === 1 && ContactMessage::markUnread($id)) {
            AuditLog::record(
                'contact.unread',
                'contact_message',
                $id,
                []
            );
            $this->flash('success', 'Marked as unread.');
        }

        $this->redirect('/admin/contact/' . $id);
    }

    /**
     * POST /admin/contact/{id}/delete — permanent removal (admin only).
     * The route registration enforces the admin role; this is a
     * defense-in-depth check.
     */
    public function destroy(array $params): void
    {
        if (!Auth::hasRole('admin')) {
            http_response_code(403);
            echo 'Forbidden.';
            return;
        }

        $id = (int)$params['id'];
        $existed = ContactMessage::delete($id);

        if ($existed) {
            AuditLog::record('contact.delete', 'contact_message', $id, []);
            $this->flash('success', 'Contact message deleted.');
        } else {
            $this->flash('error', 'Contact message not found.');
        }

        $this->redirect('/admin/contact');
    }
}
