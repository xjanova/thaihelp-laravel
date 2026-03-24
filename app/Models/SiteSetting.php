<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SiteSetting extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'setting_key',
        'setting_value',
        'setting_group',
    ];

    protected static function booted(): void
    {
        static::creating(function (SiteSetting $model) {
            $model->updated_at = now();
        });

        static::updating(function (SiteSetting $model) {
            $model->updated_at = now();
        });
    }

    /** In-memory cache for the current request (avoids repeated Cache::get calls) */
    protected static ?array $memCache = null;

    /** Cache TTL in seconds (shared across all PHP processes via Redis/file cache) */
    private const CACHE_TTL = 120; // 2 minutes
    private const CACHE_KEY = 'site_settings_all';

    /**
     * Get a setting value — cached cross-process via Laravel Cache.
     * DB is only hit once every CACHE_TTL seconds, not every request.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        if (static::$memCache === null) {
            try {
                static::$memCache = Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
                    return static::pluck('setting_value', 'setting_key')->toArray();
                });
            } catch (\Exception $e) {
                // DB not ready (migration running, etc.)
                static::$memCache = [];
            }
        }
        return static::$memCache[$key] ?? $default;
    }

    /**
     * Set a setting value — busts both in-memory and shared cache.
     */
    public static function set(string $key, mixed $value, string $group = 'general'): void
    {
        static::updateOrCreate(
            ['setting_key' => $key],
            ['setting_value' => $value, 'setting_group' => $group]
        );
        static::clearCache();
    }

    /**
     * Clear all caches (call after bulk updates).
     */
    public static function clearCache(): void
    {
        static::$memCache = null;
        Cache::forget(self::CACHE_KEY);
    }
}
