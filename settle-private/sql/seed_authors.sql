-- =============================================================
-- seed_authors.sql — blog author accounts (roadmap #3)
--
-- Settle Memorial UMC
--
-- *** THIS IS A TEMPLATE — EDIT BEFORE RUNNING. ***
-- Each row needs a real email and a real Argon2id password hash. The
-- placeholders below (REPLACE_EMAIL, REPLACE_HASH) are deliberately
-- invalid so an unedited file fails loudly instead of seeding garbage.
--
-- HOW TO MAKE A PASSWORD HASH (run on the server, one per person):
--   php -r 'echo password_hash("the-temporary-password", PASSWORD_ARGON2ID), "\n";'
-- Copy the printed string (it starts with $argon2id$) into that person's
-- password_hash below. Give each author a unique temporary password and
-- have them change it after first login.
--
-- Roles: these are seeded as 'author' — they can write/edit/publish their
-- OWN posts and upload to the media library, but cannot manage other
-- people's posts or the category list (that's editor+). The role ladder is
-- author < editor < admin (higher roles inherit lower abilities).
--
-- usernames below are suggestions; change them if you prefer. username and
-- email must each be unique across the users table.
--
-- Safe to re-run: INSERT IGNORE skips any row whose username/email already
-- exists, so running this twice will not create duplicates.
-- =============================================================

SET NAMES utf8mb4;

-- Aimee Keith — Children's Ministry and Parent's Day Out Director
INSERT IGNORE INTO users (username, email, password_hash, display_name, role, is_active)
VALUES ('aimee.keith',    'REPLACE_EMAIL', 'REPLACE_HASH', 'Aimee Keith',    'author', 1);

-- Rebecca Volk — Music Director
INSERT IGNORE INTO users (username, email, password_hash, display_name, role, is_active)
VALUES ('rebecca.volk',   'REPLACE_EMAIL', 'REPLACE_HASH', 'Rebecca Volk',   'author', 1);

-- Chris Tolliver — Church Organist
INSERT IGNORE INTO users (username, email, password_hash, display_name, role, is_active)
VALUES ('chris.tolliver', 'REPLACE_EMAIL', 'REPLACE_HASH', 'Chris Tolliver', 'author', 1);

-- Jeff Keeley — Middle School Youth
INSERT IGNORE INTO users (username, email, password_hash, display_name, role, is_active)
VALUES ('jeff.keeley',    'REPLACE_EMAIL', 'REPLACE_HASH', 'Jeff Keeley',    'author', 1);

-- Wesley Marcum — Senior High Youth and Young Adults
INSERT IGNORE INTO users (username, email, password_hash, display_name, role, is_active)
VALUES ('wesley.marcum',  'REPLACE_EMAIL', 'REPLACE_HASH', 'Wesley Marcum',  'author', 1);

-- Lori Roach — Church Secretary
INSERT IGNORE INTO users (username, email, password_hash, display_name, role, is_active)
VALUES ('lori.roach',     'REPLACE_EMAIL', 'REPLACE_HASH', 'Lori Roach',     'author', 1);

-- -------------------------------------------------------------------
-- Mark Dickinson (Pastor) and Alecia Meyer (Church Administrator) are
-- the assumed INITIAL ADMINS (PROJECT_HANDOFF.md §11 #6). Admins already
-- have authoring rights, so they do NOT need author rows here — adding
-- them would create duplicate accounts. Only uncomment the lines below if
-- you specifically want them as plain 'author' accounts instead of admins.
-- -------------------------------------------------------------------
-- INSERT IGNORE INTO users (username, email, password_hash, display_name, role, is_active)
-- VALUES ('mark.dickinson', 'REPLACE_EMAIL', 'REPLACE_HASH', 'Mark Dickinson', 'author', 1);
-- INSERT IGNORE INTO users (username, email, password_hash, display_name, role, is_active)
-- VALUES ('alecia.meyer',   'REPLACE_EMAIL', 'REPLACE_HASH', 'Alecia Meyer',   'author', 1);
