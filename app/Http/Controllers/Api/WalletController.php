<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    //
    public function index(Request $request)
    {
        $wallets = $request->user()->wallets()->latest()->get();

        $totalBalance = $wallets->sum('balance');

        return response()->json([
            'success' => true,
            'message' => 'Wallets retrieved successfully',
            'data' => [
                'wallets' => $wallets,
                'total_balance' => $totalBalance,
            ]
        ], 200);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'balance' => 'nullable|numeric|min:0',
        ]);

        $wallet = $request->user()->wallets()->create([
            'name' => $validatedData['name'],
            'balance' => $validatedData['balance'] ?? 0,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Wallet created successfully',
            'data' => $wallet
        ], 201);
    }

    public function update(Request $request, string $id)
    {
        $wallet = $request->user()->wallets()->find($id);

        if (!$wallet) {
            return response()->json([
                'success' => false,
                'message' => 'Wallet not found',
            ], 404);
        }

        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'balance' => 'nullable|numeric|min:0',
        ]);

        $wallet->update($validatedData);

        return response()->json([
            'success' => true,
            'message' => 'Wallet updated successfully',
            'data' => $wallet
        ], 200);
    }

    public function destroy(Request $request, string $id)
    {
        $wallet = $request->user()->wallets()->find($id);

        if (!$wallet) {
            return response()->json([
                'success' => false,
                'message' => 'Wallet not found',
            ], 404);
        }

        // Check if the wallet has any associated transactions
        if ($wallet->transactions()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete wallet with associated transactions',
            ], 400);
        }

        $wallet->delete();

        return response()->json([
            'success' => true,
            'message' => 'Wallet deleted successfully',
        ], 200);
    }
}
