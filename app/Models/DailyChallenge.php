<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DailyChallenge extends Model
{
    protected $fillable = [
        'title',
        'description',
        'target_type',
        'target_count',
        'reward_stars',
        'date',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'target_count' => 'integer',
            'reward_stars' => 'integer',
            'date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function userChallenges(): HasMany
    {
        return $this->hasMany(UserChallenge::class, 'challenge_id');
    }
}
