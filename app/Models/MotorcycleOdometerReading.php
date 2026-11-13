<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MotorcycleOdometerReading extends Model
{
    use HasFactory;

    protected $fillable = [
        'motorcycle_id',
        'odometer_km',
        'recorded_at',
        'source',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'odometer_km' => 'integer',
            'recorded_at' => 'datetime',
        ];
    }

    public function motorcycle()
    {
        return $this->belongsTo(Motorcycle::class);
    }
}
