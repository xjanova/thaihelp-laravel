<?php

namespace App\Services;

use App\Models\SiteSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * API Key Pool - Round-robin key rotation for any API service.
 *
 * Stores multiple API keys per service, tracks usage/errors,
 * auto-skips keys that hit rate limits, and rotates evenly.
 *
 * Keys stored in site_settings as JSON array:
 *   api_pool_groq = [{"key":"gsk_xxx","label":"Account 1","enabled":true}, ...]
 *   api_pool_google_tts = [{"key":"AIza...","label":"Main","enabled":true}, ...]
 *   api_pool_google_maps = [{"key":"AIza...","label":"Project 1","enabled":true}, ...]
 */
class ApiKeyPool
{
    /**
     * Get the next available API key for a service (round-robin).
     */
    public static function getKey(string $service, ?string $fallbackEnvKey = null): ?string
    {
        $pool = self::getPool($service);

        if (empty($pool)) {
            // No pool configured — use single key from settings/env
            return self::getFallbackKey($service, $fallbackEnvKey);
        }

        // Filter enabled keys that aren't rate-limited
        $available = collect($pool)
            ->filter(fn($k) => ($k['enabled'] ?? true) && !self::isRateLimited($service, $k['key']))
            ->values();

        if ($available->isEmpty()) {
            // All keys rate-limited, try the least recently used one anyway
            $available = collect($pool)->filter(fn($k) => $k['enabled'] ?? true)->values();

            if ($available->isEmpty()) {
                Log::warning("ApiKeyPool: No enabled keys for {$service}");
                return self::getFallbackKey($service, $fallbackEnvKey);
            }
        }

        // Round-robin: get current index, advance it
        $cacheKey = "api_pool_index_{$service}";
        $index = Cache::get($cacheKey, 0) % $available->count();
        Cache::put($cacheKey, $index + 1, 3600);

        $selected = $available[$index];

        // Track usage
        self::trackUsage($service, $selected['key']);

        Log::debug("ApiKeyPool: Using key #{$index} ({$selected['label']}) for {$service}");

        return $selected['key'];
    }

    /**
     * Mark a key as rate-limited (temporarily skip it).
     */
    public static function markRateLimited(string $service, string $key, int $cooldownSeconds = 60): void
    {
        $cacheKey = "api_pool_ratelimit_{$service}_" . md5($key);
        Cache::put($cacheKey, true, $cooldownSeconds);

        // Track error count
        $errorKey = "api_pool_errors_{$service}_" . md5($key);
        $errors = Cache::get($errorKey, 0);
        Cache::put($errorKey, $errors + 1, 86400);

        Log::warning("ApiKeyPool: Key rate-limited for {$service}", [
            'key_hash' => substr(md5($key), 0, 8),
            'cooldown' => $cooldownSeconds,
            'total_errors' => $errors + 1,
        ]);
    }

    /**
     * Mark a key as failed (longer cooldown).
     */
    public static function markFailed(string $service, string $key): void
    {
        self::markRateLimited($service, $key, 300); // 5 min cooldown
    }

    /**
     * Check if a key is currently rate-limited.
     */
    public static function isRateLimited(string $service, string $key): bool
    {
        return Cache::has("api_pool_ratelimit_{$service}_" . md5($key));
    }

    /**
     * Get the full pool for a service.
     */
    public static function getPool(string $service): array
    {
        $raw = SiteSetting::get("api_pool_{$service}");
        if (!$raw) return [];

        $pool = is_string($raw) ? json_decode($raw, true) : $raw;
        return is_array($pool) ? $pool : [];
    }

    /**
     * Set the pool for a service.
     */
    public static function setPool(string $service, array $keys): void
    {
        SiteSetting::set("api_pool_{$service}", json_encode($keys), 'api_pool');

        // Clear rotation index
        Cache::forget("api_pool_index_{$service}");
    }

    /**
     * Add a key to the pool.
     */
    public static function addKey(string $service, string $key, string $label = ''): void
    {
        $pool = self::getPool($service);

        // Check duplicate
        foreach ($pool as $existing) {
            if ($existing['key'] === $key) return;
        }

        $pool[] = [
            'key' => $key,
            'label' => $label ?: 'Key #' . (count($pool) + 1),
            'enabled' => true,
            'added_at' => now()->toISOString(),
        ];

        self::setPool($service, $pool);
    }

    /**
     * Remove a key from the pool.
     */
    public static function removeKey(string $service, string $key): void
    {
        $pool = collect(self::getPool($service))
            ->filter(fn($k) => $k['key'] !== $key)
            ->values()
            ->toArray();

        self::setPool($service, $pool);
    }

    /**
     * Get pool stats for admin dashboard.
     */
    public static function getStats(string $service): array
    {
        $pool = self::getPool($service);
        $stats = [];

        foreach ($pool as $entry) {
            $keyHash = md5($entry['key']);
            $stats[] = [
                'label' => $entry['label'] ?? 'Unknown',
                'enabled' => $entry['enabled'] ?? true,
                'key_preview' => substr($entry['key'], 0, 12) . '...',
                'is_rate_limited' => self::isRateLimited($service, $entry['key']),
                'usage_today' => Cache::get("api_pool_usage_{$service}_{$keyHash}", 0),
                'errors_today' => Cache::get("api_pool_errors_{$service}_{$keyHash}", 0),
            ];
        }

        return $stats;
    }

    /**
     * Track usage count.
     */
    private static function trackUsage(string $service, string $key): void
    {
        $cacheKey = "api_pool_usage_{$service}_" . md5($key);
        $count = Cache::get($cacheKey, 0);
        Cache::put($cacheKey, $count + 1, 86400); // reset daily
    }

    /**
     * Get single fallback key from settings/env.
     */
    private static function getFallbackKey(string $service, ?string $fallbackEnvKey): ?string
    {
        $settingMap = [
            'groq' => 'groq_api_key',
            'google_maps' => 'google_maps_api_key',
            'google_tts' => 'google_cloud_tts_key',
        ];

        $settingKey = $settingMap[$service] ?? null;
        if ($settingKey) {
            $val = SiteSetting::get($settingKey);
            if ($val) return $val;
        }

        if ($fallbackEnvKey) {
            return config("services.{$fallbackEnvKey}") ?: '';
        }

        return null;
    }
}
