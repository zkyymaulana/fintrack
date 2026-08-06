<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Budget;
use Illuminate\Http\Request;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

class BudgetController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $targetMonth = null;
        $targetYear = null;

        // Tangkap parameter query filter jika ada (contoh: ?month=8&year=2026 atau ?month_year=08-2026)
        if ($request->filled('month_year')) {
            $monthYear = trim($request->query('month_year'));
            if (preg_match('/^(\d{1,2})-(\d{4})$/', $monthYear, $matches)) {
                $targetMonth = (int) $matches[1];
                $targetYear = (int) $matches[2];
            } elseif (preg_match('/^(\d{4})-(\d{1,2})$/', $monthYear, $matches)) {
                $targetYear = (int) $matches[1];
                $targetMonth = (int) $matches[2];
            }
        }

        if (!$targetMonth && $request->filled('month')) {
            $targetMonth = (int) $request->query('month');
        }

        if (!$targetYear && $request->filled('year')) {
            $targetYear = (int) $request->query('year');
        }

        $now = now();
        if (!$targetMonth || $targetMonth < 1 || $targetMonth > 12) {
            $targetMonth = (int) $now->format('m');
        }

        if (!$targetYear || $targetYear < 1000 || $targetYear > 9999) {
            $targetYear = (int) $now->format('Y');
        }

        $targetMonthYearStr = sprintf('%02d-%04d', $targetMonth, $targetYear);

        // Ambil semua data budget user, terurut dari yang terbaru
        $allBudgets = $request->user()->budgets()
            ->with('category')
            ->orderBy('id', 'desc')
            ->get();

        // Kelompokkan berdasarkan category_id
        // Tiap kategori mempertahankan limit_amount terbaru,
        // namun actual_spend dihitung khusus dari transaksi pada target bulan & tahun.
        $budgets = $allBudgets->groupBy('category_id')->map(function ($categoryBudgets) use ($targetMonthYearStr, $request, $targetMonth, $targetYear) {
            $budget = $categoryBudgets->firstWhere('month_year', $targetMonthYearStr) ?? $categoryBudgets->first();

            $actualSpend = $request->user()->transactions()
                ->where('category_id', $budget->category_id)
                ->whereMonth('date', $targetMonth)
                ->whereYear('date', $targetYear)
                ->where('type', 'expense')
                ->get()
                ->sum(function ($transaction) {
                    return (float) ($transaction->amount + $transaction->admin_fee);
                });

            $budget->actual_spend = (float) $actualSpend;
            $budget->remaining_budget = (float) ($budget->limit_amount - $actualSpend);

            return $budget;
        })->values();

        return response()->json([
            'success' => true,
            'message' => 'Budgets retrieved successfully',
            'data' => $budgets
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'limit_amount' => 'required|numeric|min:0',
            'month_year'   => 'required|string|size:7', // Format: MM-YYYY (contoh: 06-2026)
        ]);

        $budget = Budget::updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'category_id' => $validatedData['category_id'],
                'month_year' => $validatedData['month_year'],
            ],
            [
                'limit_amount' => $validatedData['limit_amount'],
            ]
        );

        return response()->json([
            'message' => 'Budget created successfully',
            'data' => $budget->load('category')
        ], 201);
    }

    public static function checkBudgetAndNotify(Request $request): void
    {
        $user     = $request->user();
        $fcmToken = $user->fcm_token;

        if (!$fcmToken) return;

        $now   = now();
        $month = (int) $now->format('m');
        $year  = (int) $now->format('Y');
        $monthYearStr = sprintf('%02d-%04d', $month, $year);

        $allBudgets = $user->budgets()->with('category')->orderBy('id', 'desc')->get();
        $budgets = $allBudgets->groupBy('category_id')->map(function ($categoryBudgets) use ($monthYearStr) {
            return $categoryBudgets->firstWhere('month_year', $monthYearStr) ?? $categoryBudgets->first();
        })->values();

        foreach ($budgets as $budget) {
            $actualSpend = $user->transactions()
                ->where('category_id', $budget->category_id)
                ->whereMonth('date', $month)
                ->whereYear('date', $year)
                ->where('type', 'expense')
                ->get()
                ->sum(fn($t) => (float) ($t->amount + $t->admin_fee));

            $percentage   = $budget->limit_amount > 0
                ? ($actualSpend / $budget->limit_amount) * 100
                : 0;

            $categoryName = $budget->category->name ?? 'General';
            $percentRound = round($percentage);
            $remaining    = number_format(max($budget->limit_amount - $actualSpend, 0), 0, ',', '.');
            $over         = number_format(max($actualSpend - $budget->limit_amount, 0), 0, ',', '.');

            // ⚠️ Approaching budget limit (80% - 99%)
            if ($percentage >= 80 && $percentage < 100) {
                self::sendFcmNotification(
                    $fcmToken,
                    $categoryName . ' Budget Alert',
                    $percentRound . '% of your ' . $categoryName . ' budget has been used. Rp' . $remaining . ' remaining.'
                );
            }

            // 🚨 Budget exceeded (>= 100%)
            if ($percentage >= 100) {
                self::sendFcmNotification(
                    $fcmToken,
                    $categoryName . ' Budget Exceeded',
                    'Your ' . $categoryName . ' spending has exceeded the budget limit by Rp' . $over . ' this month.'
                );
            }
        }

        // 📊 Expense vs Income check
        $totalExpense = $user->transactions()
            ->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->where('type', 'expense')
            ->get()
            ->sum(fn($t) => (float) ($t->amount + $t->admin_fee));

        $totalIncome = $user->transactions()
            ->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->where('type', 'income')
            ->get()
            ->sum(fn($t) => (float) ($t->amount + $t->admin_fee));

        if ($totalExpense > $totalIncome && $totalIncome > 0) {
            $deficit = number_format($totalExpense - $totalIncome, 0, ',', '.');
            self::sendFcmNotification(
                $fcmToken,
                'Spending Exceeds Income',
                'Your expenses exceed your income by Rp' . $deficit . ' this month. Consider reviewing your spending.'
            );
        }
    }

    /**
     * Helper kirim notifikasi via FCM V1
     */
    private static function sendFcmNotification(string $fcmToken, string $title, string $body): void
    {
        try {
            $messaging = app(Messaging::class);

            $message = CloudMessage::withTarget('token', $fcmToken)
                ->withNotification(Notification::create($title, $body));

            $messaging->send($message);
        } catch (\Exception $e) {
            // Gagal kirim notifikasi, log error tapi jangan crash app
            \Log::error('FCM Error: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
