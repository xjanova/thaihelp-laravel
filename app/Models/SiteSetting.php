<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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

    protected static ?array $cache = null;

    public static function get(string $key, mixed $default = null): mixed
    {
        if (static::$cache === null) {
            try {
                static::$cache = static::pluck('setting_value', 'setting_key')->toArray();
            } catch (\Exception $e) {
                static::$cache = [];
            }
        }
        return static::$cache[$key] ?? $default;
    }

    public static function set(string $key, mixed $value, string $group = 'general'): void
    {
        static::updateOrCreate(
            ['setting_key' => $key],
            ['setting_value' => $value, 'setting_group' => $group]
        );
        static::$cache = null; // bust cache
    }

    public static function clearCache(): void
    {
        static::$cache = null;
    }
}
