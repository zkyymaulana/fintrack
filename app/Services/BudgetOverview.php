<?php

namespace App\Services;

use App\Models\User;
use Carbon\CarbonImmutable;

class BudgetOverview
{
    private const INVESTMENT_KEYWORDS = ['investasi', 'saham', 'bibit', 'bbri', 'bmri', 'saving'];

    public function forMonth(User $user, CarbonImmutable $month): array
    {
        $period = $month->format('m-Y');
        $transactions = $user->transactions()
            ->whereBetween('date', [$month->startOfMonth()->toDateString(), $month->endOfMonth()->toDateString()])
            ->whereIn('type', ['expense', 'transfer'])
            ->get(['category_id', 'type', 'amount', 'admin_fee', 'title', 'note']);
        $groups = $user->budgets()->with('category:id,name,type')->orderByDesc('id')->get()->groupBy('category_id');
        $rows = [];
        $totalLimit = '0.00';
        $totalSpent = '0.00';

        foreach ($groups as $categoryBudgets) {
            $budget = $categoryBudgets->firstWhere('month_year', $period) ?? $categoryBudgets->first();
            $categoryName = $budget->category?->name ?? 'Kategori tidak tersedia';
            $investment = $this->containsInvestmentKeyword($categoryName);
            $spent = '0.00';

            foreach ($transactions as $transaction) {
                if ($investment) {
                    if ($transaction->type !== 'transfer') {
                        continue;
                    }
                    $text = strtolower(trim(($transaction->title ?? '').' '.($transaction->note ?? '')));
                    if ((int) $transaction->category_id !== (int) $budget->category_id && ! $this->containsInvestmentKeyword($text)) {
                        continue;
                    }
                } elseif ($transaction->type !== 'expense' || (int) $transaction->category_id !== (int) $budget->category_id) {
                    continue;
                }

                $spent = bcadd($spent, bcadd((string) $transaction->amount, (string) ($transaction->admin_fee ?? 0), 2), 2);
            }

            $limit = bcadd((string) $budget->limit_amount, '0', 2);
            $remaining = bcsub($limit, $spent, 2);
            $percent = bccomp($limit, '0', 2) > 0 ? round((float) $spent / (float) $limit * 100, 1) : (bccomp($spent, '0', 2) > 0 ? 100.0 : 0.0);
            $rows[] = [
                'id' => $budget->id,
                'category_id' => $budget->category_id,
                'category_name' => $categoryName,
                'limit_amount' => $limit,
                'spent' => $spent,
                'remaining' => $remaining,
                'percentage' => $percent,
                'status' => $percent >= 100 ? 'over' : ($percent >= 80 ? 'warning' : 'safe'),
                'inherited' => $budget->month_year !== $period,
            ];
            $totalLimit = bcadd($totalLimit, $limit, 2);
            $totalSpent = bcadd($totalSpent, $spent, 2);
        }

        usort($rows, fn ($a, $b) => $b['percentage'] <=> $a['percentage']);

        return [
            'budgets' => $rows,
            'totalLimit' => $totalLimit,
            'totalSpent' => $totalSpent,
            'totalRemaining' => bcsub($totalLimit, $totalSpent, 2),
        ];
    }

    private function containsInvestmentKeyword(string $text): bool
    {
        foreach (self::INVESTMENT_KEYWORDS as $keyword) {
            if (str_contains(strtolower($text), $keyword)) {
                return true;
            }
        }

        return false;
    }
}
