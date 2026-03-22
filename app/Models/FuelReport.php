<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FuelReport extends Model
{
    use HasFactory;

    public const FUEL_TYPES = [
        'gasohol95',
        'gasohol91',
        'e20',
        'e85',
        'diesel',
        'diesel_b7',
        'premium_diesel',
        'ngv',
        'lpg',
    ];

    public const FUEL_LABELS = [
        'gasohol95' => 'แก๊สโซฮอล์ 95',
        'gasohol91' => 'แก๊สโซฮอล์ 91',
        'e20' => 'E20',
        'e85' => 'E85',
        'diesel' => 'ดีเซล',
        'diesel_b7' => 'ดีเซล B7',
        'premium_diesel' => 'ดีเซลพรีเมียม',
        'ngv' => 'NGV',
        'lpg' => 'LPG',
    ];

    public const STATUSES = [
        'available',
        'low',
        'empty',
        'unknown',
    ];

    public const STATUS_LABELS = [
        'available' => 'มี',
        'low' => 'เหลือน้อย',
        'empty' => 'หมด',
        'unknown' => 'ไม่ทราบ',
    ];

    protected $fillable = [
        'report_id',
        'fuel_type',
        'status',
        'price',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
        ];
    }

    public function stationReport(): BelongsTo
    {
        return $this->belongsTo(StationReport::class, 'report_id');
    }
}
