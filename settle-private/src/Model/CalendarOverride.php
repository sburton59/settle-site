<?php
declare(strict_types=1);

namespace Settle\Model;

use Settle\Database;

/**
 * Write side + admin listing for calendar_event_overrides.
 *
 * The public read model (\Settle\Model\CalendarEvent) overlays overrides
 * at render and intentionally filters out hidden events. This model is the
 * admin counterpart: it lists EVERY cached event (including hidden ones)
 * with its current override state, and authors the website-only fields.
 *
 * Scope (roadmap #4b): hide and feature are driven by the [hide] /
 * [featured] tags in the Google Calendar description (cache.is_hidden /
 * cache.is_featured), so this writer touches ONLY the two fields that
 * cannot live in a calendar tag: override_image_id and notes. It never
 * writes hide / force_featured, so a manually-set hide or force_featured
 * row is preserved across a save. clear() removes the whole override row.
 *
 * Overrides key on google_event_id (UNIQUE). Admin routes carry the cache
 * row's numeric id for clean URLs; the controller resolves google_event_id
 * via findForAdmin() before calling upsert()/clear().
 */
final class CalendarOverride
{
    /**
     * Cached event + its override state, keyed by the cache row id.
     * Returns null if no such cached event exists.
     *
     * @return array<string, mixed>|null
     */
    public static function findForAdmin(int $cacheId): ?array
    {
        $row = Database::query(
            self::ADMIN_SELECT . ' WHERE c.id = :id',
            [':id' => $cacheId]
        )->fetch();

        return $row === false ? null : $row;
    }

    /**
     * Every cached event with its override state, INCLUDING hidden ones,
     * chronological. Used by the admin listing.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function allEventsForAdmin(): array
    {
        return Database::query(
            self::ADMIN_SELECT . ' ORDER BY c.starts_at ASC, c.id ASC'
        )->fetchAll();
    }

    /**
     * Create or update the website-only override fields (image + notes)
     * for an event, keyed on its google_event_id. Leaves hide and
     * force_featured untouched on update; they default to 0 on insert.
     *
     * @param int      $userId  $_SESSION['user_id'] — stamped to updated_by (NOT NULL).
     */
    public static function upsert(string $googleEventId, ?int $imageId, ?string $notes, int $userId): void
    {
        Database::query(
            'INSERT INTO calendar_event_overrides
                (google_event_id, override_image_id, notes, updated_by, updated_at)
             VALUES
                (:gid, :img, :notes, :uid, NOW())
             ON DUPLICATE KEY UPDATE
                override_image_id = VALUES(override_image_id),
                notes             = VALUES(notes),
                updated_by        = VALUES(updated_by),
                updated_at        = NOW()',
            [
                ':gid'   => $googleEventId,
                ':img'   => $imageId,
                ':notes' => $notes,
                ':uid'   => $userId,
            ]
        );
    }

    /**
     * Remove the override row for an event entirely (reverts it to pure
     * Google-driven behaviour). Safe to call when no row exists.
     */
    public static function clear(string $googleEventId): void
    {
        Database::query(
            'DELETE FROM calendar_event_overrides WHERE google_event_id = :gid',
            [':gid' => $googleEventId]
        );
    }

    /**
     * Shared SELECT for the admin side. Unlike CalendarEvent::BASE_SELECT
     * it does NOT filter hidden events — the admin must see and manage
     * them. Surfaces the tag-driven flags (is_featured / is_hidden) as
     * read-only context and the override fields for editing.
     */
    private const ADMIN_SELECT = <<<SQL
        SELECT
            c.id,
            c.google_event_id,
            c.title,
            c.location,
            c.starts_at,
            c.ends_at,
            c.is_all_day,
            c.is_featured,
            c.is_hidden,
            c.html_link,
            (o.id IS NOT NULL)        AS has_override,
            o.force_featured,
            o.hide                    AS override_hide,
            o.override_image_id,
            o.notes                   AS override_notes,
            m.filename                AS override_image_filename,
            m.alt_text                AS override_image_alt
        FROM calendar_events_cache c
        LEFT JOIN calendar_event_overrides o ON o.google_event_id = c.google_event_id
        LEFT JOIN media m ON m.id = o.override_image_id
    SQL;
}
