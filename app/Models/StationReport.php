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
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'double',
            'longitude' => 'double',
        ];
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
