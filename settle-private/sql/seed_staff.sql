-- =============================================================
-- Settle Memorial UMC — staff directory seed (image pass, v3.2)
--
-- Creates the 11 staff-directory rows that match the live
-- settleumc.com /listings/staff/ roster, in the live display order.
-- Photos are NOT set here — they are attached afterward by
-- bin/migrate-wp-images.php (which imports each portrait into the
-- Media Library and points photo_media_id at it). RUN THIS FIRST,
-- then run that CLI.
--
-- SAFE TO RE-RUN. Every INSERT is gated by WHERE NOT EXISTS on
-- full_name (the same idempotent pattern as seed_pages.sql), so
-- running the file twice does nothing for a name that already
-- exists, and it never touches a row you have since edited through
-- /admin/staff.
--
-- EMAILS ARE SEEDED NULL. The live site obfuscates staff addresses
-- (Cloudflare email-protection), so they could not be recovered by
-- migration. Add each address through /admin/staff after seeding.
--
-- Titles are taken verbatim from the live roster. Libby Kassinger
-- had no title on the live site, so hers is left NULL — set it in
-- /admin/staff if/when you have one.
--
-- sort_order uses increments of 10 (matching the models' reorder
-- convention) so you can drag-to-reorder or squeeze rows in later
-- without a full renumber. is_visible = 1 (all shown).
-- =============================================================

SET NAMES utf8mb4;

-- -------------------------------------------------------------
-- Staff rows (live order). Pattern: INSERT ... SELECT ...
-- WHERE NOT EXISTS (full_name match).
-- -------------------------------------------------------------

INSERT INTO staff (full_name, title, email, phone, bio_html, photo_media_id, sort_order, is_visible)
SELECT 'Mark Dickinson', 'Senior Pastor', NULL, NULL, NULL, NULL, 10, 1
WHERE NOT EXISTS (SELECT 1 FROM staff WHERE full_name = 'Mark Dickinson');

INSERT INTO staff (full_name, title, email, phone, bio_html, photo_media_id, sort_order, is_visible)
SELECT 'Alecia Meyer', 'Church Administrator', NULL, NULL, NULL, NULL, 20, 1
WHERE NOT EXISTS (SELECT 1 FROM staff WHERE full_name = 'Alecia Meyer');

INSERT INTO staff (full_name, title, email, phone, bio_html, photo_media_id, sort_order, is_visible)
SELECT 'Aimee Keith', 'Children''s Ministry and Parent''s Day Out Director', NULL, NULL, NULL, NULL, 30, 1
WHERE NOT EXISTS (SELECT 1 FROM staff WHERE full_name = 'Aimee Keith');

INSERT INTO staff (full_name, title, email, phone, bio_html, photo_media_id, sort_order, is_visible)
SELECT 'Kim Massey', 'Church Accountant', NULL, NULL, NULL, NULL, 40, 1
WHERE NOT EXISTS (SELECT 1 FROM staff WHERE full_name = 'Kim Massey');

INSERT INTO staff (full_name, title, email, phone, bio_html, photo_media_id, sort_order, is_visible)
SELECT 'Rebecca Volk', 'Traditional Music Director', NULL, NULL, NULL, NULL, 50, 1
WHERE NOT EXISTS (SELECT 1 FROM staff WHERE full_name = 'Rebecca Volk');

INSERT INTO staff (full_name, title, email, phone, bio_html, photo_media_id, sort_order, is_visible)
SELECT 'Chris Tolliver', 'Church Organist', NULL, NULL, NULL, NULL, 60, 1
WHERE NOT EXISTS (SELECT 1 FROM staff WHERE full_name = 'Chris Tolliver');

INSERT INTO staff (full_name, title, email, phone, bio_html, photo_media_id, sort_order, is_visible)
SELECT 'Jeff Keeley', 'Middle School Youth', NULL, NULL, NULL, NULL, 70, 1
WHERE NOT EXISTS (SELECT 1 FROM staff WHERE full_name = 'Jeff Keeley');

INSERT INTO staff (full_name, title, email, phone, bio_html, photo_media_id, sort_order, is_visible)
SELECT 'Libby Kassinger', NULL, NULL, NULL, NULL, NULL, 80, 1
WHERE NOT EXISTS (SELECT 1 FROM staff WHERE full_name = 'Libby Kassinger');

INSERT INTO staff (full_name, title, email, phone, bio_html, photo_media_id, sort_order, is_visible)
SELECT 'Lori Roach', 'Church Secretary', NULL, NULL, NULL, NULL, 90, 1
WHERE NOT EXISTS (SELECT 1 FROM staff WHERE full_name = 'Lori Roach');

INSERT INTO staff (full_name, title, email, phone, bio_html, photo_media_id, sort_order, is_visible)
SELECT 'Sharee Best', 'Preschool Director', NULL, NULL, NULL, NULL, 100, 1
WHERE NOT EXISTS (SELECT 1 FROM staff WHERE full_name = 'Sharee Best');

INSERT INTO staff (full_name, title, email, phone, bio_html, photo_media_id, sort_order, is_visible)
SELECT 'Wesley Marcum', 'Senior High Youth and Young Adults', NULL, NULL, NULL, NULL, 110, 1
WHERE NOT EXISTS (SELECT 1 FROM staff WHERE full_name = 'Wesley Marcum');
