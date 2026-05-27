-- =============================================================
-- Migration 0001 — add menu_items table
--
-- Settle Memorial UMC / multi-church core
-- Adds the data-driven public navigation table introduced in
-- roadmap #1.5 (see PROJECT_HANDOFF.md §14.5).
--
-- Idempotent: safe to run more than once. Apply against any
-- database that was created from a pre-migration schema.sql.
--
-- This is the FIRST migration file in the project, and establishes
-- the convention for the migrations/ folder. Subsequent migrations
-- follow the 0002_*, 0003_* numbering with a short verb-noun name.
-- =============================================================

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS menu_items (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    label       VARCHAR(100) NOT NULL,
    url         VARCHAR(500) NOT NULL DEFAULT '',
    parent_id   INT UNSIGNED NULL,
    sort_order  SMALLINT NOT NULL DEFAULT 0,
    target      ENUM('_self','_blank') NOT NULL DEFAULT '_self',
    is_active   TINYINT(1) NOT NULL DEFAULT 1,
    updated_by  INT UNSIGNED NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_menu_parent_sort (parent_id, is_active, sort_order),
    CONSTRAINT fk_menu_parent FOREIGN KEY (parent_id) REFERENCES menu_items(id) ON DELETE CASCADE,
    CONSTRAINT fk_menu_user   FOREIGN KEY (updated_by) REFERENCES users(id)      ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
