<?php
declare(strict_types=1);

/** Key/value site settings with a per-request cache. */
class Setting
{
    private static ?array $cache = null;

    private static function load(): array
    {
        if (self::$cache === null) {
            self::$cache = [];
            foreach (db_all('SELECT setting_key, setting_value FROM settings') as $row) {
                self::$cache[$row['setting_key']] = $row['setting_value'];
            }
        }
        return self::$cache;
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        return self::load()[$key] ?? $default;
    }

    public static function all(): array
    {
        return self::load();
    }

    public static function set(string $key, ?string $value): void
    {
        db_run(
            'INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)',
            [$key, $value]
        );
        self::$cache = null; // invalidate
    }
}
