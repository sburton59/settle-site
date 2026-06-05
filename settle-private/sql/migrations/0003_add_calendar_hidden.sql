-- =============================================================
-- Migration 0003 — add is_hidden to calendar_events_cache
--
-- Settle Memorial UMC
-- Adds the [hide] tag capability (roadmap #4b). A [hide] token in a
-- Google Calendar event's description (case-insensitive, the mirror of
-- [featured]) now removes that event from the public site. The sync
-- layer (\Settle\GoogleCalendar) sets is_hidden; the render overlay
-- (\Settle\Model\CalendarEvent) filters is_hidden = 0 from every public
-- query, exactly as override.hide already does.
--
-- Idempotent: guarded against re-running. Apply against any database
-- created from a schema.sql predating this migration. Fresh installs get
-- the column directly from schema.sql and do not need this file.
-- =============================================================

SET NAMES utf8mb4;

SET @col_exists := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'calendar_events_cache'
      AND COLUMN_NAME  = 'is_hidden'
);

SET @ddl := IF(
    @col_exists = 0,
    'ALTER TABLE calendar_events_cache
        ADD COLUMN is_hidden TINYINT(1) NOT NULL DEFAULT 0 AFTER is_featured',
    'DO 0'
);

PREPARE stmt FROM @ddl;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
