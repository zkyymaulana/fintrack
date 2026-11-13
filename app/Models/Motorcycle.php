<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Motorcycle extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'name',
        'brand',
        'model',
        'license_plate',
        'current_odometer_km',
    ];

    protected function casts(): array
    {
        return [
            'current_odometer_km' => 'integer',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function maintenanceItems()
    {
        return $this->hasMany(MotorcycleMaintenanceItem::class);
    }

    public function maintenanceRecords()
    {
        return $this->hasMany(MotorcycleMaintenanceRecord::class);
    }

    public function odometerReadings()
    {
        return $this->hasMany(MotorcycleOdometerReading::class);
    }
}
