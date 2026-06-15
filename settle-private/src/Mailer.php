<?php
declare(strict_types=1);

namespace Settle;

/**
 * Minimal authenticated-SMTP mailer.
 *
 * Sends plain-text UTF-8 email by logging into a real mailbox over SMTP
 * (typically a cPanel mailbox on the site's own domain). Mail therefore
 * goes out domain-aligned (SPF/DKIM) and reaches inboxes, unlike
 * anonymous PHP mail() from a shared-hosting web process.
 *
 * Design mirrors \Settle\AuditLog: a single static entry point, and
 * failures are swallowed and error_log()'d so a mail problem can never
 * break the user-visible action that triggered the send. The mailer is
 * a best-effort side effect, not a transaction participant.
 *
 * Configuration lives in config.php under the 'mail' key (host, port,
 * encryption, username, password, from_email, from_name, enabled,
 * timeout). See config.example.php for the documented shape. Because the
 * mailbox password is a secret, it stays in config.php (gitignored, 0640)
 * — never in the database.
 *
 * Scope is deliberately narrow: short transactional notifications. Plain
 * text only — no HTML, no attachments, no multipart — which keeps the
 * SMTP conversation simple and the whole class auditable in one sitting.
 * If richer mail is ever needed, swap the implementation behind send().
 *
 * Security: all recipient/Reply-To addresses are validated with
 * FILTER_VALIDATE_EMAIL (which rejects embedded CR/LF), and any text
 * placed in a header is stripped of CR/LF, so caller-supplied data
 * cannot inject extra headers. Visitor-supplied free text belongs in the
 * body, never in a header.
 *
 *   Mailer::send('team@church.org', 'Subject', "Body text", [
 *       'reply_to' => 'visitor@example.com',   // optional, validated
 *   ]);
 *
 * Returns true only if the server accepted the message for delivery.
 */
final class Mailer
{
    /**
     * Send one plain-text email. Returns true on acceptance by the
     * server, false on any failure or if mail is not configured/enabled.
     *
     * @param array<string,mixed> $opts Supported: 'reply_to' (string)
     */
    public static function send(string $to, string $subject, string $body, array $opts = []): bool
    {
        $cfg = $GLOBALS['settle_config']['mail'] ?? null;
        if (!is_array($cfg) || empty($cfg['enabled'])) {
            return false;
        }

        $to = trim($to);
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            error_log('Mailer: invalid recipient address; send skipped.');
            return false;
        }

        try {
            return self::deliver($cfg, $to, $subject, $body, $opts);
        } catch (\Throwable $e) {
            error_log('Mailer::send failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Split a stored recipient-list setting into individual addresses.
     *
     * Accepts commas, semicolons, and newlines as separators (so a staff
     * member can paste either "a@x.org, b@y.org" or one address per line),
     * trims each, drops blanks, and de-duplicates case-insensitively.
     *
     * Does NOT validate addresses — callers pass each result to send(),
     * which validates with FILTER_VALIDATE_EMAIL and skips bad ones. This
     * keeps a single source of truth for what counts as a valid recipient.
     *
     * @return list<string>
     */
    public static function parseRecipients(string $list): array
    {
        $parts = preg_split('/[,;\r\n]+/', $list);
        if ($parts === false) {
            return [];
        }
        $out  = [];
        $seen = [];
        foreach ($parts as $part) {
            $addr = trim($part);
            if ($addr === '') {
                continue;
            }
            $key = strtolower($addr);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $out[] = $addr;
        }
        return $out;
    }

    /**
     * @param array<string,mixed> $cfg
     * @param array<string,mixed> $opts
     */
    private static function deliver(array $cfg, string $to, string $subject, string $body, array $opts): bool
    {
        $host       = (string)($cfg['host'] ?? '');
        $port       = (int)($cfg['port'] ?? 465);
        $encryption = strtolower((string)($cfg['encryption'] ?? 'ssl'));
        $username   = (string)($cfg['username'] ?? '');
        $password   = (string)($cfg['password'] ?? '');
        $fromEmail  = (string)($cfg['from_email'] ?? $username);
        $fromName   = (string)($cfg['from_name'] ?? '');
        $timeout    = (int)($cfg['timeout'] ?? 15);

        if ($host === '' || $username === '' || $fromEmail === '') {
            error_log('Mailer: incomplete mail config; send skipped.');
            return false;
        }

        // Implicit TLS (465) speaks ssl:// from the first byte. STARTTLS
        // (587) connects in the clear then upgrades mid-session.
        $transport = ($encryption === 'ssl')
            ? "ssl://{$host}:{$port}"
            : "tcp://{$host}:{$port}";

        $errno  = 0;
        $errstr = '';
        $socket = @stream_socket_client(
            $transport,
            $errno,
            $errstr,
            $timeout,
            STREAM_CLIENT_CONNECT
        );
        if ($socket === false) {
            throw new \RuntimeException("connect failed ({$errno}): {$errstr}");
        }
        stream_set_timeout($socket, $timeout);

        try {
            $ehloHost = self::ehloName($fromEmail);

            self::expect($socket, null, 220);                 // server greeting
            self::expect($socket, "EHLO {$ehloHost}", 250);

            if ($encryption === 'tls') {
                self::expect($socket, 'STARTTLS', 220);
                if (!@stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    throw new \RuntimeException('STARTTLS negotiation failed');
                }
                self::expect($socket, "EHLO {$ehloHost}", 250);
            }

            // AUTH LOGIN: 334 prompts, then base64 username, then base64 password.
            self::expect($socket, 'AUTH LOGIN', 334);
            self::expect($socket, base64_encode($username), 334);
            self::expect($socket, base64_encode($password), 235);

            self::expect($socket, 'MAIL FROM:<' . $fromEmail . '>', 250);
            self::expect($socket, 'RCPT TO:<' . $to . '>', [250, 251]);
            self::expect($socket, 'DATA', 354);

            $message = self::buildMessage($to, $subject, $body, $opts, $fromEmail, $fromName);
            if (fwrite($socket, $message . "\r\n.\r\n") === false) {
                throw new \RuntimeException('write failed sending DATA payload');
            }
            self::expect($socket, null, 250);                 // message accepted

            self::expect($socket, 'QUIT', 221);
        } finally {
            if (is_resource($socket)) {
                fclose($socket);
            }
        }

        return true;
    }

    /**
     * Optionally write a command line, then read the SMTP reply and
     * assert its status code. Multi-line replies (e.g. EHLO) are fully
     * consumed; the final line's code is checked.
     *
     * @param resource  $socket
     * @param int|int[] $expected
     */
    private static function expect($socket, ?string $command, $expected): void
    {
        if ($command !== null) {
            if (fwrite($socket, $command . "\r\n") === false) {
                throw new \RuntimeException('write failed: ' . $command);
            }
        }

        $line = '';
        do {
            $chunk = fgets($socket, 1024);
            if ($chunk === false) {
                $meta = stream_get_meta_data($socket);
                if (!empty($meta['timed_out'])) {
                    throw new \RuntimeException('SMTP read timed out');
                }
                throw new \RuntimeException('SMTP connection closed unexpectedly');
            }
            $line = $chunk;
            // A continuation line has '-' as its 4th character; the final
            // line has a space. Keep reading until the final line.
        } while (isset($line[3]) && $line[3] === '-');

        $code = (int)substr($line, 0, 3);
        $ok = is_array($expected)
            ? in_array($code, $expected, true)
            : ($code === $expected);

        if (!$ok) {
            $want = is_array($expected) ? implode('/', $expected) : (string)$expected;
            throw new \RuntimeException("SMTP expected {$want}, got: " . trim($line));
        }
    }

    /**
     * Assemble the RFC-822 message (headers + blank line + body).
     *
     * @param array<string,mixed> $opts
     */
    private static function buildMessage(
        string $to,
        string $subject,
        string $body,
        array $opts,
        string $fromEmail,
        string $fromName
    ): string {
        $crlf = "\r\n";

        $fromHeader = $fromName !== ''
            ? self::encodeHeaderWord($fromName) . ' <' . $fromEmail . '>'
            : $fromEmail;

        $headers   = [];
        $headers[] = 'Date: ' . date('r');
        $headers[] = 'From: ' . $fromHeader;
        $headers[] = 'To: <' . $to . '>';
        $headers[] = 'Subject: ' . self::encodeHeaderWord($subject);

        $replyTo = isset($opts['reply_to']) ? trim((string)$opts['reply_to']) : '';
        if ($replyTo !== '' && filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
            $headers[] = 'Reply-To: <' . $replyTo . '>';
        }

        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-Type: text/plain; charset=UTF-8';
        $headers[] = 'Content-Transfer-Encoding: 8bit';
        $headers[] = 'Message-ID: <' . bin2hex(random_bytes(16)) . '@' . self::ehloName($fromEmail) . '>';

        return implode($crlf, $headers) . $crlf . $crlf . self::normalizeBody($body);
    }

    /**
     * Normalize line endings to CRLF and dot-stuff so a line of "."
     * cannot prematurely terminate the DATA stream.
     */
    private static function normalizeBody(string $body): string
    {
        $body = str_replace(["\r\n", "\r", "\n"], "\n", $body);
        $body = str_replace("\n", "\r\n", $body);
        // SMTP transparency: a leading dot on any line gets doubled.
        $body = preg_replace('/^\./m', '..', $body);
        return (string)$body;
    }

    /**
     * Encode a header value. CR/LF are stripped (injection defense);
     * non-ASCII is wrapped as an RFC 2047 encoded-word.
     */
    private static function encodeHeaderWord(string $s): string
    {
        $s = self::sanitizeHeader($s);
        if ($s !== '' && preg_match('/[^\x20-\x7E]/', $s)) {
            return '=?UTF-8?B?' . base64_encode($s) . '?=';
        }
        return $s;
    }

    private static function sanitizeHeader(string $s): string
    {
        return trim(str_replace(["\r", "\n"], ' ', $s));
    }

    /**
     * Domain to use for EHLO and the Message-ID right-hand side,
     * derived from the From address.
     */
    private static function ehloName(string $email): string
    {
        $at     = strrpos($email, '@');
        $domain = $at !== false ? substr($email, $at + 1) : '';
        return $domain !== '' ? $domain : 'localhost';
    }
}
