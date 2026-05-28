<?php
declare(strict_types=1);

namespace Settle;

/**
 * Settings reader for the key/value `settings` table.
 *
 * Templates and controllers call this rather than hitting the database
 * directly. The settings table is small (~30 rows for Settle, similar
 * for Trinity) so the entire table is loaded once per request and
 * served from a static cache for the rest of the request.
 *
 * See PROJECT_HANDOFF.md §3.7 and §9 (no hardcoded church identity).
 *
 * Reading:
 *   Settings::all()                   — full [key => value] array
 *   Settings::get('church_name', '')  — one value with optional default
 *
 * Writing (rare; typically only the eventual /admin/settings screen):
 *   Settings::put('church_name', 'Trinity UMC', $userId);
 *
 * Settings are PER-CHURCH data. They live in this church's database.
 * Anything that needs to be the SAME across all churches lives in
 * config.php instead (e.g. feature flags, DB credentials).
 */
final class Settings
{
    /** @var array<string, string>|null */
    private static ?array $cache = null;

    /**
     * Return the entire settings table as [key => value].
     *
     * @return array<string, string>
     */
    public static function all(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }

        $stmt = Database::query("SELECT setting_key, setting_value FROM settings");
        $out = [];
        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $out[(string) $row['setting_key']] = (string) ($row['setting_value'] ?? '');
        }
        self::$cache = $out;
        return $out;
    }

    /**
     * Return one setting value, or $default if the key is not set
     * or its stored value is the empty string.
     *
     * Returning $default on empty string matters because the seed
     * stores '' for genuinely-blank settings (rather than NULL).
     * Templates expect "give me something usable or fall back."
     */
    public static function get(string $key, ?string $default = null): ?string
    {
        $all = self::all();
        if (!array_key_exists($key, $all) || $all[$key] === '') {
            return $default;
        }
        return $all[$key];
    }

    /**
     * Write or upsert a setting.
     *
     * Used by the eventual admin settings screen. Bypasses the
     * static cache, then clears it so subsequent get() calls in the
     * same request reflect the new value.
     *
     * Note: the settings table does not have an updated_by column
     * (it's a flat key/value table). If audit-trailing settings
     * changes becomes important, that's a small schema migration
     * and is best done at the same time the admin UI is built.
     */
    public static function put(string $key, string $value): void
    {
        Database::query(
            "INSERT INTO settings (setting_key, setting_value)
             VALUES (:k, :v)
             ON DUPLICATE KEY UPDATE setting_value = :v_update",
            [
                ':k'        => $key,
                ':v'        => $value,
                ':v_update' => $value,
            ]
        );
        self::$cache = null;  // force reload on next get/all
    }

    /**
     * Forget the in-memory cache. Useful in test scaffolding or
     * after a known external mutation.
     */
    public static function flush(): void
    {
        self::$cache = null;
    }
}
