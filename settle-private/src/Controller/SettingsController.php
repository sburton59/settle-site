<?php
declare(strict_types=1);

namespace Settle\Controller;

use Settle\Auth;
use Settle\AuditLog;
use Settle\Settings;

/**
 * Admin Settings — church identity, contact, email-notification routing,
 * worship times, social/app links, homepage copy, SEO meta, and branding
 * (logo/favicon via the Media Library + brand colors).
 *
 * Admin-only. Settings are global per-church key/value rows, so there is
 * no per-record ownership — a plain admin role gate (route-level AND a
 * defense-in-depth in-code check here) is the whole access story.
 *
 * Persistence reuses \Settle\Settings::put() (upsert + per-request cache
 * flush); there is NO settings schema change. Brand IMAGE settings
 * (logo/favicon/apple icon) are URLs the public layout already reads, so
 * they apply as soon as they are saved. Brand COLOR settings
 * (brand_primary, brand_ink) are persisted here and applied by an inline
 * <style> override emitted from the public layout (Phase 2); until that
 * ships they save fine but do not yet change the public CSS.
 *
 * The form is PREFILLED with each setting's current value, falling back
 * to the field's default (mirroring seed_settings.sql) when the stored
 * value is blank — so the admin always sees the current-or-default state,
 * even on a partially-seeded database.
 *
 * Validation is server-side and ATOMIC: if any field is invalid, NOTHING
 * is written and the form re-renders with per-field errors and the values
 * the user typed. Colors are validated against a strict 6-digit hex
 * pattern here AND again at emit time in the layout (defense in depth — a
 * value set via raw SQL still can't break or inject CSS).
 *
 * See PROJECT_HANDOFF.md §15 (theming/branding plan), §9 (conventions),
 * §3.5 (security baseline).
 */
final class SettingsController extends BaseController
{
    /**
     * Editable settings, grouped for the form. Order here IS the display
     * order. This is the single source of truth shared by edit() (prefill),
     * update() (which keys to read + how to validate), and the template
     * (rendering). Only keys listed here can be written — anything else
     * posted is ignored, so the form can never set an arbitrary key.
     *
     * Field 'type': text | textarea | email | url | media | color
     *   'max'     — max characters (validated server-side)
     *   'short'   — render a narrow input (phone, city, state, zip)
     *   'help'    — hint text under the label
     *   'default' — value shown when the stored setting is blank. These
     *               mirror seed_settings.sql / seed_settings_mail.sql so
     *               the form reflects the seeded defaults on a fresh or
     *               partially-seeded install. (For colors, this is also
     *               the swatch fallback and the theme.css default.)
     *
     * @return array<int, array<string, mixed>>
     */
    private static function groups(): array
    {
        return [
            ['title' => 'Identity',
             'intro' => 'The church name and tagline shown across the site.',
             'fields' => [
                ['key' => 'church_name',       'label' => 'Full church name', 'type' => 'text', 'max' => 190,
                 'default' => 'Settle Memorial United Methodist Church'],
                ['key' => 'church_short_name', 'label' => 'Short name',       'type' => 'text', 'max' => 120,
                 'default' => 'Settle Memorial UMC',
                 'help' => 'Used where space is tight, e.g. the header when no logo is set.'],
                ['key' => 'church_tagline',    'label' => 'Tagline',          'type' => 'text', 'max' => 190,
                 'default' => 'Owensboro, Kentucky'],
            ]],

            ['title' => 'Contact',
             'fields' => [
                ['key' => 'church_phone',         'label' => 'Phone',           'type' => 'text',  'max' => 50,  'short' => true,
                 'default' => '(270) 684-4226'],
                ['key' => 'church_office_email',  'label' => 'Office email',    'type' => 'email', 'max' => 190,
                 'default' => ''],
                ['key' => 'church_office_hours',  'label' => 'Office hours',    'type' => 'text',  'max' => 190,
                 'default' => 'Tuesday – Thursday, 8:30 a.m. – 3:00 p.m.'],
                ['key' => 'church_mailing',       'label' => 'Mailing address', 'type' => 'text',  'max' => 190,
                 'default' => 'P.O. Box 1756, Owensboro, KY 42302'],
                ['key' => 'church_address_line1', 'label' => 'Street address',  'type' => 'text',  'max' => 150,
                 'default' => '202 E. 4th Street'],
                ['key' => 'church_address_city',  'label' => 'City',            'type' => 'text',  'max' => 100, 'short' => true,
                 'default' => 'Owensboro'],
                ['key' => 'church_address_state', 'label' => 'State',           'type' => 'text',  'max' => 20,  'short' => true,
                 'default' => 'KY'],
                ['key' => 'church_address_zip',   'label' => 'ZIP',             'type' => 'text',  'max' => 20,  'short' => true,
                 'default' => '42303'],
            ]],

            ['title' => 'Email notifications',
             'intro' => 'Where website form submissions are sent. Changes take effect immediately — no deploy needed.',
             'fields' => [
                ['key' => 'contact_notify_to', 'label' => 'Contact form goes to',  'type' => 'email', 'max' => 190,
                 'default' => 'office@settlemem.org'],
                ['key' => 'prayer_notify_to',  'label' => 'Prayer requests go to', 'type' => 'email', 'max' => 190,
                 'default' => 'prayer@settlemem.org'],
            ]],

            ['title' => 'Worship times',
             'fields' => [
                ['key' => 'worship_traditional',   'label' => 'Traditional',   'type' => 'text', 'max' => 190,
                 'default' => 'Traditional Worship — 10:00 a.m.'],
                ['key' => 'worship_contemporary',  'label' => 'Contemporary',  'type' => 'text', 'max' => 190,
                 'default' => 'Contemporary Worship (SHOUT!) — 10:30 a.m.'],
                ['key' => 'worship_sunday_school', 'label' => 'Sunday school', 'type' => 'text', 'max' => 190,
                 'default' => 'Sunday School — 9:00 a.m.'],
            ]],

            ['title' => 'Social & apps',
             'intro' => 'Full web addresses. Leave a field blank to hide that link.',
             'fields' => [
                ['key' => 'social_facebook',  'label' => 'Facebook',    'type' => 'url', 'max' => 500,
                 'default' => 'https://www.facebook.com/SettleMem'],
                ['key' => 'social_instagram', 'label' => 'Instagram',   'type' => 'url', 'max' => 500,
                 'default' => 'https://www.instagram.com/shoutatsettle/'],
                ['key' => 'social_youtube',   'label' => 'YouTube',     'type' => 'url', 'max' => 500,
                 'default' => 'https://www.youtube.com/@settlememorialunitedmethod5839'],
                ['key' => 'app_ios_url',      'label' => 'iOS app',     'type' => 'url', 'max' => 500,
                 'default' => 'https://apps.apple.com/app/settle-umc/id1639009037'],
                ['key' => 'app_android_url',  'label' => 'Android app', 'type' => 'url', 'max' => 500,
                 'default' => 'https://play.google.com/store/apps/details?id=com.redpixelstudios.settleumc'],
            ]],

            ['title' => 'Homepage',
             'fields' => [
                ['key' => 'homepage_welcome_heading', 'label' => 'Welcome heading',   'type' => 'text',     'max' => 150,
                 'default' => 'Welcome home'],
                ['key' => 'homepage_welcome_lead',    'label' => 'Welcome lead text', 'type' => 'textarea', 'max' => 500,
                 'default' => ''],
            ]],

            ['title' => 'Meta / SEO',
             'fields' => [
                ['key' => 'meta_description',      'label' => 'Meta description', 'type' => 'textarea', 'max' => 300,
                 'help' => 'Shown by search engines and in link previews.',
                 'default' => 'Settle Memorial United Methodist Church, Owensboro, Kentucky — a faith journey where you can connect with new friends, learn more about Jesus, and experience His transforming love and grace.'],
                ['key' => 'meta_copyright_holder', 'label' => 'Copyright holder', 'type' => 'text',     'max' => 190,
                 'default' => 'Settle Memorial United Methodist Church'],
            ]],

            ['title' => 'Branding',
             'intro' => 'Logo and icons come from the Media Library. Brand colors apply across the public site; leave a color blank to use the built-in theme default.',
             'fields' => [
                ['key' => 'brand_logo_url',       'label' => 'Logo',                       'type' => 'media', 'max' => 500,
                 'default' => 'https://settleumc.com/wp-content/uploads/Settle-UMC-Logo.png'],
                ['key' => 'brand_favicon_url',    'label' => 'Favicon (32×32)',            'type' => 'media', 'max' => 500,
                 'default' => 'https://settleumc.com/wp-content/uploads/cropped-Favicon-32x32.png'],
                ['key' => 'brand_apple_icon_url', 'label' => 'Apple touch icon (180×180)', 'type' => 'media', 'max' => 500,
                 'default' => 'https://settleumc.com/wp-content/uploads/cropped-Favicon-180x180.png'],
                ['key' => 'brand_primary',        'label' => 'Primary color',              'type' => 'color', 'max' => 7,
                 'default' => '#9E2A2B', 'help' => 'Header, nav, and accents. Theme default #9E2A2B.'],
                ['key' => 'brand_ink',            'label' => 'Ink color',                  'type' => 'color', 'max' => 7,
                 'default' => '#2C2C2E', 'help' => 'Dark text and shield tone. Theme default #2C2C2E.'],
            ]],
        ];
    }

    /**
     * Flatten groups() to [key => fieldSpec].
     *
     * @return array<string, array<string, mixed>>
     */
    private static function flatFields(): array
    {
        $out = [];
        foreach (self::groups() as $group) {
            foreach ($group['fields'] as $f) {
                $out[$f['key']] = $f;
            }
        }
        return $out;
    }

    /**
     * Build the form's prefill data: each setting's current value, or its
     * schema default when the stored value is blank/absent. Keeps the UI
     * meaningful on fresh or partially-seeded installs and surfaces the
     * brand-color defaults before the Phase 2 seed runs.
     *
     * @return array<string, string>
     */
    private static function prefillData(): array
    {
        $current = Settings::all();
        $out = [];
        foreach (self::flatFields() as $key => $spec) {
            $cur = $current[$key] ?? '';
            $out[$key] = $cur !== '' ? $cur : (string) ($spec['default'] ?? '');
        }
        return $out;
    }

    /**
     * GET /admin/settings — show the grouped form, prefilled.
     */
    public function edit(): void
    {
        if (!Auth::hasRole('admin')) {
            http_response_code(403);
            echo 'Forbidden.';
            return;
        }

        // NOTE: the values key must NOT be named 'data' — View::render's
        // own parameter is $data, and its extract(..., EXTR_SKIP) would
        // then skip our key, leaving the template's values undefined.
        $this->render('admin/settings/edit', [
            'groups' => self::groups(),
            'values' => self::prefillData(),
            'errors' => [],
        ]);
    }

    /**
     * POST /admin/settings — validate, persist changed keys, audit.
     * Atomic: any validation error writes nothing.
     */
    public function update(): void
    {
        if (!Auth::hasRole('admin')) {
            http_response_code(403);
            echo 'Forbidden.';
            return;
        }

        $fields = self::flatFields();

        // Read every known field — and ONLY known fields.
        $posted = [];
        foreach ($fields as $key => $spec) {
            $posted[$key] = trim((string) $this->input($key, ''));
        }

        $errors = $this->validate($posted, $fields);
        if ($errors) {
            $this->render('admin/settings/edit', [
                'groups' => self::groups(),
                // Echo back exactly what the user typed so they can fix it.
                'values' => $posted,
                'errors' => $errors,
            ]);
            return;
        }

        $current = Settings::all();
        $changed = [];
        foreach ($posted as $key => $val) {
            if (($current[$key] ?? '') !== $val) {
                Settings::put($key, $val);
                $changed[] = $key;
            }
        }

        if ($changed) {
            AuditLog::record('settings.update', 'settings', null, ['changed' => $changed]);
            $n = count($changed);
            $this->flash('success', $n . ' setting' . ($n === 1 ? '' : 's') . ' updated.');
        } else {
            $this->flash('info', 'No changes to save.');
        }

        $this->redirect('/admin/settings');
    }

    /**
     * Validate posted values against the field schema. Blank is allowed for
     * every field (clears the value / falls back to a default). Returns
     * [key => message]; an empty array means OK.
     *
     * @param array<string, string>              $posted
     * @param array<string, array<string,mixed>> $fields
     * @return array<string, string>
     */
    private function validate(array $posted, array $fields): array
    {
        $errors = [];

        foreach ($fields as $key => $spec) {
            $val = $posted[$key] ?? '';
            $max = (int) ($spec['max'] ?? 255);

            if ($val !== '' && mb_strlen($val) > $max) {
                $errors[$key] = $spec['label'] . ' must be ' . $max . ' characters or fewer.';
                continue;
            }

            if ($val === '') {
                continue; // blank is always allowed
            }

            switch ($spec['type']) {
                case 'email':
                    if (!filter_var($val, FILTER_VALIDATE_EMAIL)) {
                        $errors[$key] = 'Enter a valid email address.';
                    }
                    break;

                case 'url':
                    // External links must be absolute http(s) URLs.
                    if (!filter_var($val, FILTER_VALIDATE_URL) || !preg_match('#^https?://#i', $val)) {
                        $errors[$key] = 'Enter a full web address starting with http:// or https://.';
                    }
                    break;

                case 'media':
                    // Media Library images are served as ROOT-RELATIVE paths
                    // (e.g. /uploads/logo.png); existing brand assets are
                    // absolute http(s) URLs. Accept either, but reject
                    // protocol-relative ('//host') and anything that isn't a
                    // plain path or an http(s) URL (so no javascript:/data:).
                    $isRootRelative = ($val[0] === '/' && !str_starts_with($val, '//'));
                    $isHttpUrl      = (bool) preg_match('#^https?://#i', $val)
                                      && filter_var($val, FILTER_VALIDATE_URL);
                    if (!$isRootRelative && !$isHttpUrl) {
                        $errors[$key] = 'Choose an image from the Media Library, or enter a full https:// address.';
                    }
                    break;

                case 'color':
                    if (!preg_match('/^#[0-9a-fA-F]{6}$/', $val)) {
                        $errors[$key] = 'Use a 6-digit hex color, e.g. #9E2A2B.';
                    }
                    break;

                // text / textarea: length already checked above
            }
        }

        return $errors;
    }
}
