<?php

namespace App\Exports;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;

class FinancialReportExport implements FromView, ShouldAutoSize, WithTitle
{
    protected User $user;
    protected int $month;
    protected int $year;

    public function __construct(User $user, int $month, int $year)
    {
        $this->user = $user;
        $this->month = $month;
        $this->year = $year;
    }

    public function view(): View
    {
        $targetDate = Carbon::createFromDate($this->year, $this->month, 1);
        $startOfMonth = $targetDate->copy()->startOfMonth()->toDateString();
        $endOfMonth = $targetDate->copy()->endOfMonth()->toDateString();
        $targetMonthYearStr = sprintf('%02d-%04d', $this->month, $this->year);

        // Ambil transaksi dengan relasi (Hindari N+1)
        $transactions = $this->user->transactions()
            ->with(['category'])
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->orderBy('date', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $incomeTransactions = $transactions->where('type', 'income');
        $expenseTransactions = $transactions->where('type', 'expense');

        $totalIncome = (float) $incomeTransactions->sum(function ($tx) {
            return (float) ($tx->amount + ($tx->admin_fee ?? 0));
        });

        $totalExpense = (float) $expenseTransactions->sum(function ($tx) {
            return (float) ($tx->amount + ($tx->admin_fee ?? 0));
        });

        $netCashFlow = (float) ($totalIncome - $totalExpense);

        // Savings Rate
        if ($totalIncome > 0) {
            $savingsRate = round((($totalIncome - $totalExpense) / $totalIncome) * 100, 2);
        } else {
            $savingsRate = $totalExpense > 0 ? -100.0 : 0.0;
        }

        // Budget Limit
        $totalBudgetLimit = (float) $this->user->budgets()
            ->where('month_year', $targetMonthYearStr)
            ->sum('limit_amount');

        if ($totalBudgetLimit <= 0) {
            $allBudgets = $this->user->budgets()->get();
            $totalBudgetLimit = (float) $allBudgets
                ->groupBy('category_id')
                ->map(fn($group) => $group->firstWhere('month_year', $targetMonthYearStr) ?? $group->first())
                ->sum('limit_amount');
        }

        $budgetUtilization = $totalBudgetLimit > 0
            ? round(($totalExpense / $totalBudgetLimit) * 100, 2)
            : 0.0;

        return view('reports.financial_excel', [
            'user'              => $this->user,
            'period'            => $targetDate->translatedFormat('F Y'),
            'month'             => $this->month,
            'year'              => $this->year,
            'generatedAt'       => Carbon::now()->translatedFormat('d F Y, H:i'),
            'transactions'      => $transactions,
            'totalIncome'       => $totalIncome,
            'totalExpense'      => $totalExpense,
            'netCashFlow'       => $netCashFlow,
            'savingsRate'       => $savingsRate,
            'totalBudgetLimit'  => $totalBudgetLimit,
            'budgetUtilization' => $budgetUtilization,
        ]);
    }

    public function title(): string
    {
        return 'Laporan ' . Carbon::createFromDate($this->year, $this->month, 1)->format('M Y');
    }
}

