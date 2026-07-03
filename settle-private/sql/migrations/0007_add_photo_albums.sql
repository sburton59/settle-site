-- =============================================================
-- Migration 0007 — add photo_albums + album_media tables
--
-- Settle Memorial UMC
-- Adds the Photo Albums / gallery feature (Flickr replacement,
-- roadmap #12b successor). Mirrors the categories / post_categories
-- pattern: photo_albums is the curated list, album_media is the
-- many-to-many junction to the existing media table.
--
-- A photo only ever appears in the public gallery once it is
-- explicitly assigned to an album via album_media — logos, PDFs, and
-- other Media Library files are never auto-included just by existing
-- in the library.
--
-- Idempotent: safe to run more than once. Apply against any database
-- that was created from a schema.sql predating this migration. The
-- media/users tables already exist; this migration only adds the two
-- new tables.
-- =============================================================

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS photo_albums (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    slug VARCHAR(150) NOT NULL,
    name VARCHAR(150) NOT NULL,
    description VARCHAR(500) NULL,
    event_date DATE NULL,
    cover_media_id INT UNSIGNED NULL,
    is_published TINYINT(1) NOT NULL DEFAULT 0,
    sort_order SMALLINT NOT NULL DEFAULT 0,
    created_by INT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_photo_albums_slug (slug),
    KEY idx_pa_event_date (event_date),
    CONSTRAINT fk_pa_cover   FOREIGN KEY (cover_media_id) REFERENCES media(id) ON DELETE SET NULL,
    CONSTRAINT fk_pa_creator FOREIGN KEY (created_by)     REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS album_media (
    album_id INT UNSIGNED NOT NULL,
    media_id INT UNSIGNED NOT NULL,
    sort_order SMALLINT NOT NULL DEFAULT 0,
    added_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (album_id, media_id),
    KEY idx_am_media (media_id),
    CONSTRAINT fk_am_album FOREIGN KEY (album_id) REFERENCES photo_albums(id) ON DELETE CASCADE,
    CONSTRAINT fk_am_media FOREIGN KEY (media_id) REFERENCES media(id)        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
