<?php

namespace App\Services;

use App\Models\User;
use Carbon\CarbonImmutable;

class FinanceAnalytics
{
    public function report(User $user, CarbonImmutable $start, CarbonImmutable $end, CarbonImmutable $trendStart): array
    {
        $queryStart = $trendStart->lessThan($start) ? $trendStart : $start;
        $transactions = $user->transactions()
            ->with('category:id,name')
            ->whereBetween('date', [$queryStart->toDateString(), $end->toDateString()])
            ->get(['id', 'category_id', 'type', 'amount', 'admin_fee', 'date']);

        $income = '0.00';
        $expense = '0.00';
        $distribution = [];
        $trend = [];
        for ($cursor = $trendStart->startOfMonth(); $cursor->lessThanOrEqualTo($end); $cursor = $cursor->addMonth()) {
            $key = $cursor->format('Y-m');
            $trend[$key] = ['month' => $key, 'income' => '0.00', 'expense' => '0.00'];
        }

        foreach ($transactions as $transaction) {
            if (! in_array($transaction->type, ['income', 'expense'], true)) {
                continue;
            }
            $key = substr($transaction->date, 0, 7);
            $value = $transaction->type === 'expense'
                ? bcadd((string) $transaction->amount, (string) ($transaction->admin_fee ?? 0), 2)
                : bcadd((string) $transaction->amount, '0', 2);
            $trend[$key][$transaction->type] = bcadd($trend[$key][$transaction->type], $value, 2);

            if ($transaction->date < $start->toDateString() || $transaction->date > $end->toDateString()) {
                continue;
            }
            if ($transaction->type === 'income') {
                $income = bcadd($income, $value, 2);
            } else {
                $expense = bcadd($expense, $value, 2);
                $categoryId = $transaction->category_id;
                if (! isset($distribution[$categoryId])) {
                    $distribution[$categoryId] = [
                        'category_id' => $categoryId,
                        'category_name' => $transaction->category?->name ?? 'Kategori tidak tersedia',
                        'amount' => '0.00',
                        'count' => 0,
                    ];
                }
                $distribution[$categoryId]['amount'] = bcadd($distribution[$categoryId]['amount'], $value, 2);
                $distribution[$categoryId]['count']++;
            }
        }

        $distribution = array_values($distribution);
        usort($distribution, fn ($a, $b) => bccomp($b['amount'], $a['amount'], 2));
        foreach ($distribution as &$row) {
            $row['percentage'] = bccomp($expense, '0', 2) > 0 ? round((float) $row['amount'] / (float) $expense * 100, 1) : 0.0;
        }
        unset($row);

        return [
            'summary' => [
                'income' => $income,
                'expense' => $expense,
                'net' => bcsub($income, $expense, 2),
            ],
            'trend' => array_values($trend),
            'distribution' => $distribution,
        ];
    }
}
