<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MotorcycleMaintenanceRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'motorcycle_id',
        'maintenance_item_id',
        'transaction_id',
        'title',
        'odometer_km',
        'performed_on',
        'cost',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'odometer_km' => 'integer',
            'performed_on' => 'date:Y-m-d',
        ];
    }

    public function motorcycle()
    {
        return $this->belongsTo(Motorcycle::class);
    }

    public function maintenanceItem()
    {
        return $this->belongsTo(MotorcycleMaintenanceItem::class, 'maintenance_item_id');
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }
}
