<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Budget;
use Illuminate\Http\Request;

class BudgetController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $budgets = $request->user()->budgets()->with('category')->get();

        $budgets = $budgets->map(function ($budget) use ($request) {
            list ($month, $year) = explode('-', $budget->month_year);

            $actualSpend = $request->user()->transactions()
                ->where('category_id', $budget->category_id)
                ->whereMonth('date', $month)
                ->whereYear('date', $year)
                ->where('type', 'expense')
                ->get()
                ->sum(function ($transaction) {
                    return $transaction->amount + $transaction->admin_fee;
                });
            $budget->actual_spend = $actualSpend; 
            $budget->remaining_budget = $budget->limit_amount - $actualSpend;

            return $budget;
        });      
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
