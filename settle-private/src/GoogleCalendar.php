<?php
declare(strict_types=1);

namespace Settle;

/**
 * Google Calendar sync service (roadmap #2).
 *
 * Pulls events from a PUBLIC Google Calendar via an API key (read-only,
 * no OAuth, no service account — see PROJECT_HANDOFF.md §3.3) into the
 * local `calendar_events_cache` table. The public site renders from that
 * cache, never directly from Google, so a Google outage can slow or skip
 * a sync but can NEVER blank the calendar page.
 *
 * Design mirrors \Settle\Mailer and \Settle\AuditLog: a static entry
 * point, and every network/parse failure is swallowed and error_log()'d.
 * sync() returns the number of events written, or -1 on any failure.
 *
 * Source of truth is Google. We only ever cache the events inside a
 * rolling window [now - window_past_days, now + window_future_days].
 * After a CLEAN full fetch, rows for this calendar that were not seen in
 * the fetch are pruned, so the cache is exactly the latest window
 * snapshot. A failed/partial fetch prunes NOTHING — the previous cache
 * is left intact.
 *
 * Configuration lives in config.php under the 'google_calendar' key
 * (api_key, calendar_id, timezone, featured_keyword, window_*_days,
 * cache_ttl, lazy_sync, http_timeout, enabled). The api_key is a secret
 * and stays in config.php (gitignored, 0640) — never in the database,
 * never in a committed file, and only ever sent server-to-Google.
 *
 * Time handling:
 *   - Timed events are converted from their source offset into the
 *     church's local timezone and stored as naive DATETIMEs, so the
 *     render layer needs no timezone math.
 *   - All-day events carry floating dates (no timezone). Google's
 *     end.date is EXCLUSIVE, so we subtract one day when storing ends_at,
 *     which makes a one-day all-day event store starts_at == ends_at and
 *     avoids the classic off-by-one.
 *
 * Featured detection: a case-insensitive match of the configured keyword
 * (default "[featured]") anywhere in the event description sets
 * is_featured. The original description is stored verbatim; the keyword
 * is stripped only at render time.
 */
final class GoogleCalendar
{
    private const API_BASE = 'https://www.googleapis.com/calendar/v3/calendars/';

    /**
     * Fetch the configured public calendar and refresh the local cache.
     *
     * @return int Number of events written, or -1 on failure / not configured.
     */
    public static function sync(): int
    {
        $cfg = $GLOBALS['settle_config']['google_calendar'] ?? null;
        if (!is_array($cfg) || empty($cfg['enabled'])) {
            return -1;
        }

        $calId  = trim((string)($cfg['calendar_id'] ?? ''));
        $apiKey = trim((string)($cfg['api_key'] ?? ''));
        if ($calId === '' || $apiKey === '' || str_starts_with($apiKey, 'REPLACE_WITH')) {
            error_log('GoogleCalendar: calendar_id / api_key not configured; sync skipped.');
            return -1;
        }

        $tz         = (string)($cfg['timezone'] ?? 'America/Chicago');
        $keyword    = (string)($cfg['featured_keyword'] ?? '[featured]');
        $hiddenKw   = (string)($cfg['hidden_keyword'] ?? '[hide]');
        $pastDays   = max(0, (int)($cfg['window_past_days'] ?? 1));
        $futureDays = max(1, (int)($cfg['window_future_days'] ?? 365));
        $timeout    = max(1, (int)($cfg['http_timeout'] ?? 10));

        try {
            $utc     = new \DateTimeZone('UTC');
            $now     = new \DateTime('now', $utc);
            $timeMin = (clone $now)->modify("-{$pastDays} days")->format('Y-m-d\TH:i:s\Z');
            $timeMax = (clone $now)->modify("+{$futureDays} days")->format('Y-m-d\TH:i:s\Z');

            $items = self::fetchAllPages($calId, $apiKey, $timeMin, $timeMax, $timeout);
            if ($items === null) {
                // Fetch failed — leave the existing cache untouched.
                return -1;
            }

            $rows = self::normalizeItems($items, $calId, $tz, $keyword, $hiddenKw);
            self::persist($rows, $calId);
            return count($rows);
        } catch (\Throwable $e) {
            error_log('GoogleCalendar::sync failed: ' . $e->getMessage());
            return -1;
        }
    }

    /**
     * Optional lazy refresh on page view. No-op unless config has
     * 'lazy_sync' => true. With cron running, lazy_sync stays false and
     * this returns immediately. Provided as a fallback for hosts without
     * cron. Failures are swallowed.
     */
    public static function maybeLazySync(): void
    {
        $cfg = $GLOBALS['settle_config']['google_calendar'] ?? null;
        if (!is_array($cfg) || empty($cfg['enabled']) || empty($cfg['lazy_sync'])) {
            return;
        }
        $ttl = max(60, (int)($cfg['cache_ttl'] ?? 900));
        try {
            $age = Database::query(
                'SELECT TIMESTAMPDIFF(SECOND, MAX(last_synced_at), NOW())
                 FROM calendar_events_cache'
            )->fetchColumn();
            // No rows yet ($age null/false) or stale → sync.
            if ($age === null || $age === false || (int)$age >= $ttl) {
                self::sync();
            }
        } catch (\Throwable $e) {
            error_log('GoogleCalendar::maybeLazySync failed: ' . $e->getMessage());
        }
    }

    /**
     * Normalize raw Google `events.list` items into cache-row shape.
     *
     * Pure function: no database, no network. This is where the tricky
     * cases live (all-day off-by-one, timezone conversion, cancelled
     * skip, featured detection), so it is unit-tested in isolation.
     *
     * @param array<int, array<string, mixed>> $items Google `items` array
     * @return array<int, array<string, mixed>> Cache rows
     */
    public static function normalizeItems(array $items, string $calId, string $tz, string $keyword, string $hiddenKeyword = ''): array
    {
        try {
            $churchTz = new \DateTimeZone($tz);
        } catch (\Throwable $e) {
            $churchTz = new \DateTimeZone('America/Chicago');
        }
        $kw  = mb_strtolower(trim($keyword));
        $hkw = mb_strtolower(trim($hiddenKeyword));
        $out = [];

        foreach ($items as $it) {
            if (!is_array($it)) {
                continue;
            }
            // Cancelled instances are tombstones — skip entirely.
            if (($it['status'] ?? '') === 'cancelled') {
                continue;
            }
            $gid = trim((string)($it['id'] ?? ''));
            if ($gid === '') {
                continue;
            }

            $title = trim((string)($it['summary'] ?? ''));
            if ($title === '') {
                $title = '(untitled event)';
            }
            $desc = isset($it['description']) ? (string)$it['description'] : null;
            $loc  = isset($it['location']) ? trim((string)$it['location']) : null;
            $link = isset($it['htmlLink']) ? (string)$it['htmlLink'] : null;

            $start = is_array($it['start'] ?? null) ? $it['start'] : [];
            $end   = is_array($it['end'] ?? null) ? $it['end'] : [];
            $isAllDay = isset($start['date']);

            if ($isAllDay) {
                $startsAt = (string)$start['date'] . ' 00:00:00';
                $endsAt   = null;
                if (isset($end['date'])) {
                    $endDt = \DateTime::createFromFormat('Y-m-d', (string)$end['date']);
                    if ($endDt !== false) {
                        // Google's all-day end.date is EXCLUSIVE.
                        $endDt->modify('-1 day');
                        $endsAt = $endDt->format('Y-m-d') . ' 00:00:00';
                    }
                }
                if ($endsAt === null) {
                    $endsAt = $startsAt;
                }
            } else {
                $sRaw = (string)($start['dateTime'] ?? '');
                if ($sRaw === '') {
                    continue; // neither date nor dateTime — unusable
                }
                $sDt = self::toChurchTime($sRaw, $churchTz);
                if ($sDt === null) {
                    continue;
                }
                $startsAt = $sDt->format('Y-m-d H:i:s');

                $endsAt = null;
                $eRaw   = (string)($end['dateTime'] ?? '');
                if ($eRaw !== '') {
                    $eDt = self::toChurchTime($eRaw, $churchTz);
                    if ($eDt !== null) {
                        $endsAt = $eDt->format('Y-m-d H:i:s');
                    }
                }
            }

            $featured = false;
            $hidden   = false;
            $tags     = [];
            if ($desc !== null) {
                $descLower = mb_strtolower($desc);
                if ($kw !== '' && mb_strpos($descLower, $kw) !== false) {
                    $featured = true;
                    $tags[]   = $keyword;
                }
                if ($hkw !== '' && mb_strpos($descLower, $hkw) !== false) {
                    $hidden = true;
                    $tags[] = $hiddenKeyword;
                }
            }

            $out[] = [
                'google_event_id'    => mb_substr($gid, 0, 255),
                'google_calendar_id' => mb_substr($calId, 0, 255),
                'title'              => mb_substr($title, 0, 255),
                'description'        => $desc,
                'location'           => ($loc !== null && $loc !== '') ? mb_substr($loc, 0, 255) : null,
                'starts_at'          => $startsAt,
                'ends_at'            => $endsAt,
                'is_all_day'         => $isAllDay ? 1 : 0,
                'is_featured'        => $featured ? 1 : 0,
                'is_hidden'          => $hidden ? 1 : 0,
                'raw_tags'           => $tags === [] ? null : mb_substr(implode(' ', $tags), 0, 500),
                'html_link'          => ($link !== null && $link !== '') ? mb_substr($link, 0, 500) : null,
            ];
        }

        return $out;
    }

    /**
     * Parse an RFC-3339 dateTime (which carries its own UTC offset) and
     * convert it into the church's local wall-clock time.
     */
    private static function toChurchTime(string $rfc3339, \DateTimeZone $churchTz): ?\DateTime
    {
        try {
            $dt = new \DateTime($rfc3339); // honors the embedded offset
            $dt->setTimezone($churchTz);
            return $dt;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Fetch every page of events.list for the window. Returns the merged
     * items array, or null if ANY page fails (so the caller skips the
     * prune and keeps the existing cache).
     *
     * @return array<int, array<string, mixed>>|null
     */
    private static function fetchAllPages(
        string $calId,
        string $apiKey,
        string $timeMin,
        string $timeMax,
        int $timeout
    ): ?array {
        $items     = [];
        $pageToken = null;
        $base      = self::API_BASE . rawurlencode($calId) . '/events';
        $guard     = 0;

        do {
            if (++$guard > 50) { // safety: never loop forever on a bad token
                error_log('GoogleCalendar: pagination guard tripped.');
                return null;
            }

            $params = [
                'singleEvents' => 'true',
                'orderBy'      => 'startTime',
                'timeMin'      => $timeMin,
                'timeMax'      => $timeMax,
                'maxResults'   => '2500',
                'fields'       => 'nextPageToken,items(id,status,summary,description,location,start,end,htmlLink)',
                'key'          => $apiKey,
            ];
            if ($pageToken !== null) {
                $params['pageToken'] = $pageToken;
            }

            $body = self::httpGet($base . '?' . http_build_query($params), $timeout);
            if ($body === null) {
                return null;
            }

            $data = json_decode($body, true);
            if (!is_array($data) || isset($data['error'])) {
                error_log('GoogleCalendar: API returned an error or non-JSON body.');
                return null;
            }

            foreach (($data['items'] ?? []) as $it) {
                $items[] = $it;
            }
            $pageToken = isset($data['nextPageToken']) ? (string)$data['nextPageToken'] : null;
        } while ($pageToken !== null && $pageToken !== '');

        return $items;
    }

    /**
     * HTTPS GET returning the response body, or null on any non-2xx /
     * transport failure. Uses cURL when present, else a stream context.
     * TLS verification is always on. The URL (which carries the API key)
     * is only ever sent server-to-Google and is never logged here.
     */
    private static function httpGet(string $url, int $timeout): ?string
    {
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => $timeout,
                CURLOPT_CONNECTTIMEOUT => $timeout,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_HTTPHEADER     => ['Accept: application/json'],
            ]);
            $body = curl_exec($ch);
            $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $err  = curl_error($ch);
            curl_close($ch);

            if ($body === false || $code < 200 || $code >= 300) {
                // Log the status, NOT the URL (which contains the key).
                error_log("GoogleCalendar: HTTP {$code}" . ($err !== '' ? " ({$err})" : ''));
                return null;
            }
            return (string)$body;
        }

        // Fallback: stream wrapper.
        $ctx = stream_context_create([
            'http' => [
                'method'        => 'GET',
                'timeout'       => $timeout,
                'header'        => "Accept: application/json\r\n",
                'ignore_errors' => true,
            ],
            'ssl' => [
                'verify_peer'      => true,
                'verify_peer_name' => true,
            ],
        ]);
        $body = @file_get_contents($url, false, $ctx);
        if ($body === false) {
            error_log('GoogleCalendar: HTTP fetch failed (stream).');
            return null;
        }
        // Inspect status line from $http_response_header.
        $code = 0;
        if (isset($http_response_header[0]) && preg_match('#\s(\d{3})\s#', $http_response_header[0], $m)) {
            $code = (int)$m[1];
        }
        if ($code !== 0 && ($code < 200 || $code >= 300)) {
            error_log("GoogleCalendar: HTTP {$code} (stream).");
            return null;
        }
        return (string)$body;
    }

    /**
     * Upsert the normalized rows and prune anything for this calendar that
     * was not in the fetch. Runs in a transaction; a partial failure rolls
     * back and leaves the previous cache intact.
     *
     * @param array<int, array<string, mixed>> $rows
     */
    private static function persist(array $rows, string $calId): void
    {
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $up = $pdo->prepare(
                'INSERT INTO calendar_events_cache
                    (google_event_id, google_calendar_id, title, description, location,
                     starts_at, ends_at, is_all_day, is_featured, is_hidden, raw_tags, html_link, last_synced_at)
                 VALUES
                    (:gid, :cal, :title, :descr, :loc, :starts, :ends, :allday, :feat, :hidden, :tags, :link, NOW())
                 ON DUPLICATE KEY UPDATE
                    title          = VALUES(title),
                    description    = VALUES(description),
                    location       = VALUES(location),
                    starts_at      = VALUES(starts_at),
                    ends_at        = VALUES(ends_at),
                    is_all_day     = VALUES(is_all_day),
                    is_featured    = VALUES(is_featured),
                    is_hidden      = VALUES(is_hidden),
                    raw_tags       = VALUES(raw_tags),
                    html_link      = VALUES(html_link),
                    last_synced_at = NOW()'
            );

            $seen = [];
            foreach ($rows as $r) {
                $up->execute([
                    ':gid'    => $r['google_event_id'],
                    ':cal'    => $r['google_calendar_id'],
                    ':title'  => $r['title'],
                    ':descr'  => $r['description'],
                    ':loc'    => $r['location'],
                    ':starts' => $r['starts_at'],
                    ':ends'   => $r['ends_at'],
                    ':allday' => $r['is_all_day'],
                    ':feat'   => $r['is_featured'],
                    ':hidden' => $r['is_hidden'],
                    ':tags'   => $r['raw_tags'],
                    ':link'   => $r['html_link'],
                ]);
                $seen[] = $r['google_event_id'];
            }

            // Prune events no longer present in (or fallen out of) the window.
            if ($seen === []) {
                $pdo->prepare(
                    'DELETE FROM calendar_events_cache WHERE google_calendar_id = :cal'
                )->execute([':cal' => $calId]);
            } else {
                $placeholders = implode(',', array_fill(0, count($seen), '?'));
                $del = $pdo->prepare(
                    "DELETE FROM calendar_events_cache
                     WHERE google_calendar_id = ?
                       AND google_event_id NOT IN ({$placeholders})"
                );
                $del->execute(array_merge([$calId], $seen));
            }

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
