<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MotorcycleMaintenanceItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'motorcycle_id',
        'name',
        'kind',
        'interval_km',
        'baseline_odometer_km',
        'note',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'interval_km' => 'integer',
            'baseline_odometer_km' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function motorcycle()
    {
        return $this->belongsTo(Motorcycle::class);
    }

    public function records()
    {
        return $this->hasMany(MotorcycleMaintenanceRecord::class, 'maintenance_item_id');
    }

    public function lastServicedOdometerKm(): int
    {
        return (int) ($this->records()->max('odometer_km') ?? $this->baseline_odometer_km);
    }

    public function nextDueOdometerKm(): int
    {
        return $this->lastServicedOdometerKm() + $this->interval_km;
    }
}
