<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'category_id',
        'wallet_id',
        'to_wallet_id',
        'title',
        'amount',
        'admin_fee',
        'type',
        'date',
        'note',
        'receipt_image_path',
        'is_ocr',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function destinationWallet()
    {
        return $this->belongsTo(Wallet::class, 'to_wallet_id');
    }
}
