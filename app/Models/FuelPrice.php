<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FuelPrice extends Model
{
    protected $fillable = ['brand', 'fuel_type', 'price', 'date'];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'date' => 'date',
        ];
    }
}
