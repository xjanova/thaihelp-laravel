<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BreakingNews extends Model
{
    protected $fillable = [
        'title', 'content', 'category', 'latitude', 'longitude',
        'location_name', 'image_urls', 'source_incident_ids',
        'reporter_count', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'image_urls' => 'array',
            'source_incident_ids' => 'array',
            'is_active' => 'boolean',
            'latitude' => 'double',
            'longitude' => 'double',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
