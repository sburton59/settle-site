-- =============================================================
-- Migration 0005 — add thumbnail_filename to media
--
-- Settle Memorial UMC
-- Backs the thumbnail variant (roadmap #9). \Settle\Upload now writes a
-- small (<=600px long edge) thumbnail next to each uploaded image and
-- records its relative path here; the admin grid, the editor image
-- picker, and public blog cards render the thumbnail instead of the
-- full-size file.
--
-- NULL is meaningful: it means "no thumbnail" (a PDF, or a legacy image
-- uploaded before this feature). Every consumer falls back to the
-- full-size filename when this column is NULL, so an un-backfilled row
-- still renders — just heavier. Run bin/thumbnail-backfill.php once after
-- applying this migration to generate thumbnails for existing images.
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
      AND TABLE_NAME   = 'media'
      AND COLUMN_NAME  = 'thumbnail_filename'
);

SET @ddl := IF(
    @col_exists = 0,
    'ALTER TABLE media
        ADD COLUMN thumbnail_filename VARCHAR(255) NULL AFTER filename',
    'DO 0'
);

PREPARE stmt FROM @ddl;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
