<?php
declare(strict_types=1);

namespace Settle\Model;

use Settle\Database;

/**
 * Read-side model for cached Google Calendar events.
 *
 * The write side (\Settle\GoogleCalendar) keeps `calendar_events_cache`
 * in sync with Google. This model reads that cache for public rendering,
 * overlaying any rows in `calendar_event_overrides` so the church can
 * make website-only adjustments it cannot express in Google Calendar:
 *
 *   - hide            → event is dropped from public output entirely.
 *                       Driven either by the [hide] tag in the Google
 *                       Calendar description (cache.is_hidden, synced) or
 *                       by an override row (override.hide, manual).
 *   - force_featured  → event is treated as featured regardless of tag
 *   - override_image  → an image to show instead of nothing
 *   - notes           → a website-only blurb
 *
 * Overrides are keyed by google_event_id (a stable per-event id from
 * Google). Hide and feature are authored as [hide] / [featured] tags in
 * the Google Calendar event description; the admin override editor
 * (\Settle\Controller\CalendarOverrideController) authors the website-only
 * image and notes.
 *
 * The effective "featured" flag is (cache.is_featured OR
 * override.force_featured). A row is excluded when cache.is_hidden = 1
 * (the [hide] tag) OR override.hide = 1. Rows are returned with an
 * `effective_featured` column so templates do not re-derive it.
 *
 * All stored datetimes are already in the church's local timezone (the
 * sync layer converts before writing), so this model does no timezone
 * math — it compares against the local "now".
 */
final class CalendarEvent
{
    /**
     * The shared SELECT + LEFT JOIN that overlays overrides and media.
     * Callers append their own WHERE / ORDER BY / LIMIT.
     */
    private const BASE_SELECT = <<<SQL
        SELECT
            c.id,
            c.google_event_id,
            c.title,
            c.description,
            c.location,
            c.starts_at,
            c.ends_at,
            c.is_all_day,
            c.html_link,
            (c.is_featured = 1 OR COALESCE(o.force_featured, 0) = 1) AS effective_featured,
            o.notes            AS override_notes,
            o.override_image_id,
            m.filename         AS override_image_filename,
            m.alt_text         AS override_image_alt
        FROM calendar_events_cache c
        LEFT JOIN calendar_event_overrides o ON o.google_event_id = c.google_event_id
        LEFT JOIN media m ON m.id = o.override_image_id
        WHERE c.is_hidden = 0
          AND COALESCE(o.hide, 0) = 0
    SQL;

    /**
     * Upcoming events for the homepage widget: events starting today or
     * later, within $days, in chronological (date) order. The featured
     * flag is still surfaced (effective_featured) for the star badge, but
     * no longer reorders the list.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function upcoming(int $limit = 3, int $days = 90): array
    {
        $limit = max(1, $limit);
        $days  = max(1, $days);

        $today   = (new \DateTime('today'))->format('Y-m-d H:i:s');
        $horizon = (new \DateTime('today'))->modify("+{$days} days")->format('Y-m-d H:i:s');

        // LIMIT is an integer cast inline (PDO in non-emulated mode cannot
        // bind LIMIT as a string parameter). Safe: it is a hard (int) cast.
        $sql = self::BASE_SELECT
             . ' AND c.starts_at >= :today
                 AND c.starts_at < :horizon
                 ORDER BY c.starts_at ASC, c.id ASC
                 LIMIT ' . (int)$limit;

        return Database::query($sql, [
            ':today'   => $today,
            ':horizon' => $horizon,
        ])->fetchAll();
    }

    /**
     * Every event overlapping a given month, chronological. Used by the
     * public month-grid page. An event "overlaps" the month if it starts
     * before the month ends and ends on/after the month starts.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function forMonth(int $year, int $month): array
    {
        // Clamp to a sane range, then build the month bounds.
        $year  = max(1970, min(9999, $year));
        $month = max(1, min(12, $month));

        $monthStart = (new \DateTime())->setDate($year, $month, 1)->setTime(0, 0, 0);
        $monthEndEx = (clone $monthStart)->modify('first day of next month');

        $sql = self::BASE_SELECT
             . ' AND c.starts_at < :month_end
                 AND COALESCE(c.ends_at, c.starts_at) >= :month_start
                 ORDER BY c.starts_at ASC, c.id ASC';

        return Database::query($sql, [
            ':month_end'   => $monthEndEx->format('Y-m-d H:i:s'),
            ':month_start' => $monthStart->format('Y-m-d H:i:s'),
        ])->fetchAll();
    }

    /**
     * Count of events overlapping a month — cheap helper for the homepage
     * widget's "is the calendar populated?" guard without pulling rows.
     */
    public static function hasAnyUpcoming(int $days = 90): bool
    {
        $today   = (new \DateTime('today'))->format('Y-m-d H:i:s');
        $horizon = (new \DateTime('today'))->modify('+' . max(1, $days) . ' days')->format('Y-m-d H:i:s');

        $n = Database::query(
            'SELECT COUNT(*)
             FROM calendar_events_cache c
             LEFT JOIN calendar_event_overrides o ON o.google_event_id = c.google_event_id
             WHERE c.is_hidden = 0
               AND COALESCE(o.hide, 0) = 0
               AND c.starts_at >= :today
               AND c.starts_at < :horizon',
            [':today' => $today, ':horizon' => $horizon]
        )->fetchColumn();

        return (int)$n > 0;
    }

    /**
     * Every event overlapping a date range [from, to] (inclusive), in
     * chronological order. Used by the month-grid builder (which needs the
     * full visible grid, including spillover days from adjacent months) and
     * by forDay(). An event overlaps when it starts before the range ends
     * and ends on/after the range starts.
     *
     * @param string $fromDate 'Y-m-d' (inclusive lower bound)
     * @param string $toDate   'Y-m-d' (inclusive upper bound)
     * @return array<int, array<string, mixed>>
     */
    public static function forRange(string $fromDate, string $toDate): array
    {
        $start  = (new \DateTime($fromDate))->setTime(0, 0, 0);
        $endExc = (new \DateTime($toDate))->setTime(0, 0, 0)->modify('+1 day');

        $sql = self::BASE_SELECT
             . ' AND c.starts_at < :end_exc
                 AND COALESCE(c.ends_at, c.starts_at) >= :start
                 ORDER BY c.starts_at ASC, c.id ASC';

        return Database::query($sql, [
            ':end_exc' => $endExc->format('Y-m-d H:i:s'),
            ':start'   => $start->format('Y-m-d H:i:s'),
        ])->fetchAll();
    }

    /**
     * Events overlapping a single calendar day (handles multi-day events
     * that pass through the day). Used by the public day view.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function forDay(string $ymd): array
    {
        return self::forRange($ymd, $ymd);
    }

    /**
     * One page of upcoming events for the public list view: events that are
     * still current or in the future (ends today or later, so an in-progress
     * multi-day event is included), chronological, paginated.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function upcomingList(int $limit, int $offset): array
    {
        $limit  = max(1, $limit);
        $offset = max(0, $offset);

        $today = (new \DateTime('today'))->format('Y-m-d H:i:s'); // PHP-bound, §13.8

        // LIMIT/OFFSET are integer-cast inline — PDO in non-emulated mode
        // cannot bind them as parameters. Safe: hard (int) casts above.
        $sql = self::BASE_SELECT
             . ' AND COALESCE(c.ends_at, c.starts_at) >= :today
                 ORDER BY c.starts_at ASC, c.id ASC
                 LIMIT ' . (int)$limit . ' OFFSET ' . (int)$offset;

        return Database::query($sql, [':today' => $today])->fetchAll();
    }

    /** Total upcoming (current-or-future) events, for list-view pagination. */
    public static function countUpcoming(): int
    {
        $today = (new \DateTime('today'))->format('Y-m-d H:i:s');

        $n = Database::query(
            'SELECT COUNT(*)
             FROM calendar_events_cache c
             LEFT JOIN calendar_event_overrides o ON o.google_event_id = c.google_event_id
             WHERE c.is_hidden = 0
               AND COALESCE(o.hide, 0) = 0
               AND COALESCE(c.ends_at, c.starts_at) >= :today',
            [':today' => $today]
        )->fetchColumn();

        return (int)$n;
    }
}
