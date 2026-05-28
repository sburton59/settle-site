-- =============================================================
-- Settle Memorial UMC — initial menu_items seed
--
-- Run once after the menu_items table is created (migration 0001).
-- Idempotent: re-running will not duplicate items — each INSERT
-- checks that no item with the same label exists already. Edits
-- you make through /admin/menu after seeding are preserved.
--
-- Conservative URL choices: only includes routes we know exist
-- (the root, staff, prayer, contact) plus a parent-only "Connect"
-- group. Add About / Sundays / Give and the Connect children
-- through /admin/menu after you have the corresponding pages.
--
-- Sort order pattern: top-level items at 10/20/30/40/50; children
-- at 10/20/30/40 within their parent. Increment-by-10 matches
-- staff and slideshow_slides for consistency.
-- =============================================================

SET NAMES utf8mb4;

-- Top-level items. Each insert is gated by NOT EXISTS so re-running
-- the file does nothing on items that are already present.

INSERT INTO menu_items (label, url, parent_id, sort_order, target, is_active)
SELECT 'Home', '/', NULL, 10, '_self', 1
WHERE NOT EXISTS (SELECT 1 FROM menu_items WHERE label = 'Home' AND parent_id IS NULL);

INSERT INTO menu_items (label, url, parent_id, sort_order, target, is_active)
SELECT 'Staff', '/staff', NULL, 20, '_self', 1
WHERE NOT EXISTS (SELECT 1 FROM menu_items WHERE label = 'Staff' AND parent_id IS NULL);

INSERT INTO menu_items (label, url, parent_id, sort_order, target, is_active)
SELECT 'Connect', '', NULL, 30, '_self', 1
WHERE NOT EXISTS (SELECT 1 FROM menu_items WHERE label = 'Connect' AND parent_id IS NULL);

INSERT INTO menu_items (label, url, parent_id, sort_order, target, is_active)
SELECT 'Prayer', '/prayer', NULL, 40, '_self', 1
WHERE NOT EXISTS (SELECT 1 FROM menu_items WHERE label = 'Prayer' AND parent_id IS NULL);

INSERT INTO menu_items (label, url, parent_id, sort_order, target, is_active)
SELECT 'Contact', '/contact', NULL, 50, '_self', 1
WHERE NOT EXISTS (SELECT 1 FROM menu_items WHERE label = 'Contact' AND parent_id IS NULL);

-- Children of "Connect". We look up its id at insert time so we don't
-- depend on the parent having a specific id. If the "Connect" parent
-- was not created (e.g. because someone deleted it through the admin
-- before re-running), the children inserts are also skipped — the
-- LIMIT 1 subquery returns NULL and the WHERE NOT EXISTS still
-- evaluates false-positive only if another item already shares the
-- label with that parent_id, which it doesn't.

INSERT INTO menu_items (label, url, parent_id, sort_order, target, is_active)
SELECT 'Staff Directory',
       '/staff',
       (SELECT id FROM (SELECT id FROM menu_items WHERE label = 'Connect' AND parent_id IS NULL LIMIT 1) AS p),
       10, '_self', 1
WHERE EXISTS (SELECT 1 FROM menu_items WHERE label = 'Connect' AND parent_id IS NULL)
  AND NOT EXISTS (
        SELECT 1 FROM menu_items
        WHERE label = 'Staff Directory'
          AND parent_id = (SELECT id FROM (SELECT id FROM menu_items WHERE label = 'Connect' AND parent_id IS NULL LIMIT 1) AS p2)
      );

INSERT INTO menu_items (label, url, parent_id, sort_order, target, is_active)
SELECT 'Prayer Requests',
       '/prayer',
       (SELECT id FROM (SELECT id FROM menu_items WHERE label = 'Connect' AND parent_id IS NULL LIMIT 1) AS p),
       20, '_self', 1
WHERE EXISTS (SELECT 1 FROM menu_items WHERE label = 'Connect' AND parent_id IS NULL)
  AND NOT EXISTS (
        SELECT 1 FROM menu_items
        WHERE label = 'Prayer Requests'
          AND parent_id = (SELECT id FROM (SELECT id FROM menu_items WHERE label = 'Connect' AND parent_id IS NULL LIMIT 1) AS p2)
      );

INSERT INTO menu_items (label, url, parent_id, sort_order, target, is_active)
SELECT 'Contact Us',
       '/contact',
       (SELECT id FROM (SELECT id FROM menu_items WHERE label = 'Connect' AND parent_id IS NULL LIMIT 1) AS p),
       30, '_self', 1
WHERE EXISTS (SELECT 1 FROM menu_items WHERE label = 'Connect' AND parent_id IS NULL)
  AND NOT EXISTS (
        SELECT 1 FROM menu_items
        WHERE label = 'Contact Us'
          AND parent_id = (SELECT id FROM (SELECT id FROM menu_items WHERE label = 'Connect' AND parent_id IS NULL LIMIT 1) AS p2)
      );

-- That's the initial set. Add more items through /admin/menu rather
-- than editing this file — it documents the seed, not the live state.
