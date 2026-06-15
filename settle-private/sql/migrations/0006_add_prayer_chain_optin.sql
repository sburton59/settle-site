-- =============================================================
-- Migration 0006 — add allow_prayer_chain to prayer_requests
--
-- Settle Memorial UMC
-- Backs the prayer-chain opt-in (roadmap #6 follow-up). The public
-- prayer form now offers an explicit, unchecked "share with our
-- prayer-chain volunteers" checkbox. This column records that consent.
--
-- Default 0 is meaningful and safe: it is an OPT-IN, so every existing
-- row (submitted before this feature) is treated as "not on the chain,"
-- which is the privacy-preserving default. The submit path additionally
-- forces this to 0 whenever is_private = 1 — a private request never
-- goes on the chain regardless of the checkbox — so the two flags can
-- never contradict each other in stored data.
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
      AND TABLE_NAME   = 'prayer_requests'
      AND COLUMN_NAME  = 'allow_prayer_chain'
);

SET @ddl := IF(
    @col_exists = 0,
    'ALTER TABLE prayer_requests
        ADD COLUMN allow_prayer_chain TINYINT(1) NOT NULL DEFAULT 0 AFTER is_private',
    'DO 0'
);

PREPARE stmt FROM @ddl;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
