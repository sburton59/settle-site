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
 *   - hide            → event is dropped from public output entirely
 *   - force_featured  → event is treated as featured regardless of tag
 *   - override_image  → an image to show instead of nothing
 *   - notes           → a website-only blurb
 *
 * Overrides are keyed by google_event_id (a stable per-event id from
 * Google). The admin editor for creating overrides is deferred (see the
 * v1.9 handoff); for now overrides are applied here at render time and
 * authored directly in the table.
 *
 * The effective "featured" flag is (cache.is_featured OR
 * override.force_featured) AND NOT override.hide. Rows are returned with
 * an `effective_featured` column so templates do not re-derive it.
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
        WHERE COALESCE(o.hide, 0) = 0
    SQL;

    /**
     * Upcoming events for the homepage widget: events starting today or
     * later, within $days, featured first, then chronological.
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
                 ORDER BY effective_featured DESC, c.starts_at ASC
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
             WHERE COALESCE(o.hide, 0) = 0
               AND c.starts_at >= :today
               AND c.starts_at < :horizon',
            [':today' => $today, ':horizon' => $horizon]
        )->fetchColumn();

        return (int)$n > 0;
    }
}
