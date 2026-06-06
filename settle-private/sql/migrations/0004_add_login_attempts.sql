-- =============================================================
-- Migration 0004 — add login_attempts
--
-- Settle Memorial UMC
-- Backs the login throttle + password-reset request cap (roadmap #8,
-- \Settle\RateLimiter). Stores timestamped attempt rows under an opaque
-- key (sha256 of ip|lower(identifier)); the limiter counts rows within a
-- rolling window and prunes old ones opportunistically (no cron).
--
-- Deliberately has NO foreign key: attempts are recorded pre-auth and may
-- name a username/email that doesn't exist (so there's no users.id to tie
-- to), and the key is hashed rather than storing the raw identifier.
--
-- Idempotent: CREATE TABLE IF NOT EXISTS. Apply against any database
-- created from a schema.sql predating this migration. Fresh installs get
-- the table directly from schema.sql and do not need this file.
-- =============================================================

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS login_attempts (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    attempt_key CHAR(64) NOT NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_login_attempts_key_time (attempt_key, created_at),
    KEY idx_login_attempts_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
