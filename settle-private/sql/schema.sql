-- =============================================================
-- Settle Memorial UMC — Site Database Schema
-- Engine: InnoDB, charset utf8mb4
--
-- Fresh installs run this file. Existing databases run the
-- migrations in settle-private/sql/migrations/ in order.
-- =============================================================
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- 1. USERS
CREATE TABLE users (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL,
    email VARCHAR(190) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    display_name VARCHAR(100) NOT NULL,
    role ENUM('admin','editor','author') NOT NULL DEFAULT 'author',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    last_login_at DATETIME NULL,
    password_reset_token CHAR(64) NULL,
    password_reset_expires DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_username (username),
    UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. MEDIA
CREATE TABLE media (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    filename VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    file_size INT UNSIGNED NOT NULL,
    width SMALLINT UNSIGNED NULL,
    height SMALLINT UNSIGNED NULL,
    alt_text VARCHAR(255) NULL,
    caption VARCHAR(500) NULL,
    uploaded_by INT UNSIGNED NOT NULL,
    uploaded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_media_uploaded_by (uploaded_by),
    CONSTRAINT fk_media_user FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. PAGES
CREATE TABLE pages (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    slug VARCHAR(100) NOT NULL,
    title VARCHAR(200) NOT NULL,
    body_html MEDIUMTEXT NOT NULL,
    meta_description VARCHAR(300) NULL,
    hero_media_id INT UNSIGNED NULL,
    parent_id INT UNSIGNED NULL,
    menu_order SMALLINT NOT NULL DEFAULT 0,
    show_in_nav TINYINT(1) NOT NULL DEFAULT 1,
    is_published TINYINT(1) NOT NULL DEFAULT 1,
    updated_by INT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_pages_slug (slug),
    KEY idx_pages_parent (parent_id),
    CONSTRAINT fk_pages_parent FOREIGN KEY (parent_id) REFERENCES pages(id) ON DELETE SET NULL,
    CONSTRAINT fk_pages_hero FOREIGN KEY (hero_media_id) REFERENCES media(id) ON DELETE SET NULL,
    CONSTRAINT fk_pages_user FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. POSTS
CREATE TABLE posts (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    slug VARCHAR(200) NOT NULL,
    title VARCHAR(255) NOT NULL,
    excerpt VARCHAR(500) NULL,
    body_html MEDIUMTEXT NOT NULL,
    featured_media_id INT UNSIGNED NULL,
    author_id INT UNSIGNED NOT NULL,
    status ENUM('draft','published','archived') NOT NULL DEFAULT 'draft',
    published_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_posts_slug (slug),
    KEY idx_posts_author (author_id),
    KEY idx_posts_status_published (status, published_at),
    CONSTRAINT fk_posts_author FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE RESTRICT,
    CONSTRAINT fk_posts_media FOREIGN KEY (featured_media_id) REFERENCES media(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE post_media (
    post_id INT UNSIGNED NOT NULL,
    media_id INT UNSIGNED NOT NULL,
    sort_order SMALLINT NOT NULL DEFAULT 0,
    PRIMARY KEY (post_id, media_id),
    CONSTRAINT fk_pm_post FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    CONSTRAINT fk_pm_media FOREIGN KEY (media_id) REFERENCES media(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4b. BLOG CATEGORIES (added roadmap #3)
-- A curated, editor-managed list of ministry areas (Music, Youth, etc.).
-- Posts relate to categories many-to-many via post_categories. Authors
-- assign from this list; only editors+ create/rename/delete categories.
CREATE TABLE categories (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    slug VARCHAR(150) NOT NULL,
    name VARCHAR(100) NOT NULL,
    sort_order SMALLINT NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_categories_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE post_categories (
    post_id INT UNSIGNED NOT NULL,
    category_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (post_id, category_id),
    KEY idx_pc_category (category_id),
    CONSTRAINT fk_pc_post     FOREIGN KEY (post_id)     REFERENCES posts(id)      ON DELETE CASCADE,
    CONSTRAINT fk_pc_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. SLIDESHOW
CREATE TABLE slideshow_slides (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    media_id INT UNSIGNED NOT NULL,
    caption VARCHAR(255) NULL,
    link_url VARCHAR(500) NULL,
    sort_order SMALLINT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_slides_active_sort (is_active, sort_order),
    CONSTRAINT fk_slides_media FOREIGN KEY (media_id) REFERENCES media(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. STAFF
CREATE TABLE staff (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    full_name VARCHAR(150) NOT NULL,
    title VARCHAR(150) NULL,
    email VARCHAR(190) NULL,
    phone VARCHAR(50) NULL,
    bio_html TEXT NULL,
    photo_media_id INT UNSIGNED NULL,
    sort_order SMALLINT NOT NULL DEFAULT 0,
    is_visible TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_staff_sort (is_visible, sort_order),
    CONSTRAINT fk_staff_photo FOREIGN KEY (photo_media_id) REFERENCES media(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. GOOGLE CALENDAR
CREATE TABLE calendar_events_cache (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    google_event_id VARCHAR(255) NOT NULL,
    google_calendar_id VARCHAR(255) NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    location VARCHAR(255) NULL,
    starts_at DATETIME NOT NULL,
    ends_at DATETIME NULL,
    is_all_day TINYINT(1) NOT NULL DEFAULT 0,
    is_featured TINYINT(1) NOT NULL DEFAULT 0,
    raw_tags VARCHAR(500) NULL,
    html_link VARCHAR(500) NULL,
    last_synced_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_event_uid (google_event_id, google_calendar_id),
    KEY idx_event_starts (starts_at),
    KEY idx_event_featured (is_featured, starts_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE calendar_event_overrides (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    google_event_id VARCHAR(255) NOT NULL,
    force_featured TINYINT(1) NOT NULL DEFAULT 0,
    hide TINYINT(1) NOT NULL DEFAULT 0,
    override_image_id INT UNSIGNED NULL,
    notes VARCHAR(500) NULL,
    updated_by INT UNSIGNED NOT NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_override_event (google_event_id),
    CONSTRAINT fk_ovr_image FOREIGN KEY (override_image_id) REFERENCES media(id) ON DELETE SET NULL,
    CONSTRAINT fk_ovr_user FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 8. SETTINGS
CREATE TABLE settings (
    setting_key VARCHAR(100) NOT NULL,
    setting_value TEXT NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 9. PRAYER REQUESTS
CREATE TABLE prayer_requests (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    submitter_name VARCHAR(150) NULL,
    submitter_email VARCHAR(190) NULL,
    is_private TINYINT(1) NOT NULL DEFAULT 0,
    request_text TEXT NOT NULL,
    ip_address VARCHAR(45) NULL,
    status ENUM('new','prayed','archived') NOT NULL DEFAULT 'new',
    submitted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_prayer_status_date (status, submitted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 10. CONTACT MESSAGES
CREATE TABLE contact_messages (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    sender_name VARCHAR(150) NOT NULL,
    sender_email VARCHAR(190) NULL,
    sender_phone VARCHAR(50) NULL,
    reply_method ENUM('email','phone','either') NOT NULL DEFAULT 'email',
    message_text TEXT NOT NULL,
    ip_address VARCHAR(45) NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    submitted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_contact_read (is_read, submitted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 11. SESSIONS (optional; PHP sessions handle this by default)
CREATE TABLE sessions (
    id CHAR(64) NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_sessions_user (user_id),
    KEY idx_sessions_expires (expires_at),
    CONSTRAINT fk_sessions_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 12. AUDIT LOG
-- Tracks who did what when across the admin panel. Written to by
-- \Settle\AuditLog. Used so far by PrayerRequestController and
-- ContactMessageController; more controllers will hook in over time.
CREATE TABLE audit_log (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT UNSIGNED NULL,
    action VARCHAR(50) NOT NULL,
    entity_type VARCHAR(50) NOT NULL,
    entity_id INT UNSIGNED NULL,
    details JSON NULL,
    ip_address VARCHAR(45) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_audit_user (user_id),
    KEY idx_audit_entity (entity_type, entity_id),
    KEY idx_audit_created (created_at),
    CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 13. MENU ITEMS
-- Data-driven public navigation (roadmap #1.5; see PROJECT_HANDOFF.md §14.5).
-- Flat list with parent_id for one level of nesting. Core provides the
-- tree; per-site templates render the HTML. URLs are stored as plain
-- strings — the admin URL picker filters destinations through
-- \Settle\Features::enabled() so admins cannot link to a disabled
-- feature, but no FK ties a menu item to a specific page row.
CREATE TABLE menu_items (
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

SET FOREIGN_KEY_CHECKS = 1;
