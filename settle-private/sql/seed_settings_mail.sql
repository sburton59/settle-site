-- =====================================================================
--  seed_settings_mail.sql
--  Mail routing addresses for the contact form and prayer team.
--  Added for roadmap #6 (Email sending).
-- =====================================================================
--
--  These are PER-CHURCH operational values, stored in `settings` so they
--  can be changed later without a code deploy (and via the eventual
--  /admin/settings screen).
--
--  INSERT IGNORE means running this more than once — or against a DB that
--  already has these keys — is harmless and will NOT overwrite a value
--  you've already customized. To CHANGE an existing value, edit it in
--  phpMyAdmin (or use \Settle\Settings::put()).
--
--  ⚠  Replace the placeholder addresses below with the church's real
--     destination inboxes before (or right after) import.
--
--  Run order: any time after schema.sql + seed_settings.sql.
-- ---------------------------------------------------------------------

INSERT IGNORE INTO settings (setting_key, setting_value) VALUES
    ('contact_notify_to', 'office@settlemem.org'),   -- contact-form forwards land here
    ('prayer_notify_to',  'prayer@settlemem.org');    -- prayer-team alerts land here
