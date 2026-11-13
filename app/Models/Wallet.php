<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Wallet extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'name',
        'wallet_type',
        'balance',
        'auto_deduction_enabled',
        'auto_deduction_amount',
        'auto_deduction_category_id',
        'auto_deduction_title',
        'auto_deduction_note',
        'auto_deduction_frequency',
        'auto_deduction_next_run_on',
        'auto_deduction_day_of_month',
        'auto_deduction_last_run_on',
        'auto_deduction_last_status',
    ];

    protected function casts(): array
    {
        return [
            'auto_deduction_enabled' => 'boolean',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
}
