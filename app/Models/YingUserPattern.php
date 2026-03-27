<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class YingUserPattern extends Model
{
    protected $fillable = [
        'user_id', 'session_id', 'pattern_type', 'pattern_key',
        'pattern_data', 'occurrence_count', 'confidence',
    ];

    protected $casts = [
        'pattern_data' => 'array',
        'confidence' => 'float',
    ];

    const TYPES = [
        'preferred_brand' => 'แบรนด์ปั๊มที่ชอบ',
        'preferred_fuel' => 'ประเภทน้ำมัน',
        'frequent_route' => 'เส้นทางประจำ',
        'home_area' => 'พื้นที่บ้าน',
        'work_area' => 'พื้นที่ทำงาน',
        'time_pattern' => 'ช่วงเวลาใช้งาน',
        'vehicle_type' => 'ประเภทรถ',
        'communication_style' => 'สไตล์สื่อสาร',
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
        });
    }

    public function scopeConfident($query, float $threshold = 0.7)
    {
        return $query->where('confidence', '>=', $threshold);
    }

    /**
     * Record or strengthen a pattern.
     */
    public static function record(?int $userId, ?string $sessionId, string $type, string $key, array $data): self
    {
        $existing = static::where('pattern_type', $type)
            ->where('pattern_key', $key)
            ->where(function ($q) use ($userId, $sessionId) {
                if ($userId) $q->where('user_id', $userId);
                elseif ($sessionId) $q->where('session_id', $sessionId);
            })->first();

        if ($existing) {
            $existing->increment('occurrence_count');
            $newConfidence = min(1.0, $existing->confidence + 0.1);
            $existing->update([
                'pattern_data' => array_merge($existing->pattern_data, $data),
                'confidence' => $newConfidence,
            ]);
            return $existing;
        }

        return static::create([
            'user_id' => $userId,
            'session_id' => $sessionId,
            'pattern_type' => $type,
            'pattern_key' => $key,
            'pattern_data' => $data,
            'occurrence_count' => 1,
            'confidence' => 0.5,
        ]);
    }
}
