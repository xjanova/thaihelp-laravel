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
        'fuel_shortage',
        'fire',
        'protest',
        'crime',
        'other',
    ];

    public const CATEGORY_LABELS = [
        'accident' => 'อุบัติเหตุ',
        'flood' => 'น้ำท่วม',
        'roadblock' => 'ถนนปิด',
        'checkpoint' => 'จุดตรวจ',
        'construction' => 'ก่อสร้าง',
        'fuel_shortage' => 'น้ำมันหมด',
        'fire' => 'ไฟไหม้',
        'protest' => 'ชุมนุม/ประท้วง',
        'crime' => 'อาชญากรรม',
        'other' => 'อื่นๆ',
    ];

    public const CATEGORY_EMOJI = [
        'accident' => '🚗',
        'flood' => '🌊',
        'roadblock' => '🚧',
        'checkpoint' => '👮',
        'construction' => '🏗️',
        'fuel_shortage' => '⛽',
        'fire' => '🔥',
        'protest' => '📢',
        'crime' => '🚨',
        'other' => '⚠️',
    ];

    public const SEVERITIES = ['critical', 'high', 'medium', 'low'];

    public const SEVERITY_LABELS = [
        'critical' => 'วิกฤต',
        'high' => 'รุนแรง',
        'medium' => 'ปานกลาง',
        'low' => 'เล็กน้อย',
    ];

    public const SEVERITY_COLORS = [
        'critical' => '#dc2626',
        'high' => '#f97316',
        'medium' => '#eab308',
        'low' => '#22c55e',
    ];

    public const STATUSES = ['active', 'confirmed', 'resolved', 'expired'];

    public const STATUS_LABELS = [
        'active' => 'กำลังเกิด',
        'confirmed' => 'ยืนยันแล้ว',
        'resolved' => 'คลี่คลายแล้ว',
        'expired' => 'หมดอายุ',
    ];

    public const REPORT_SOURCES = ['app', 'voice', 'discord', 'auto'];

    protected $fillable = [
        'user_id',
        'category',
        'title',
        'description',
        'latitude',
        'longitude',
        'location_name',
        'severity',
        'status',
        'image_url',
        'photos',
        'video_url',
        'incident_at',
        'road_name',
        'affected_lanes',
        'has_injuries',
        'emergency_notified',
        'reporter_ip',
        'report_source',
        'upvotes',
        'confirmation_count',
        'is_active',
        'is_demo',
        'is_danger_zone',
        'danger_radius_km',
        'expires_at',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_demo' => 'boolean',
            'is_danger_zone' => 'boolean',
            'has_injuries' => 'boolean',
            'emergency_notified' => 'boolean',
            'danger_radius_km' => 'double',
            'photos' => 'array',
            'expires_at' => 'datetime',
            'incident_at' => 'datetime',
            'resolved_at' => 'datetime',
            'latitude' => 'double',
            'longitude' => 'double',
            'confirmation_count' => 'integer',
            'affected_lanes' => 'integer',
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
            ->where('status', '!=', 'resolved')
            ->where(function (Builder $q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    /**
     * Scope: only within radius (km) from a point.
     */
    public function scopeWithinRadius(Builder $query, float $lat, float $lng, float $radiusKm): Builder
    {
        return $query->whereRaw(
            '(6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) < ?',
            [$lat, $lng, $lat, $radiusKm]
        );
    }

    /**
     * Mark as resolved.
     */
    public function resolve(): void
    {
        $this->update([
            'status' => 'resolved',
            'is_active' => false,
            'resolved_at' => now(),
        ]);
    }

    /**
     * Increment confirmation + evaluate emergency threshold.
     */
    public function addConfirmation(): void
    {
        $this->increment('confirmation_count');
        $count = $this->fresh()->confirmation_count;

        // Stage 1: 3+ confirmations → confirmed status
        if ($count >= 3 && $this->status === 'active') {
            $this->update(['status' => 'confirmed']);
        }

        // Stage 2: 5+ confirmations or critical severity → emergency report
        // น้องหญิงแจ้งฉุกเฉินเมื่อมีคนยืนยันเยอะพอ
        if ($count >= 5 || ($this->severity === 'critical' && $count >= 3) || $this->has_injuries) {
            try {
                app(\App\Services\EmergencyReportService::class)->evaluate($this->fresh());
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning('Emergency evaluate failed', ['error' => $e->getMessage()]);
            }
        }

        // Stage 3: 10+ confirmations → danger zone (ตีกรอบแดงห้ามเข้า)
        if ($count >= 10 && !$this->is_danger_zone) {
            $this->update(['is_danger_zone' => true]);
        }
    }

    /**
     * Check if this incident is a danger zone.
     */
    public function isDangerZone(): bool
    {
        return $this->is_danger_zone
            || ($this->severity === 'critical' && $this->confirmation_count >= 5)
            || ($this->has_injuries && $this->confirmation_count >= 3);
    }

    /**
     * Get severity color.
     */
    public function getSeverityColorAttribute(): string
    {
        return self::SEVERITY_COLORS[$this->severity ?? 'medium'] ?? '#eab308';
    }

    /**
     * Get display label.
     */
    public function getCategoryLabelAttribute(): string
    {
        return self::CATEGORY_LABELS[$this->category] ?? $this->category;
    }

    public function getCategoryEmojiAttribute(): string
    {
        return self::CATEGORY_EMOJI[$this->category] ?? '⚠️';
    }
}
