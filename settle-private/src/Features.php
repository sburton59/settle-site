<?php
declare(strict_types=1);

namespace Settle;

/**
 * Feature flag registry.
 *
 * Reads the 'features' array from $GLOBALS['settle_config'] (populated by
 * bootstrap.php from settle-private/config/config.php) and exposes a single
 * enabled() check.
 *
 * Conventions (see PROJECT_HANDOFF.md §9 and §14.4):
 *   - Three call sites consult this: route registration, sidebar nav,
 *     and the menu URL picker (when the menu system ships).
 *   - For Settle, every flag is true. The plumbing must exist anyway so
 *     the eventual multi-church split is mechanical, not a retrofit.
 *   - This is intentionally a file-based, NOT a database-based, toggle.
 *     Enabling or disabling a feature is a deploy, not an admin-UI button —
 *     because enablement decisions (e.g. exposing finances) are policy,
 *     not operational settings.
 *   - Unknown keys default to TRUE (fail-open). Once every feature is
 *     declared in config.example.php and propagated to live config files,
 *     the unknown-key path stops being exercised in practice.
 *
 * Example use:
 *   if (\Settle\Features::enabled('blog')) {
 *       $router->get('/blog', [BlogController::class, 'index']);
 *   }
 */
final class Features
{
    /**
     * @return bool True if the feature is enabled (or not declared).
     */
    public static function enabled(string $key): bool
    {
        $features = $GLOBALS['settle_config']['features'] ?? null;

        if (!is_array($features)) {
            // No features array configured at all — fail-open during the
            // transition. Once every config has the array, this branch
            // becomes dead code (which is fine; defensive is correct).
            return true;
        }

        if (!array_key_exists($key, $features)) {
            // Unknown key — fail-open. Lets new features ship without
            // requiring every existing config to be updated first.
            return true;
        }

        return (bool) $features[$key];
    }

    /**
     * Return every declared feature flag as a [key => bool] map.
     *
     * Useful for the eventual /admin/settings screen and for the menu
     * URL picker, which needs to know which feature-provided URLs to
     * offer as link targets.
     *
     * @return array<string, bool>
     */
    public static function all(): array
    {
        $features = $GLOBALS['settle_config']['features'] ?? [];
        if (!is_array($features)) {
            return [];
        }
        $out = [];
        foreach ($features as $k => $v) {
            $out[(string) $k] = (bool) $v;
        }
        return $out;
    }
}
