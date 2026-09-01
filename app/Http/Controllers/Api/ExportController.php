<?php

namespace App\Http\Controllers\Api;

use App\Exports\FinancialReportExport;
use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ExportController extends Controller
{
    /**
     * Helper untuk memproses parameter bulan dan tahun
     */
    private function parseMonthYear(Request $request): array
    {
        $month = $request->query('month');
        $year = $request->query('year');

        // Handle format alternatif 'month_year' (contoh: 2026-08 atau 08-2026)
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

        return [$month, $year];
    }

    /**
     * Export Laporan Keuangan ke format PDF
     * GET /api/export/pdf?month=x&year=y
     */
    public function exportPDF(Request $request)
    {
        [$month, $year] = $this->parseMonthYear($request);
        $user = $request->user();

        $targetDate = Carbon::createFromDate($year, $month, 1);
        $startOfMonth = $targetDate->copy()->startOfMonth()->toDateString();
        $endOfMonth = $targetDate->copy()->endOfMonth()->toDateString();
        $targetMonthYearStr = sprintf('%02d-%04d', $month, $year);

        // Ambil transaksi dengan eager loading kategori
        $transactions = $user->transactions()
            ->with(['category:id,name,icon,type'])
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
        $totalBudgetLimit = (float) $user->budgets()
            ->where('month_year', $targetMonthYearStr)
            ->sum('limit_amount');

        if ($totalBudgetLimit <= 0) {
            $allBudgets = $user->budgets()->get();
            $totalBudgetLimit = (float) $allBudgets
                ->groupBy('category_id')
                ->map(fn($group) => $group->firstWhere('month_year', $targetMonthYearStr) ?? $group->first())
                ->sum('limit_amount');
        }

        $budgetUtilization = $totalBudgetLimit > 0
            ? round(($totalExpense / $totalBudgetLimit) * 100, 2)
            : 0.0;

        $pdfData = [
            'user'              => $user,
            'period'            => $targetDate->translatedFormat('F Y'),
            'month'             => $month,
            'year'              => $year,
            'generatedAt'       => Carbon::now()->translatedFormat('d F Y, H:i'),
            'transactions'      => $transactions,
            'totalIncome'       => $totalIncome,
            'totalExpense'      => $totalExpense,
            'netCashFlow'       => $netCashFlow,
            'savingsRate'       => $savingsRate,
            'totalBudgetLimit'  => $totalBudgetLimit,
            'budgetUtilization' => $budgetUtilization,
        ];

        $fileName = 'Laporan_Keuangan_' . $targetDate->format('M_Y') . '.pdf';

        $pdf = Pdf::loadView('reports.financial_pdf', $pdfData);
        $pdf->setPaper('a4', 'portrait');

        // Kembalikan stream/download PDF
        return $pdf->download($fileName);
    }

    /**
     * Export Laporan Keuangan ke format Excel (.xlsx)
     * GET /api/export/excel?month=x&year=y
     */
    public function exportExcel(Request $request)
    {
        [$month, $year] = $this->parseMonthYear($request);
        $user = $request->user();

        $targetDate = Carbon::createFromDate($year, $month, 1);
        $fileName = 'Laporan_Keuangan_' . $targetDate->format('M_Y') . '.xlsx';

        return Excel::download(
            new FinancialReportExport($user, $month, $year),
            $fileName,
            \Maatwebsite\Excel\Excel::XLSX
        );
    }
}

