<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class HospitalReport extends Model {
    const HOSPITAL_TYPES = ['general', 'community', 'private', 'clinic', 'field_hospital'];
    const HOSPITAL_TYPE_LABELS = [
        'general' => 'โรงพยาบาลทั่วไป',
        'community' => 'โรงพยาบาลชุมชน',
        'private' => 'โรงพยาบาลเอกชน',
        'clinic' => 'คลินิก',
        'field_hospital' => 'โรงพยาบาลสนาม',
    ];
    const ER_STATUSES = ['open', 'busy', 'full', 'closed', 'unknown'];
    const ER_STATUS_LABELS = [
        'open' => 'เปิดรับ',
        'busy' => 'หนาแน่น',
        'full' => 'เตียงเต็ม',
        'closed' => 'ปิด',
        'unknown' => 'ไม่ทราบ',
    ];
    const ER_STATUS_COLORS = [
        'open' => '#22c55e',
        'busy' => '#eab308',
        'full' => '#ef4444',
        'closed' => '#6b7280',
        'unknown' => '#94a3b8',
    ];

    protected $fillable = [
        'user_id', 'hospital_name', 'hospital_type', 'google_place_id',
        'latitude', 'longitude', 'address', 'phone',
        'total_beds', 'available_beds', 'icu_beds', 'icu_available',
        'er_status', 'note', 'reporter_ip', 'is_demo', 'is_verified', 'confirmation_count',
    ];

    protected function casts(): array {
        return [
            'latitude' => 'double', 'longitude' => 'double',
            'is_demo' => 'boolean', 'is_verified' => 'boolean',
            'total_beds' => 'integer', 'available_beds' => 'integer',
            'icu_beds' => 'integer', 'icu_available' => 'integer',
        ];
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }

    public function scopeRecent(Builder $query): Builder {
        return $query->where('created_at', '>=', now()->subHours(12));
    }

    public function scopeWithinRadius(Builder $query, float $lat, float $lng, float $km): Builder {
        return $query->whereRaw(
            '(6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) < ?',
            [$lat, $lng, $lat, $km]
        );
    }

    public function getBedPercentageAttribute(): ?int {
        if (!$this->total_beds || $this->total_beds <= 0) return null;
        return (int) round(($this->available_beds / $this->total_beds) * 100);
    }
}
