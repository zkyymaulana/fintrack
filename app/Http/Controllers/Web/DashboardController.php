<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\BudgetOverview;
use App\Services\FinanceAnalytics;
use App\Support\TransactionData;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request, BudgetOverview $budgets, FinanceAnalytics $analytics): Response
    {
        $user = $request->user();
        $month = now()->startOfMonth();
        $transactions = $user->transactions()->whereBetween('date', [$month->toDateString(), $month->copy()->endOfMonth()->toDateString()]);
        $period = CarbonImmutable::now()->startOfMonth();
        $budgetOverview = $budgets->forMonth($user, $period);
        $trend = $analytics->report($user, $period, $period->endOfMonth(), $period->subMonths(5)->startOfMonth())['trend'];

        return Inertia::render('Dashboard', [
            'totalBalance' => (string) $user->wallets()->sum('balance'),
            'monthlyIncome' => (string) (clone $transactions)->where('type', 'income')->sum('amount'),
            'monthlyExpense' => (string) (clone $transactions)->where('type', 'expense')->selectRaw('COALESCE(SUM(amount + admin_fee), 0) as total')->value('total'),
            'walletCount' => $user->wallets()->count(),
            'transactionCount' => $user->transactions()->count(),
            'recentTransactions' => $user->transactions()->with(['category:id,name', 'wallet:id,name', 'toWallet:id,name'])
                ->orderByDesc('date')->orderByDesc('id')->limit(5)->get()->map(fn ($transaction) => TransactionData::from($transaction)),
            'budgetOverview' => ['totalLimit' => $budgetOverview['totalLimit'], 'totalSpent' => $budgetOverview['totalSpent'], 'budgets' => array_slice($budgetOverview['budgets'], 0, 3)],
            'trend' => $trend,
        ]);
    }
}
