<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    /**
     * Laporan Analisa Keuangan Mendalam (Financial Analytics Report)
     * Mengembalikan Summary, Health Indicators, Expense by Category, dan 6-Months Trend.
     */
    public function getMonthlyReport(Request $request)
    {
        $month = $request->query('month');
        $year = $request->query('year');

        // Handle parameter query alternatif 'month_year' (contoh: 2026-08 atau 08-2026)
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

        $now = Carbon::now();
        $month = ($month !== null && (int)$month >= 1 && (int)$month <= 12) ? (int)$month : (int)$now->month;
        $year = ($year !== null && (int)$year >= 1000 && (int)$year <= 9999) ? (int)$year : (int)$now->year;

        $targetDate = Carbon::createFromDate($year, $month, 1);
        $startOfMonth = $targetDate->copy()->startOfMonth()->toDateString();
        $endOfMonth = $targetDate->copy()->endOfMonth()->toDateString();
        $targetMonthYearStr = sprintf('%02d-%04d', $month, $year);

        $user = $request->user();

        // -------------------------------------------------------------
        // 1. Ambil seluruh transaksi bulan berjalan (Hindari N+1 dengan Eager Loading)
        // -------------------------------------------------------------
        $currentMonthTransactions = $user->transactions()
            ->with('category:id,name,icon,type')
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->get();

        // Isolasi Mutlak: Hanya transaksi 'income' dan 'expense' murni (transfer/saving dikecualikan)
        $incomeTransactions = $currentMonthTransactions->filter(function ($tx) {
            return strtolower(trim($tx->type ?? '')) === 'income';
        });

        $expenseTransactions = $currentMonthTransactions->filter(function ($tx) {
            return strtolower(trim($tx->type ?? '')) === 'expense';
        });

        $totalIncome = (float) $incomeTransactions->sum(function ($tx) {
            return (float) ($tx->amount + ($tx->admin_fee ?? 0));
        });

        $totalExpense = (float) $expenseTransactions->sum(function ($tx) {
            return (float) ($tx->amount + ($tx->admin_fee ?? 0));
        });

        // Auto-detect saving / investment dari transaksi transfer dengan kata kunci tertentu
        $savingKeywords = ['investasi', 'saham', 'bibit', 'bbri', 'bmri', 'saving'];
        $savingTransactions = $currentMonthTransactions->filter(function ($tx) use ($savingKeywords) {
            if (strtolower(trim($tx->type ?? '')) !== 'transfer') {
                return false;
            }
            $searchableText = strtolower(trim(($tx->title ?? '') . ' ' . ($tx->note ?? '')));
            foreach ($savingKeywords as $keyword) {
                if (str_contains($searchableText, $keyword)) {
                    return true;
                }
            }
            return false;
        });

        $totalSaving = (float) $savingTransactions->sum(function ($tx) {
            return (float) $tx->amount;
        });

        $netCashFlow = (float) ($totalIncome - $totalExpense);

        // -------------------------------------------------------------
        // 2. Health Indicators (Indikator Kesehatan Finansial) & Formulasi Presisi
        // -------------------------------------------------------------
        // saving_rate = (total_saving / total_income) * 100 (Jika total_income 0, set ke 0)
        $savingRate = $totalIncome > 0
            ? round(($totalSaving / $totalIncome) * 100, 2)
            : 0.0;

        // Total Budget Allocated Bulan Ini (prioritas bulan ini, fallback ke budget aktif per kategori)
        $totalBudgetAllocated = (float) $user->budgets()
            ->where('month_year', $targetMonthYearStr)
            ->sum('limit_amount');

        if ($totalBudgetAllocated <= 0) {
            $allBudgets = $user->budgets()->get();
            $totalBudgetAllocated = (float) $allBudgets
                ->groupBy('category_id')
                ->map(fn($group) => $group->firstWhere('month_year', $targetMonthYearStr) ?? $group->first())
                ->sum('limit_amount');
        }

        // budget_utilization = (total_expense / total_budget_allocated) * 100 (Jika total_budget 0, set ke 0)
        $budgetUtilization = $totalBudgetAllocated > 0
            ? round(($totalExpense / $totalBudgetAllocated) * 100, 2)
            : 0.0;

        // Health Status Logic (Berdasarkan saving_rate)
        if ($savingRate >= 20) {
            $healthStatus = 'Sangat Sehat';
        } elseif ($savingRate >= 10) {
            $healthStatus = 'Sehat';
        } elseif ($savingRate >= 1) {
            $healthStatus = 'Rentan';
        } else {
            $healthStatus = $totalIncome > 0 ? 'Kurang/Defisit' : 'Belum Ada Pemasukan';
        }

        // -------------------------------------------------------------
        // 3. Chart Data: Pengeluaran per Kategori (diurutkan terbesar)
        // -------------------------------------------------------------
        $expenseByCategory = $expenseTransactions
            ->groupBy(function ($tx) {
                return $tx->category ? $tx->category->name : 'Lainnya';
            })
            ->map(function ($group, $categoryName) use ($totalExpense) {
                $categoryTotal = (float) $group->sum(function ($tx) {
                    return (float) ($tx->amount + ($tx->admin_fee ?? 0));
                });

                $percentage = $totalExpense > 0
                    ? round(($categoryTotal / $totalExpense) * 100, 2)
                    : 0.0;

                return [
                    'category_name' => $categoryName,
                    'total_amount'  => (float) $categoryTotal,
                    'percentage'    => (float) $percentage,
                ];
            })
            ->filter(fn($item) => $item['total_amount'] > 0)
            ->sortByDesc('total_amount')
            ->values()
            ->all();

        // -------------------------------------------------------------
        // 4. Chart Data: Tren 6 Bulan Terakhir (Single Query ke Database)
        // -------------------------------------------------------------
        $sixMonthsStart = $targetDate->copy()->subMonths(5)->startOfMonth()->toDateString();
        $sixMonthsEnd   = $targetDate->copy()->endOfMonth()->toDateString();

        $sixMonthsTransactions = $user->transactions()
            ->whereBetween('date', [$sixMonthsStart, $sixMonthsEnd])
            ->get(['id', 'amount', 'admin_fee', 'type', 'date']);

        $sixMonthsTrend = [];
        for ($i = 5; $i >= 0; $i--) {
            $cursor = $targetDate->copy()->subMonths($i);
            $cMonth = (int) $cursor->month;
            $cYear  = (int) $cursor->year;
            $monthLabel = $cursor->format('M Y'); // Contoh: "Mar 2026"

            $monthTx = $sixMonthsTransactions->filter(function ($tx) use ($cMonth, $cYear) {
                $dateObj = Carbon::parse($tx->date);
                return (int)$dateObj->month === $cMonth && (int)$dateObj->year === $cYear;
            });

            $mIncome = (float) $monthTx->where('type', 'income')->sum(function ($tx) {
                return (float) ($tx->amount + ($tx->admin_fee ?? 0));
            });

            $mExpense = (float) $monthTx->where('type', 'expense')->sum(function ($tx) {
                return (float) ($tx->amount + ($tx->admin_fee ?? 0));
            });

            $sixMonthsTrend[] = [
                'month_year'    => $monthLabel,
                'total_income'  => (float) $mIncome,
                'total_expense' => (float) $mExpense,
            ];
        }

        // -------------------------------------------------------------
        // 5. Item Spending Habits: Top Pengeluaran Berdasarkan Judul/Deskripsi Transaksi
        // -------------------------------------------------------------
        $topSpecificExpenses = $expenseTransactions
            ->groupBy(function ($tx) {
                $title = trim($tx->title ?? '');
                return $title !== '' ? $title : ($tx->category ? $tx->category->name : 'Pengeluaran');
            })
            ->map(function ($group, $title) {
                $sum = (float) $group->sum(function ($tx) {
                    return (float) ($tx->amount + ($tx->admin_fee ?? 0));
                });
                $count = $group->count();

                return [
                    'title'        => $title,
                    'total_amount' => (float) $sum,
                    'count'        => (int) $count,
                ];
            })
            ->filter(fn($item) => $item['total_amount'] > 0)
            ->sortByDesc('total_amount')
            ->values()
            ->take(5)
            ->all();

        // -------------------------------------------------------------
        // 6. Expense Distribution: Distribusi Pengeluaran dengan Detail Transaksi per Kategori
        // -------------------------------------------------------------
        $expenseDistribution = $expenseTransactions
            ->groupBy(function ($tx) {
                return $tx->category ? $tx->category->name : 'Lainnya';
            })
            ->map(function ($group, $categoryName) use ($totalExpense) {
                $firstCategory = $group->first()?->category;
                $categoryTotal = (float) $group->sum(function ($tx) {
                    return (float) ($tx->amount + ($tx->admin_fee ?? 0));
                });

                $percentage = $totalExpense > 0
                    ? round(($categoryTotal / $totalExpense) * 100, 2)
                    : 0.0;

                $transactions = $group->sortByDesc('date')->values()->map(function ($tx) {
                    return [
                        'id'         => $tx->id,
                        'title'      => $tx->title,
                        'amount'     => (float) ($tx->amount + ($tx->admin_fee ?? 0)),
                        'raw_amount' => (float) $tx->amount,
                        'admin_fee'  => (float) ($tx->admin_fee ?? 0),
                        'date'       => $tx->date,
                        'note'       => $tx->note,
                    ];
                })->all();

                return [
                    'category_id'        => $firstCategory?->id,
                    'category_name'      => $categoryName,
                    'category_icon'      => $firstCategory?->icon ?? 'category',
                    'total_amount'       => (float) $categoryTotal,
                    'percentage'         => (float) $percentage,
                    'transactions_count' => count($transactions),
                    'transactions'       => $transactions,
                ];
            })
            ->filter(fn($item) => $item['total_amount'] > 0)
            ->sortByDesc('total_amount')
            ->values()
            ->all();

        // -------------------------------------------------------------
        // 7. Response JSON Terstruktur Kelas Enterprise
        // -------------------------------------------------------------
        return response()->json([
            'success' => true,
            'message' => 'Monthly financial report generated successfully',
            'data'    => [
                'period' => [
                    'month'     => $month,
                    'year'      => $year,
                    'formatted' => $targetDate->format('F Y'),
                ],
                'summary' => [
                    'total_income'           => (float) $totalIncome,
                    'total_expense'          => (float) $totalExpense,
                    'total_saving'           => (float) $totalSaving,
                    'net_cash_flow'          => (float) $netCashFlow,
                    'total_budget_allocated' => (float) $totalBudgetAllocated,
                    'saving_rate'            => (float) $savingRate,
                    'budget_utilization'     => (float) $budgetUtilization,
                ],
                'total_saving'           => (float) $totalSaving,
                'saving_rate'            => (float) $savingRate,
                'budget_utilization'     => (float) $budgetUtilization,
                'total_budget_allocated' => (float) $totalBudgetAllocated,
                'health_indicators' => [
                    'savings_rate_percentage'       => (float) $savingRate,
                    'saving_rate'                   => (float) $savingRate,
                    'health_status'                 => $healthStatus,
                    'budget_utilization_percentage' => (float) $budgetUtilization,
                    'budget_utilization'            => (float) $budgetUtilization,
                    'total_budget_allocated'        => (float) $totalBudgetAllocated,
                ],
                'chart_data' => [
                    'expense_by_category'   => $expenseByCategory,
                    'six_months_trend'      => $sixMonthsTrend,
                    'top_specific_expenses' => $topSpecificExpenses,
                    'expense_distribution'  => $expenseDistribution,
                ],
                'expense_distribution' => $expenseDistribution,
                'top_specific_expenses' => $topSpecificExpenses,
            ],
            'total_saving'           => (float) $totalSaving,
            'saving_rate'            => (float) $savingRate,
            'budget_utilization'     => (float) $budgetUtilization,
            'total_budget_allocated' => (float) $totalBudgetAllocated,
            'expense_distribution'   => $expenseDistribution,
        ], 200);
    }

    /**
     * Endpoint ringkasan bulanan standar (Backward Compatible)
     */
    public function getMonthlySummary(Request $request)
    {
        return $this->getMonthlyReport($request);
    }
}


