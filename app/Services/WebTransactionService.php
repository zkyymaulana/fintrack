<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class WebTransactionService
{
    public function save(User $user, array $data, ?Transaction $transaction = null): Transaction
    {
        return DB::transaction(function () use ($user, $data, $transaction): Transaction {
            $transaction = $transaction
                ? $user->transactions()->lockForUpdate()->findOrFail($transaction->id)
                : null;

            $wallets = $this->lockWallets($user, $transaction, $data);
            if ($transaction) {
                $this->apply($wallets, $transaction, -1);
                $transaction->update($data);
            } else {
                $transaction = $user->transactions()->create($data);
            }
            $this->apply($wallets, $transaction, 1);

            return $transaction;
        });
    }

    public function delete(User $user, Transaction $transaction): void
    {
        $receiptPath = DB::transaction(function () use ($user, $transaction): ?string {
            $transaction = $user->transactions()->lockForUpdate()->findOrFail($transaction->id);
            $wallets = $this->lockWallets($user, $transaction, []);
            $this->apply($wallets, $transaction, -1);
            $path = $transaction->receipt_image_path;
            $transaction->delete();

            return $path;
        });

        if ($receiptPath) {
            Storage::disk('public')->delete($receiptPath);
        }
    }

    private function lockWallets(User $user, ?Transaction $old, array $data): array
    {
        $ids = array_values(array_unique(array_filter([
            $old?->wallet_id, $old?->to_wallet_id,
            $data['wallet_id'] ?? null, $data['to_wallet_id'] ?? null,
        ])));
        sort($ids);

        $wallets = $user->wallets()->whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get()->keyBy('id')->all();
        abort_if(count($wallets) !== count($ids), 404);

        return $wallets;
    }

    private function apply(array $wallets, Transaction $transaction, int $direction): void
    {
        $amount = (string) $transaction->amount;
        $cost = bcadd($amount, (string) ($transaction->admin_fee ?? '0'), 2);
        $source = $wallets[$transaction->wallet_id];
        $sourceChange = $transaction->type === 'income' ? $amount : bcsub('0', $cost, 2);
        $source->balance = bcadd((string) $source->balance, $direction === 1 ? $sourceChange : bcsub('0', $sourceChange, 2), 2);
        $source->save();

        if ($transaction->type === 'transfer') {
            $destination = $wallets[$transaction->to_wallet_id];
            $destination->balance = bcadd((string) $destination->balance, $direction === 1 ? $amount : bcsub('0', $amount, 2), 2);
            $destination->save();
        }
    }
}
