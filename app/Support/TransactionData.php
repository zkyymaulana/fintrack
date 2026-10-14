<?php

namespace App\Support;

use App\Models\Transaction;

class TransactionData
{
    public static function from(Transaction $transaction): array
    {
        return [
            'id' => $transaction->id,
            'title' => $transaction->title,
            'type' => $transaction->type,
            'amount' => (string) $transaction->amount,
            'admin_fee' => (string) $transaction->admin_fee,
            'date' => $transaction->date,
            'note' => $transaction->note,
            'category_id' => $transaction->category_id,
            'category_name' => $transaction->category?->name,
            'wallet_id' => $transaction->wallet_id,
            'wallet_name' => $transaction->wallet?->name,
            'to_wallet_id' => $transaction->to_wallet_id,
            'to_wallet_name' => $transaction->toWallet?->name,
        ];
    }
}
