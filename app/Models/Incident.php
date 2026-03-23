<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Incident extends Model
{
    use HasFactory;

    public const CATEGORIES = [
        'accident',
        'flood',
        'roadblock',
        'checkpoint',
        'construction',
        'other',
    ];

    public const CATEGORY_LABELS = [
        'accident' => 'อุบัติเหตุ',
        'flood' => 'น้ำท่วม',
        'roadblock' => 'ถนนปิด',
        'checkpoint' => 'จุดตรวจ',
        'construction' => 'ก่อสร้าง',
        'other' => 'อื่นๆ',
    ];

    public const CATEGORY_EMOJI = [
        'accident' => '🚗',
        'flood' => '🌊',
        'roadblock' => '🚧',
        'checkpoint' => '👮',
        'construction' => '🏗️',
        'other' => '⚠️',
    ];

    protected $fillable = [
        'user_id',
        'category',
        'title',
        'description',
        'latitude',
        'longitude',
        'image_url',
        'photos',
        'upvotes',
        'is_active',
        'is_demo',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_demo' => 'boolean',
            'photos' => 'array',
            'expires_at' => 'datetime',
            'latitude' => 'double',
            'longitude' => 'double',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function votes(): HasMany
    {
        return $this->hasMany(IncidentVote::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where(function (Builder $q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }
}
