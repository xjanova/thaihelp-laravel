<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class YingMemory extends Model
{
    protected $fillable = [
        'user_id', 'session_id', 'category', 'key', 'value',
        'source_message', 'status', 'admin_approved', 'use_count', 'last_used_at',
    ];

    protected $casts = [
        'admin_approved' => 'boolean',
        'last_used_at' => 'datetime',
    ];

    const CATEGORIES = [
        'preference' => 'ความชอบ',
        'fact' => 'ข้อเท็จจริง',
        'correction' => 'แก้ไข/สอน',
        'nickname' => 'ชื่อเล่น',
        'location' => 'สถานที่',
        'vehicle' => 'ยานพาหนะ',
        'routine' => 'กิจวัตร',
        'contact' => 'ข้อมูลติดต่อ',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeForUser($query, ?int $userId, ?string $sessionId)
    {
        return $query->where(function ($q) use ($userId, $sessionId) {
            if ($userId) $q->where('user_id', $userId);
            if ($sessionId) $q->orWhere('session_id', $sessionId);
        })->where('status', 'active');
    }

    public function markUsed(): void
    {
        $this->increment('use_count');
        $this->update(['last_used_at' => now()]);
    }
}
