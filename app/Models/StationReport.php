<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StationReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'place_id',
        'station_name',
        'user_id',
        'reporter_name',
        'reporter_email',
        'note',
        'latitude',
        'longitude',
        'confirmation_count',
        'is_verified',
        'is_demo',
        'facilities',
    ];

    public const FACILITY_TYPES = [
        'air_pump'      => ['label' => 'ที่เติมลม', 'icon' => '🌀'],
        'restroom'      => ['label' => 'ห้องน้ำ', 'icon' => '🚻'],
        'convenience'   => ['label' => 'ร้านสะดวกซื้อ', 'icon' => '🏪'],
        'car_wash'      => ['label' => 'ล้างรถ', 'icon' => '🚿'],
        'coffee'        => ['label' => 'ร้านกาแฟ', 'icon' => '☕'],
        'atm'           => ['label' => 'ATM', 'icon' => '🏧'],
        'wifi'          => ['label' => 'WiFi ฟรี', 'icon' => '📶'],
        'ev_charger'    => ['label' => 'ชาร์จ EV', 'icon' => '⚡'],
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'double',
            'longitude' => 'double',
            'confirmed_ips' => 'array',
            'is_verified' => 'boolean',
            'is_demo' => 'boolean',
            'facilities' => 'array',
        ];
    }

    /**
     * Remove demo reports for a place_id when a real report comes in.
     */
    public static function replaceDemoWithReal(string $placeId): void
    {
        \Illuminate\Support\Facades\DB::transaction(function () use ($placeId) {
            $demoIds = static::where('place_id', $placeId)->where('is_demo', true)->pluck('id');
            if ($demoIds->isNotEmpty()) {
                \App\Models\FuelReport::whereIn('report_id', $demoIds)->delete();
                static::whereIn('id', $demoIds)->delete();
            }
        });
    }

    /**
     * Confirm this report from an IP address.
     * Returns false if this IP already confirmed.
     */
    public function confirm(string $ip): bool
    {
        $confirmedIps = $this->confirmed_ips ?? [];

        if (in_array($ip, $confirmedIps, true)) {
            return false;
        }

        $confirmedIps[] = $ip;

        $this->confirmed_ips = $confirmedIps;
        $this->confirmation_count = count($confirmedIps);
        $this->is_verified = $this->confirmation_count >= 2;
        $this->save();

        return true;
    }

    /**
     * Scope: only verified reports (confirmation_count >= 2).
     */
    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function fuelReports(): HasMany
    {
        return $this->hasMany(FuelReport::class, 'report_id');
    }
}
