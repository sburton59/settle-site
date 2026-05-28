<?php
declare(strict_types=1);

namespace Settle;

/**
 * Public-side view helper.
 *
 * Wraps Settle\View::render() with the data every public template
 * needs in scope: the site settings (church name, address, social
 * URLs, etc.) and the public menu tree. Each public-rendering call
 * site (PublicController, PrayerRequestController::renderPublic,
 * ContactMessageController::renderPublic) goes through this helper
 * instead of View::render() directly.
 *
 * Why a separate helper rather than retrofitting BaseController:
 * the prayer and contact controllers deliberately bypass
 * BaseController::render() because the base defaults to the admin
 * layout. Asking them to route back through BaseController would
 * mean either changing the default layout (risky) or adding more
 * conditional logic in render() (worse). A small dedicated helper
 * is cleaner.
 *
 * Templates rendered through this helper can rely on:
 *   $settings   — the full settings table as a [key => value] map
 *   $menu_tree  — nested array of active menu items
 *   $page_title — optional, computed from settings.church_name if absent
 *   $e          — htmlspecialchars closure (from View::render)
 *
 * See PROJECT_HANDOFF.md §9 (no hardcoded church identity) and §14.5
 * (data-driven public navigation).
 */
final class PublicView
{
    /**
     * Render a public template wrapped in the public layout, with
     * settings and menu data merged into the template scope.
     *
     * @param string                $template Template path under templates/
     *                                        (e.g. 'public/home')
     * @param array<string, mixed>  $data     Caller-supplied template data
     */
    public static function render(string $template, array $data = []): void
    {
        // Global-to-every-public-page data. Caller data wins for keys
        // that overlap (which should be rare — the public_* keys are
        // namespaced).
        $publicData = [
            'settings'  => Settings::all(),
            'menu_tree' => Menu::renderTree(),
        ];

        // Resolve a page title if the caller didn't set one. Templates
        // can override per-page by passing 'page_title' explicitly.
        if (!isset($data['page_title'])) {
            $churchName = Settings::get('church_name', 'Church');
            $publicData['page_title'] = $churchName;
        }

        View::render($template, array_merge($publicData, $data), 'public');
    }
}
