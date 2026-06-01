-- =============================================================
-- Settle Memorial UMC — settings table seed
--
-- Run once after schema.sql, or any time you want to add missing
-- keys without disturbing existing values.
--
-- Idempotent: re-running this file never overwrites edits made
-- through the admin UI or directly via SQL. The ON DUPLICATE KEY
-- UPDATE clause sets setting_value to itself for existing rows
-- (a deliberate no-op).
--
-- To force a value back to its seeded default, delete the row
-- first:   DELETE FROM settings WHERE setting_key = 'foo';
-- then re-run this file.
--
-- All keys are lowercase snake_case, prefix-grouped for sort order:
--   church_*   — identity, address, phone, hours
--   worship_*  — service times
--   social_*   — social media URLs
--   brand_*    — logo and icon URLs (these may move to per-site
--                config later; for now they live with the rest)
--   meta_*     — site meta description, SEO defaults
-- =============================================================

SET NAMES utf8mb4;

INSERT INTO settings (setting_key, setting_value) VALUES

-- Church identity
('church_name',          'Settle Memorial United Methodist Church'),
('church_short_name',    'Settle Memorial UMC'),
('church_tagline',       'Owensboro, Kentucky'),

-- Physical and mailing addresses (per handoff §4 — 202, not 201)
('church_address_line1', '202 E. 4th Street'),
('church_address_city',  'Owensboro'),
('church_address_state', 'KY'),
('church_address_zip',   '42303'),
('church_mailing',       'P.O. Box 1756, Owensboro, KY 42302'),

-- Contact
('church_phone',         '(270) 684-4226'),
('church_office_email',  ''),
('church_office_hours',  'Tuesday – Thursday, 8:30 a.m. – 3:00 p.m.'),

-- Worship times
('worship_traditional',  'Traditional Worship — 10:00 a.m.'),
('worship_contemporary', 'Contemporary Worship (SHOUT!) — 10:30 a.m.'),
('worship_sunday_school','Sunday School — 9:00 a.m.'),

-- Social
('social_facebook',      'https://www.facebook.com/SettleMem'),
('social_instagram',     'https://www.instagram.com/shoutatsettle/'),
('social_youtube',       'https://www.youtube.com/@settlememorialunitedmethod5839'),

-- Mobile apps (existing presence; staying for now per handoff §11 item 10)
('app_ios_url',          'https://apps.apple.com/app/settle-umc/id1639009037'),
('app_android_url',      'https://play.google.com/store/apps/details?id=com.redpixelstudios.settleumc'),

-- Brand assets (still pulled from the old WP install until they are
-- re-uploaded to the new media library; safe transitional placement)
('brand_logo_url',       'https://settleumc.com/wp-content/uploads/Settle-UMC-Logo.png'),
('brand_favicon_url',    'https://settleumc.com/wp-content/uploads/cropped-Favicon-32x32.png'),
('brand_apple_icon_url', 'https://settleumc.com/wp-content/uploads/cropped-Favicon-180x180.png'),

-- Brand colors (override theme.css :root defaults via an inline <style>
-- emitted by the public layout; validated against /^#[0-9a-fA-F]{6}$/.
-- These match theme.css's shipped defaults so a fresh install looks
-- identical until an admin changes them. Blank/invalid => theme.css wins.
('brand_primary',        '#9E2A2B'),
('brand_ink',            '#2C2C2E'),

-- Meta / SEO defaults
('meta_description',     'Settle Memorial United Methodist Church, Owensboro, Kentucky — a faith journey where you can connect with new friends, learn more about Jesus, and experience His transforming love and grace.'),
('meta_copyright_holder','Settle Memorial United Methodist Church'),

-- Homepage content slots (blank by default; filled in via admin
-- or a later seed pass once copy is finalized)
('homepage_welcome_heading', 'Welcome home'),
('homepage_welcome_lead',    ''),

-- Google Calendar (roadmap #2 — populate when Calendar work begins)
('google_calendar_id',   '')

ON DUPLICATE KEY UPDATE setting_value = setting_value;
