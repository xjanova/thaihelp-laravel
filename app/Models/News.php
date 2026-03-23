<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class News extends Model
{
    protected $fillable = [
        'title', 'summary', 'source_url', 'source_name',
        'image_url', 'category', 'is_urgent', 'hash', 'published_at',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'is_urgent' => 'boolean',
        ];
    }

    /**
     * Scope: only today's news.
     */
    public function scopeToday($query)
    {
        return $query->where('created_at', '>=', now()->startOfDay());
    }

    /**
     * Scope: recent news (last 24h).
     */
    public function scopeRecent($query)
    {
        return $query->where('created_at', '>=', now()->subHours(24));
    }

    /**
     * Clean up news older than 24 hours.
     */
    public static function cleanupOld(): int
    {
        return static::where('created_at', '<', now()->subHours(24))->delete();
    }
}
