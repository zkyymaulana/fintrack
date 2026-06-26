<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    public function getMonthlySummary(Request $request)
    {
        // Get the current month and year
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;

        // Retrieve transactions for the authenticated user for the current month and year
        $transactions = $request->user()->transactions()
            ->with('category')
            ->whereMonth('date', $currentMonth)
            ->whereYear('date', $currentYear)
            ->get();

        $totalIncome = 0;
        $totalExpense = 0;

        foreach ($transactions as $tx) {
            $totalAmount = $tx->amount + $tx->admin_fee;
            
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
        })->map(function ($group) {
            return $group->sum(function ($tx) {
                return $tx->amount + $tx->admin_fee;
            });
        });

        // format data to make it easier to read in the response
        $categoryBreakdown = [];
        foreach ($expenseByCategory as $categoryName => $total) {
            $categoryBreakdown[] = [
                'category' => $categoryName,
                'total'    => $total
            ];
        }

        return response()->json([
            'success' => true,
            'message' => 'Analytics retrieved successfully',
            'data' => [
                'period' => Carbon::now()->format('F Y'), // Example: "June 2026"
                'summary' => [
                    'income'  => $totalIncome,
                    'expense' => $totalExpense,
                    'total_spend' => $totalExpense,
                    'balance' => $totalIncome - $totalExpense,
                ],
                'expense_by_category' => $categoryBreakdown
            ]
        ], 200);

    }   
}
