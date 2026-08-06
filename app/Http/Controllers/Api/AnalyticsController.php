<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    public function getMonthlySummary(Request $request)
    {
        $month = $request->query('month');
        $year = $request->query('year');

        // Handle optional month_year query parameter (e.g., ?month_year=2026-08)
        if ($request->filled('month_year')) {
            $monthYear = trim($request->query('month_year'));
            if (preg_match('/^(\d{4})-(\d{1,2})$/', $monthYear, $matches)) {
                $year = (int) $matches[1];
                $month = (int) $matches[2];
            } elseif (preg_match('/^(\d{1,2})-(\d{4})$/', $monthYear, $matches)) {
                $month = (int) $matches[1];
                $year = (int) $matches[2];
            }
        }

        $month = $month !== null ? (int) $month : null;
        $year = $year !== null ? (int) $year : null;

        $now = Carbon::now();

        if (!$month || $month < 1 || $month > 12) {
            $month = (int) $now->month;
        }

        if (!$year || $year < 1000 || $year > 9999) {
            $year = (int) $now->year;
        }

        $period = Carbon::createFromDate($year, $month, 1)->format('F Y');

        // Retrieve transactions for the authenticated user for specified month and year
        $transactions = $request->user()->transactions()
            ->with('category')
            ->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->get();

        $totalIncome = 0;
        $totalExpense = 0;

        foreach ($transactions as $tx) {
            $totalAmount = (float) ($tx->amount + $tx->admin_fee);

            if ($tx->type === 'income') {
                $totalIncome += $totalAmount;
            } elseif ($tx->type === 'expense') {
                $totalExpense += $totalAmount;
            }
        }

        // Group expenses by category
        $expenseTransactions = $transactions->where('type', 'expense');

        $expenseByCategory = $expenseTransactions->groupBy(function ($tx) {
            return $tx->category ? $tx->category->name : 'Uncategorized';
        })->map(function ($group, $categoryName) {
            $sum = $group->sum(function ($tx) {
                return (float) ($tx->amount + $tx->admin_fee);
            });

            return [
                'category' => $categoryName,
                'total'    => (float) $sum,
            ];
        })->values()->all();

        return response()->json([
            'success' => true,
            'message' => 'Monthly analytics fetched successfully',
            'data' => [
                'period' => $period,
                'summary' => [
                    'income'      => (float) $totalIncome,
                    'expense'     => (float) $totalExpense,
                    'total_spend' => (float) $totalExpense,
                    'balance'     => (float) ($totalIncome - $totalExpense),
                ],
                'expense_by_category' => $expenseByCategory,
            ],
        ], 200);
    }
}

