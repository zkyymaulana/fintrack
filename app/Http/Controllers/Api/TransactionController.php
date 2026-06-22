<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TransactionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Retrieve transactions for the authenticated user, including category information
        $transactions = $request->user()->transactions()->with('category')->latest()->get();
        return response()->json(['message' => 'Transactions retrieved successfully', 'data' => $transactions], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
        $validatedData = $request->validate([
            'category_id'        => 'required|exists:categories,id',
            'amount'             => 'required|numeric|min:0',
            'type'               => 'required|in:income,expense',
            'date'               => 'required|date',
            'note'               => 'nullable|string',
            'is_ocr'             => 'nullable|boolean',
            'receipt_image'      => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        // Handle file upload if receipt image is provided
        $imagepath = null;

        // logic to handle file upload
        if ($request->hasFile('receipt_image')) {
            // Store the uploaded file in the 'public/receipts' directory and get the path
            $imagepath = $request->file('receipt_image')->store('receipts', 'public');
        }

        // Create a new transaction for the authenticated user
        $transaction = $request->user()->transactions()->create([
            'user_id'           => $request->user()->id, // get the authenticated user's ID
            'category_id'        => $validatedData['category_id'],
            'amount'             => $validatedData['amount'],
            'type'               => $validatedData['type'],
            'date'               => $validatedData['date'],
            'note'               => $validatedData['note'] ?? null,
            'receipt_image_path' => $imagepath,
            'is_ocr'             => $validatedData['is_ocr'] ?? false,
        ]);

        return response()->json(['message' => 'Transaction created successfully', 'data' => $transaction->load('category') // load the category in the response
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
    public function destroy(Request $request, string $id)
    {
        // find the transaction for the authenticated user
        $transaction = $request->user()->transactions()->find($id);

        if(!$transaction) {
            return response()->json(['message' => 'Transaction not found'], 404);
        }

        // Check if the transaction has a receipt image and delete it from storage
        if ($transaction->receipt_image_path) {
            // Delete the receipt image from storage if it exists
            Storage::disk('public')->delete($transaction->receipt_image_path);
        }

        $transaction->delete();

        return response()->json(['message' => 'Transaction deleted successfully'], 200);
    }
}
