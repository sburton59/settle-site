-- =============================================================
-- Migration 0002 — add categories + post_categories tables
--
-- Settle Memorial UMC
-- Adds blog categories (roadmap #3). Categories are a curated,
-- editor-managed list of ministry areas (Music, Youth, Children's
-- Programs, Senior Programs, etc.). Posts relate to categories
-- many-to-many through post_categories. Authors assign from the
-- existing list; only editors+ create/rename/delete categories.
--
-- Idempotent: safe to run more than once. Apply against any database
-- that was created from a schema.sql predating this migration.
--
-- The posts and post_media tables already existed in schema.sql; this
-- migration only adds the two category tables.
-- =============================================================

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS categories (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    slug        VARCHAR(150) NOT NULL,
    name        VARCHAR(100) NOT NULL,
    sort_order  SMALLINT NOT NULL DEFAULT 0,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_categories_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS post_categories (
    post_id     INT UNSIGNED NOT NULL,
    category_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (post_id, category_id),
    KEY idx_pc_category (category_id),
    CONSTRAINT fk_pc_post     FOREIGN KEY (post_id)     REFERENCES posts(id)      ON DELETE CASCADE,
    CONSTRAINT fk_pc_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
