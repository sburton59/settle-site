-- =============================================================
-- Settle Memorial UMC — content-migration page + menu seed (#10)
--
-- Creates the settleumc.com content pages as DRAFTS (is_published = 0)
-- and wires the public navigation to match the live site, extended to
-- three tiers (Connect -> Missions -> Mission Partners, etc.).
--
-- SAFE TO RE-RUN. Every INSERT is gated by WHERE NOT EXISTS, so running
-- the file twice does nothing on rows that already exist. Edits you make
-- through /admin/pages and /admin/menu after seeding are preserved.
--
-- Pages are seeded HIDDEN (drafts) with placeholder bodies; real content
-- is filled in afterward (Batch 2) through the admin editor, then each
-- page is Published. Page bodies are intentionally minimal here.
--
-- The menu rows are ADDITIVE to the original seed_menu.sql set
-- (Home / Staff / Connect / Prayer / Contact). New top-level items are
-- given high sort_order values so they append after the existing ones;
-- REORDER them to taste in /admin/menu (drag-to-reorder).
--
-- updated_by is set to the first user (the seeded admin). If you have no
-- users yet, seed the admin first or this file's page inserts will fail
-- the foreign key.
-- =============================================================

SET NAMES utf8mb4;

-- -------------------------------------------------------------
-- 1. PAGES (drafts)
-- -------------------------------------------------------------
-- Pattern: INSERT ... SELECT ... WHERE NOT EXISTS (slug match).
-- body_html is a placeholder; meta_description is a short stub.

INSERT INTO pages (slug, title, body_html, meta_description, show_in_nav, is_published, updated_by)
SELECT 'im-new', 'I''m New', '<p><em>Draft — content to be added during migration.</em></p>', 'New to Settle Memorial? Start here.', 0, 0,
       (SELECT id FROM users ORDER BY id LIMIT 1)
WHERE NOT EXISTS (SELECT 1 FROM pages WHERE slug = 'im-new');

-- "Connect" landing page (#10b). Unlike the migration drafts above this is
-- seeded PUBLISHED, because the homepage "Grow in Faith" feature band links
-- to it. Its body links on to the ministry pages, which light up as those
-- drafts are published during the #10 content migration. The existing
-- "Connect" nav item is a no-link dropdown parent, so this page does not
-- appear in the nav (show_in_nav = 0); point that parent at /page/connect
-- in /admin/menu if you want the dropdown label itself to be clickable.
INSERT INTO pages (slug, title, body_html, meta_description, show_in_nav, is_published, updated_by)
SELECT 'connect', 'Connect', '<p>There''s a place for everyone at Settle Memorial. However you''d like to grow, serve, or get involved, here''s where to start.</p><ul><li><a href="/page/im-new">New here? Start with I''m New</a></li><li><a href="/page/children">Children''s Ministry</a></li><li><a href="/page/youth">Youth Ministry</a></li><li><a href="/page/adult-ministries">Adult Ministries</a></li><li><a href="/page/the-roadrunners">The Roadrunners</a></li><li><a href="/page/settle-preschool">Settle Preschool</a></li><li><a href="/page/parents-day-out">Parents'' Day Out</a></li></ul>', 'Find your place at Settle Memorial — ministries and groups for children, youth, and adults.', 0, 1,
       (SELECT id FROM users ORDER BY id LIMIT 1)
WHERE NOT EXISTS (SELECT 1 FROM pages WHERE slug = 'connect');

INSERT INTO pages (slug, title, body_html, meta_description, show_in_nav, is_published, updated_by)
SELECT 'sundays', 'Sundays Worship Services', '<p><em>Draft — content to be added during migration.</em></p>', 'Worship service times and what to expect on Sunday.', 0, 0,
       (SELECT id FROM users ORDER BY id LIMIT 1)
WHERE NOT EXISTS (SELECT 1 FROM pages WHERE slug = 'sundays');

INSERT INTO pages (slug, title, body_html, meta_description, show_in_nav, is_published, updated_by)
SELECT 'sermons', 'Sermons', '<p><em>Draft — content to be added during migration.</em></p>', 'Watch recent sermons and worship services.', 0, 0,
       (SELECT id FROM users ORDER BY id LIMIT 1)
WHERE NOT EXISTS (SELECT 1 FROM pages WHERE slug = 'sermons');

INSERT INTO pages (slug, title, body_html, meta_description, show_in_nav, is_published, updated_by)
SELECT 'worship-bulletin', 'Worship Bulletin', '<p><em>Draft — content to be added during migration.</em></p>', 'This Sunday''s worship bulletin.', 0, 0,
       (SELECT id FROM users ORDER BY id LIMIT 1)
WHERE NOT EXISTS (SELECT 1 FROM pages WHERE slug = 'worship-bulletin');

INSERT INTO pages (slug, title, body_html, meta_description, show_in_nav, is_published, updated_by)
SELECT 'weekly-schedule', 'Weekly Schedule', '<p><em>Draft — content to be added during migration.</em></p>', 'A weekly schedule of activities at Settle Memorial.', 0, 0,
       (SELECT id FROM users ORDER BY id LIMIT 1)
WHERE NOT EXISTS (SELECT 1 FROM pages WHERE slug = 'weekly-schedule');

INSERT INTO pages (slug, title, body_html, meta_description, show_in_nav, is_published, updated_by)
SELECT 'directions-parking', 'Directions & Parking', '<p><em>Draft — content to be added during migration.</em></p>', 'How to find us and where to park.', 0, 0,
       (SELECT id FROM users ORDER BY id LIMIT 1)
WHERE NOT EXISTS (SELECT 1 FROM pages WHERE slug = 'directions-parking');

INSERT INTO pages (slug, title, body_html, meta_description, show_in_nav, is_published, updated_by)
SELECT 'about', 'About Us', '<p><em>Draft — content to be added during migration.</em></p>', 'Learn about Settle Memorial United Methodist Church.', 0, 0,
       (SELECT id FROM users ORDER BY id LIMIT 1)
WHERE NOT EXISTS (SELECT 1 FROM pages WHERE slug = 'about');

INSERT INTO pages (slug, title, body_html, meta_description, show_in_nav, is_published, updated_by)
SELECT 'children', 'Children', '<p><em>Draft — content to be added during migration.</em></p>', 'Children''s ministry at Settle Memorial.', 0, 0,
       (SELECT id FROM users ORDER BY id LIMIT 1)
WHERE NOT EXISTS (SELECT 1 FROM pages WHERE slug = 'children');

INSERT INTO pages (slug, title, body_html, meta_description, show_in_nav, is_published, updated_by)
SELECT 'settle-preschool', 'Settle Preschool', '<p><em>Draft — content to be added during migration.</em></p>', 'Settle Preschool program information.', 0, 0,
       (SELECT id FROM users ORDER BY id LIMIT 1)
WHERE NOT EXISTS (SELECT 1 FROM pages WHERE slug = 'settle-preschool');

INSERT INTO pages (slug, title, body_html, meta_description, show_in_nav, is_published, updated_by)
SELECT 'parents-day-out', 'Parent''s Day Out', '<p><em>Draft — content to be added during migration.</em></p>', 'Parent''s Day Out program information.', 0, 0,
       (SELECT id FROM users ORDER BY id LIMIT 1)
WHERE NOT EXISTS (SELECT 1 FROM pages WHERE slug = 'parents-day-out');

INSERT INTO pages (slug, title, body_html, meta_description, show_in_nav, is_published, updated_by)
SELECT 'youth', 'Youth', '<p><em>Draft — content to be added during migration.</em></p>', 'Youth ministry at Settle Memorial.', 0, 0,
       (SELECT id FROM users ORDER BY id LIMIT 1)
WHERE NOT EXISTS (SELECT 1 FROM pages WHERE slug = 'youth');

INSERT INTO pages (slug, title, body_html, meta_description, show_in_nav, is_published, updated_by)
SELECT 'adult-ministries', 'Adult Ministries', '<p><em>Draft — content to be added during migration.</em></p>', 'Adult ministries and small groups.', 0, 0,
       (SELECT id FROM users ORDER BY id LIMIT 1)
WHERE NOT EXISTS (SELECT 1 FROM pages WHERE slug = 'adult-ministries');

INSERT INTO pages (slug, title, body_html, meta_description, show_in_nav, is_published, updated_by)
SELECT 'the-roadrunners', 'The Roadrunners', '<p><em>Draft — content to be added during migration.</em></p>', 'The Roadrunners ministry.', 0, 0,
       (SELECT id FROM users ORDER BY id LIMIT 1)
WHERE NOT EXISTS (SELECT 1 FROM pages WHERE slug = 'the-roadrunners');

INSERT INTO pages (slug, title, body_html, meta_description, show_in_nav, is_published, updated_by)
SELECT 'missions', 'Missions', '<p><em>Draft — content to be added during migration.</em></p>', 'Mission and service opportunities.', 0, 0,
       (SELECT id FROM users ORDER BY id LIMIT 1)
WHERE NOT EXISTS (SELECT 1 FROM pages WHERE slug = 'missions');

INSERT INTO pages (slug, title, body_html, meta_description, show_in_nav, is_published, updated_by)
SELECT 'mission-partners', 'Mission Partners', '<p><em>Draft — content to be added during migration.</em></p>', 'Our mission partners.', 0, 0,
       (SELECT id FROM users ORDER BY id LIMIT 1)
WHERE NOT EXISTS (SELECT 1 FROM pages WHERE slug = 'mission-partners');

INSERT INTO pages (slug, title, body_html, meta_description, show_in_nav, is_published, updated_by)
SELECT 'mission-outreach', 'Mission Outreach', '<p><em>Draft — content to be added during migration.</em></p>', 'Mission outreach efforts.', 0, 0,
       (SELECT id FROM users ORDER BY id LIMIT 1)
WHERE NOT EXISTS (SELECT 1 FROM pages WHERE slug = 'mission-outreach');

INSERT INTO pages (slug, title, body_html, meta_description, show_in_nav, is_published, updated_by)
SELECT 'faith-promises', 'Give to Faith Promises', '<p><em>Draft — content to be added during migration.</em></p>', 'Give to Faith Promises.', 0, 0,
       (SELECT id FROM users ORDER BY id LIMIT 1)
WHERE NOT EXISTS (SELECT 1 FROM pages WHERE slug = 'faith-promises');

INSERT INTO pages (slug, title, body_html, meta_description, show_in_nav, is_published, updated_by)
SELECT 'newsletter', 'Newsletter', '<p><em>Draft — content to be added during migration.</em></p>', 'Read the latest church newsletter.', 0, 0,
       (SELECT id FROM users ORDER BY id LIMIT 1)
WHERE NOT EXISTS (SELECT 1 FROM pages WHERE slug = 'newsletter');

INSERT INTO pages (slug, title, body_html, meta_description, show_in_nav, is_published, updated_by)
SELECT 'watch', 'Watch', '<p><em>Draft — content to be added during migration.</em></p>', 'Watch our worship services online.', 0, 0,
       (SELECT id FROM users ORDER BY id LIMIT 1)
WHERE NOT EXISTS (SELECT 1 FROM pages WHERE slug = 'watch');

INSERT INTO pages (slug, title, body_html, meta_description, show_in_nav, is_published, updated_by)
SELECT 'give', 'Give', '<p><em>Draft — content to be added during migration.</em></p>', 'Ways to give to Settle Memorial.', 0, 0,
       (SELECT id FROM users ORDER BY id LIMIT 1)
WHERE NOT EXISTS (SELECT 1 FROM pages WHERE slug = 'give');

INSERT INTO pages (slug, title, body_html, meta_description, show_in_nav, is_published, updated_by)
SELECT 'employment', 'Employment', '<p><em>Draft — content to be added during migration.</em></p>', 'Employment opportunities at Settle Memorial.', 0, 0,
       (SELECT id FROM users ORDER BY id LIMIT 1)
WHERE NOT EXISTS (SELECT 1 FROM pages WHERE slug = 'employment');

-- -------------------------------------------------------------
-- 2. MENU ITEMS (additive to seed_menu.sql; idempotent)
-- -------------------------------------------------------------
-- New top-level items get high sort_order so they append after the
-- original Home/Staff/Connect/Prayer/Contact set. Reorder to taste in
-- /admin/menu. Parent lookups reuse seed_menu.sql's derived-table trick
-- (a wrapped subquery materializes first, dodging MySQL error 1093 on
-- self-referential INSERT ... SELECT).

-- 2a. New TOP-LEVEL items ------------------------------------------------
INSERT INTO menu_items (label, url, parent_id, sort_order, target, is_active)
SELECT 'I''m New', '/page/im-new', NULL, 60, '_self', 1
WHERE NOT EXISTS (SELECT 1 FROM menu_items WHERE label = 'I''m New' AND parent_id IS NULL);

INSERT INTO menu_items (label, url, parent_id, sort_order, target, is_active)
SELECT 'Worship', '/page/sundays', NULL, 70, '_self', 1
WHERE NOT EXISTS (SELECT 1 FROM menu_items WHERE label = 'Worship' AND parent_id IS NULL);

INSERT INTO menu_items (label, url, parent_id, sort_order, target, is_active)
SELECT 'Watch', '/page/watch', NULL, 80, '_self', 1
WHERE NOT EXISTS (SELECT 1 FROM menu_items WHERE label = 'Watch' AND parent_id IS NULL);

INSERT INTO menu_items (label, url, parent_id, sort_order, target, is_active)
SELECT 'Give', '/page/give', NULL, 90, '_self', 1
WHERE NOT EXISTS (SELECT 1 FROM menu_items WHERE label = 'Give' AND parent_id IS NULL);

INSERT INTO menu_items (label, url, parent_id, sort_order, target, is_active)
SELECT 'About', '/page/about', NULL, 100, '_self', 1
WHERE NOT EXISTS (SELECT 1 FROM menu_items WHERE label = 'About' AND parent_id IS NULL);

INSERT INTO menu_items (label, url, parent_id, sort_order, target, is_active)
SELECT 'Calendar', '/calendar', NULL, 110, '_self', 1
WHERE NOT EXISTS (SELECT 1 FROM menu_items WHERE label = 'Calendar' AND parent_id IS NULL);

-- 2b. Children of WORSHIP ------------------------------------------------
INSERT INTO menu_items (label, url, parent_id, sort_order, target, is_active)
SELECT 'Sermons', '/page/sermons',
       (SELECT id FROM (SELECT id FROM menu_items WHERE label = 'Worship' AND parent_id IS NULL LIMIT 1) AS w),
       10, '_self', 1
WHERE EXISTS (SELECT 1 FROM menu_items WHERE label = 'Worship' AND parent_id IS NULL)
  AND NOT EXISTS (SELECT 1 FROM menu_items WHERE label = 'Sermons'
        AND parent_id = (SELECT id FROM (SELECT id FROM menu_items WHERE label = 'Worship' AND parent_id IS NULL LIMIT 1) AS w2));

INSERT INTO menu_items (label, url, parent_id, sort_order, target, is_active)
SELECT 'Worship Bulletin', '/page/worship-bulletin',
       (SELECT id FROM (SELECT id FROM menu_items WHERE label = 'Worship' AND parent_id IS NULL LIMIT 1) AS w),
       20, '_self', 1
WHERE EXISTS (SELECT 1 FROM menu_items WHERE label = 'Worship' AND parent_id IS NULL)
  AND NOT EXISTS (SELECT 1 FROM menu_items WHERE label = 'Worship Bulletin'
        AND parent_id = (SELECT id FROM (SELECT id FROM menu_items WHERE label = 'Worship' AND parent_id IS NULL LIMIT 1) AS w2));

INSERT INTO menu_items (label, url, parent_id, sort_order, target, is_active)
SELECT 'Weekly Schedule', '/page/weekly-schedule',
       (SELECT id FROM (SELECT id FROM menu_items WHERE label = 'Worship' AND parent_id IS NULL LIMIT 1) AS w),
       30, '_self', 1
WHERE EXISTS (SELECT 1 FROM menu_items WHERE label = 'Worship' AND parent_id IS NULL)
  AND NOT EXISTS (SELECT 1 FROM menu_items WHERE label = 'Weekly Schedule'
        AND parent_id = (SELECT id FROM (SELECT id FROM menu_items WHERE label = 'Worship' AND parent_id IS NULL LIMIT 1) AS w2));

INSERT INTO menu_items (label, url, parent_id, sort_order, target, is_active)
SELECT 'Directions & Parking', '/page/directions-parking',
       (SELECT id FROM (SELECT id FROM menu_items WHERE label = 'Worship' AND parent_id IS NULL LIMIT 1) AS w),
       40, '_self', 1
WHERE EXISTS (SELECT 1 FROM menu_items WHERE label = 'Worship' AND parent_id IS NULL)
  AND NOT EXISTS (SELECT 1 FROM menu_items WHERE label = 'Directions & Parking'
        AND parent_id = (SELECT id FROM (SELECT id FROM menu_items WHERE label = 'Worship' AND parent_id IS NULL LIMIT 1) AS w2));

-- 2c. New children of CONNECT (reuses the Connect parent from seed_menu) --
INSERT INTO menu_items (label, url, parent_id, sort_order, target, is_active)
SELECT 'Children', '/page/children',
       (SELECT id FROM (SELECT id FROM menu_items WHERE label = 'Connect' AND parent_id IS NULL LIMIT 1) AS c),
       40, '_self', 1
WHERE EXISTS (SELECT 1 FROM menu_items WHERE label = 'Connect' AND parent_id IS NULL)
  AND NOT EXISTS (SELECT 1 FROM menu_items WHERE label = 'Children'
        AND parent_id = (SELECT id FROM (SELECT id FROM menu_items WHERE label = 'Connect' AND parent_id IS NULL LIMIT 1) AS c2));

INSERT INTO menu_items (label, url, parent_id, sort_order, target, is_active)
SELECT 'Settle Preschool', '/page/settle-preschool',
       (SELECT id FROM (SELECT id FROM menu_items WHERE label = 'Connect' AND parent_id IS NULL LIMIT 1) AS c),
       50, '_self', 1
WHERE EXISTS (SELECT 1 FROM menu_items WHERE label = 'Connect' AND parent_id IS NULL)
  AND NOT EXISTS (SELECT 1 FROM menu_items WHERE label = 'Settle Preschool'
        AND parent_id = (SELECT id FROM (SELECT id FROM menu_items WHERE label = 'Connect' AND parent_id IS NULL LIMIT 1) AS c2));

INSERT INTO menu_items (label, url, parent_id, sort_order, target, is_active)
SELECT 'Parent''s Day Out', '/page/parents-day-out',
       (SELECT id FROM (SELECT id FROM menu_items WHERE label = 'Connect' AND parent_id IS NULL LIMIT 1) AS c),
       60, '_self', 1
WHERE EXISTS (SELECT 1 FROM menu_items WHERE label = 'Connect' AND parent_id IS NULL)
  AND NOT EXISTS (SELECT 1 FROM menu_items WHERE label = 'Parent''s Day Out'
        AND parent_id = (SELECT id FROM (SELECT id FROM menu_items WHERE label = 'Connect' AND parent_id IS NULL LIMIT 1) AS c2));

INSERT INTO menu_items (label, url, parent_id, sort_order, target, is_active)
SELECT 'Youth', '/page/youth',
       (SELECT id FROM (SELECT id FROM menu_items WHERE label = 'Connect' AND parent_id IS NULL LIMIT 1) AS c),
       70, '_self', 1
WHERE EXISTS (SELECT 1 FROM menu_items WHERE label = 'Connect' AND parent_id IS NULL)
  AND NOT EXISTS (SELECT 1 FROM menu_items WHERE label = 'Youth'
        AND parent_id = (SELECT id FROM (SELECT id FROM menu_items WHERE label = 'Connect' AND parent_id IS NULL LIMIT 1) AS c2));

INSERT INTO menu_items (label, url, parent_id, sort_order, target, is_active)
SELECT 'Adult Ministries', '/page/adult-ministries',
       (SELECT id FROM (SELECT id FROM menu_items WHERE label = 'Connect' AND parent_id IS NULL LIMIT 1) AS c),
       80, '_self', 1
WHERE EXISTS (SELECT 1 FROM menu_items WHERE label = 'Connect' AND parent_id IS NULL)
  AND NOT EXISTS (SELECT 1 FROM menu_items WHERE label = 'Adult Ministries'
        AND parent_id = (SELECT id FROM (SELECT id FROM menu_items WHERE label = 'Connect' AND parent_id IS NULL LIMIT 1) AS c2));

INSERT INTO menu_items (label, url, parent_id, sort_order, target, is_active)
SELECT 'Missions', '/page/missions',
       (SELECT id FROM (SELECT id FROM menu_items WHERE label = 'Connect' AND parent_id IS NULL LIMIT 1) AS c),
       90, '_self', 1
WHERE EXISTS (SELECT 1 FROM menu_items WHERE label = 'Connect' AND parent_id IS NULL)
  AND NOT EXISTS (SELECT 1 FROM menu_items WHERE label = 'Missions'
        AND parent_id = (SELECT id FROM (SELECT id FROM menu_items WHERE label = 'Connect' AND parent_id IS NULL LIMIT 1) AS c2));

INSERT INTO menu_items (label, url, parent_id, sort_order, target, is_active)
SELECT 'Newsletter', '/page/newsletter',
       (SELECT id FROM (SELECT id FROM menu_items WHERE label = 'Connect' AND parent_id IS NULL LIMIT 1) AS c),
       100, '_self', 1
WHERE EXISTS (SELECT 1 FROM menu_items WHERE label = 'Connect' AND parent_id IS NULL)
  AND NOT EXISTS (SELECT 1 FROM menu_items WHERE label = 'Newsletter'
        AND parent_id = (SELECT id FROM (SELECT id FROM menu_items WHERE label = 'Connect' AND parent_id IS NULL LIMIT 1) AS c2));

-- 2d. THIRD TIER --------------------------------------------------------
-- The Roadrunners under Adult Ministries (which is itself under Connect).
INSERT INTO menu_items (label, url, parent_id, sort_order, target, is_active)
SELECT 'The Roadrunners', '/page/the-roadrunners',
       (SELECT id FROM (SELECT id FROM menu_items WHERE label = 'Adult Ministries'
           AND parent_id = (SELECT id FROM (SELECT id FROM menu_items WHERE label = 'Connect' AND parent_id IS NULL LIMIT 1) AS c1) LIMIT 1) AS am),
       10, '_self', 1
WHERE EXISTS (SELECT 1 FROM menu_items WHERE label = 'Adult Ministries'
        AND parent_id = (SELECT id FROM (SELECT id FROM menu_items WHERE label = 'Connect' AND parent_id IS NULL LIMIT 1) AS c3))
  AND NOT EXISTS (SELECT 1 FROM menu_items WHERE label = 'The Roadrunners'
        AND parent_id = (SELECT id FROM (SELECT id FROM menu_items WHERE label = 'Adult Ministries'
           AND parent_id = (SELECT id FROM (SELECT id FROM menu_items WHERE label = 'Connect' AND parent_id IS NULL LIMIT 1) AS c2) LIMIT 1) AS am2));

-- Mission Partners / Outreach / Faith Promises under Missions.
INSERT INTO menu_items (label, url, parent_id, sort_order, target, is_active)
SELECT 'Mission Partners', '/page/mission-partners',
       (SELECT id FROM (SELECT id FROM menu_items WHERE label = 'Missions'
           AND parent_id = (SELECT id FROM (SELECT id FROM menu_items WHERE label = 'Connect' AND parent_id IS NULL LIMIT 1) AS c1) LIMIT 1) AS mi),
       10, '_self', 1
WHERE EXISTS (SELECT 1 FROM menu_items WHERE label = 'Missions'
        AND parent_id = (SELECT id FROM (SELECT id FROM menu_items WHERE label = 'Connect' AND parent_id IS NULL LIMIT 1) AS c3))
  AND NOT EXISTS (SELECT 1 FROM menu_items WHERE label = 'Mission Partners'
        AND parent_id = (SELECT id FROM (SELECT id FROM menu_items WHERE label = 'Missions'
           AND parent_id = (SELECT id FROM (SELECT id FROM menu_items WHERE label = 'Connect' AND parent_id IS NULL LIMIT 1) AS c2) LIMIT 1) AS mi2));

INSERT INTO menu_items (label, url, parent_id, sort_order, target, is_active)
SELECT 'Mission Outreach', '/page/mission-outreach',
       (SELECT id FROM (SELECT id FROM menu_items WHERE label = 'Missions'
           AND parent_id = (SELECT id FROM (SELECT id FROM menu_items WHERE label = 'Connect' AND parent_id IS NULL LIMIT 1) AS c1) LIMIT 1) AS mi),
       20, '_self', 1
WHERE EXISTS (SELECT 1 FROM menu_items WHERE label = 'Missions'
        AND parent_id = (SELECT id FROM (SELECT id FROM menu_items WHERE label = 'Connect' AND parent_id IS NULL LIMIT 1) AS c3))
  AND NOT EXISTS (SELECT 1 FROM menu_items WHERE label = 'Mission Outreach'
        AND parent_id = (SELECT id FROM (SELECT id FROM menu_items WHERE label = 'Missions'
           AND parent_id = (SELECT id FROM (SELECT id FROM menu_items WHERE label = 'Connect' AND parent_id IS NULL LIMIT 1) AS c2) LIMIT 1) AS mi2));

INSERT INTO menu_items (label, url, parent_id, sort_order, target, is_active)
SELECT 'Give to Faith Promises', '/page/faith-promises',
       (SELECT id FROM (SELECT id FROM menu_items WHERE label = 'Missions'
           AND parent_id = (SELECT id FROM (SELECT id FROM menu_items WHERE label = 'Connect' AND parent_id IS NULL LIMIT 1) AS c1) LIMIT 1) AS mi),
       30, '_self', 1
WHERE EXISTS (SELECT 1 FROM menu_items WHERE label = 'Missions'
        AND parent_id = (SELECT id FROM (SELECT id FROM menu_items WHERE label = 'Connect' AND parent_id IS NULL LIMIT 1) AS c3))
  AND NOT EXISTS (SELECT 1 FROM menu_items WHERE label = 'Give to Faith Promises'
        AND parent_id = (SELECT id FROM (SELECT id FROM menu_items WHERE label = 'Missions'
           AND parent_id = (SELECT id FROM (SELECT id FROM menu_items WHERE label = 'Connect' AND parent_id IS NULL LIMIT 1) AS c2) LIMIT 1) AS mi2));

-- End of seed_pages.sql
