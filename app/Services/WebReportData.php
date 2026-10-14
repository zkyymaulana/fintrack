<?php

namespace App\Services;

use App\Models\User;
use Carbon\CarbonImmutable;

class WebReportData
{
    public function build(User $user, CarbonImmutable $start, CarbonImmutable $end): array
    {
        $transactions = $user->transactions()
            ->with(['category:id,name', 'wallet:id,name', 'toWallet:id,name'])
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('date')
            ->orderBy('id')
            ->get();

        $income = '0.00';
        $expense = '0.00';
        $rows = [];

        foreach ($transactions as $transaction) {
            $amount = bcadd((string) $transaction->amount, '0', 2);
            $fee = bcadd((string) ($transaction->admin_fee ?? 0), '0', 2);
            $total = $transaction->type === 'income' ? $amount : bcadd($amount, $fee, 2);

            if ($transaction->type === 'income') {
                $income = bcadd($income, $amount, 2);
            } elseif ($transaction->type === 'expense') {
                $expense = bcadd($expense, $total, 2);
            }

            $rows[] = [
                'date' => $transaction->date,
                'title' => $transaction->title,
                'type' => $transaction->type,
                'category' => $transaction->category?->name ?? '-',
                'wallet' => $transaction->wallet?->name ?? '-',
                'destination' => $transaction->toWallet?->name,
                'amount' => $amount,
                'admin_fee' => $fee,
                'total' => $total,
                'note' => $transaction->note,
            ];
        }

        return [
            'start' => $start->toDateString(),
            'end' => $end->toDateString(),
            'summary' => [
                'income' => $income,
                'expense' => $expense,
                'net' => bcsub($income, $expense, 2),
            ],
            'count' => count($rows),
            'transactions' => $rows,
        ];
    }
}
