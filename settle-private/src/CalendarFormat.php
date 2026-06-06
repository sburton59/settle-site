<?php
declare(strict_types=1);
namespace Settle;

/**
 * Presentation helpers for cached Google Calendar events (roadmap #8a).
 *
 * Centralises the formatting that was previously duplicated as local
 * closures in templates/public/calendar.php and templates/public/home.php,
 * so the month grid, list view, day view, and homepage cards all render
 * times and descriptions identically.
 *
 * All stored event datetimes are already in the church's local timezone
 * (the sync layer converts before writing), so these helpers do no
 * timezone math — they format the stored value verbatim.
 */
final class CalendarFormat
{
    /**
     * Full human time label for an event: handles all-day, single timed,
     * same-day ranged, and cross-day ranged events. Examples:
     *   "All day"
     *   "May 31 – Jun 3 · All day"
     *   "9:00 am"
     *   "9:00 am – 10:00 am"
     *   "9:00 am – Jun 2, 11:00 am"
     *
     * @param array<string,mixed> $ev
     */
    public static function timeLabel(array $ev): string
    {
        $start = new \DateTime((string)$ev['starts_at']);

        if (!empty($ev['is_all_day'])) {
            $end = !empty($ev['ends_at']) ? new \DateTime((string)$ev['ends_at']) : clone $start;
            if ($end->format('Y-m-d') !== $start->format('Y-m-d')) {
                return $start->format('M j') . ' – ' . $end->format('M j') . ' · All day';
            }
            return 'All day';
        }

        return self::clockRange($ev);
    }

    /**
     * Time-only label (no leading date), for surfaces that print the date
     * separately (homepage cards, day-view list). All-day -> "All day".
     *
     * @param array<string,mixed> $ev
     */
    public static function clockRange(array $ev): string
    {
        if (!empty($ev['is_all_day'])) {
            return 'All day';
        }
        $start = new \DateTime((string)$ev['starts_at']);
        $label = $start->format('g:i a');
        if (!empty($ev['ends_at'])) {
            $end = new \DateTime((string)$ev['ends_at']);
            if ($end->format('Y-m-d') === $start->format('Y-m-d')) {
                $label .= ' – ' . $end->format('g:i a');
            } else {
                $label .= ' – ' . $end->format('M j, g:i a');
            }
        }
        return $label;
    }

    /**
     * Compact start time for tight month-grid entries: "8:45a", "9a",
     * "All day". Minutes are dropped on the hour.
     *
     * @param array<string,mixed> $ev
     */
    public static function shortStart(array $ev): string
    {
        if (!empty($ev['is_all_day'])) {
            return 'All day';
        }
        $start = new \DateTime((string)$ev['starts_at']);
        $ampm  = substr($start->format('a'), 0, 1); // 'a' | 'p'
        return ((int)$start->format('i') === 0)
            ? $start->format('g') . $ampm
            : $start->format('g:i') . $ampm;
    }

    /**
     * Strip the [featured] / [hide] keyword tokens from an event
     * description for display (case-insensitive). Keywords are read from
     * config so a non-default keyword is still removed.
     */
    public static function cleanDescription(?string $desc): string
    {
        if ($desc === null || $desc === '') {
            return '';
        }
        $cfg = $GLOBALS['settle_config']['google_calendar'] ?? [];
        $keywords = [
            (string)($cfg['featured_keyword'] ?? '[featured]'),
            (string)($cfg['hidden_keyword'] ?? '[hide]'),
        ];
        foreach ($keywords as $kw) {
            if ($kw !== '') {
                $desc = preg_replace('/' . preg_quote($kw, '/') . '/i', '', $desc) ?? $desc;
            }
        }
        return trim($desc);
    }

    /**
     * Build "subscribe to this calendar" links from a PUBLIC Google
     * calendar id. Returns ['google' => ..., 'ics' => ..., 'webcal' => ...].
     * All empty when the id is blank/unset (e.g. dev placeholder), so the
     * template can hide the controls. The calendar id of a public calendar
     * is not a secret (only the API key is).
     */
    public static function subscribeUrls(string $calendarId): array
    {
        $calendarId = trim($calendarId);
        // Treat the shipped placeholder as "not set".
        if ($calendarId === '' || stripos($calendarId, 'REPLACE_WITH') === 0) {
            return ['google' => '', 'ics' => '', 'webcal' => ''];
        }
        $enc = rawurlencode($calendarId);
        return [
            'google' => 'https://calendar.google.com/calendar/render?cid=' . $enc,
            'ics'    => 'https://calendar.google.com/calendar/ical/' . $enc . '/public/basic.ics',
            'webcal' => 'webcal://calendar.google.com/calendar/ical/' . $enc . '/public/basic.ics',
        ];
    }
}
