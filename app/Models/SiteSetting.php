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

    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = static::where('setting_key', $key)->first();

        return $setting ? $setting->setting_value : $default;
    }

    public static function set(string $key, mixed $value, string $group = 'general'): static
    {
        return static::updateOrCreate(
            ['setting_key' => $key],
            [
                'setting_value' => $value,
                'setting_group' => $group,
                'updated_at' => now(),
            ],
        );
    }
}
