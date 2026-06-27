<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class TransactionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Retrieve transactions for the authenticated user, including category information
        $transactions = $request->user()->transactions()->with('category')->latest()->get()->map(function ($transaction) {
            $transaction->total_amount = $transaction->amount + $transaction->admin_fee;
            return $transaction;
        });
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
            'wallet_id'          => 'nullable|exists:wallets,id', 
            'title'              => 'required|string|max:255',
            'amount'             => 'required|numeric|min:0',
            'admin_fee'          => 'nullable|numeric|min:0',
            'type'               => 'required|in:income,expense,transfer',
            'to_wallet_id' => 'nullable|exists:wallets,id|different:wallet_id',
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

        $adminFee = $validatedData['admin_fee'] ?? 0;

        // Create a new transaction for the authenticated user
        $transaction = $request->user()->transactions()->create([
            'user_id'           => $request->user()->id, // get the authenticated user's ID
            'category_id'        => $validatedData['category_id'],
            'wallet_id'          => $validatedData['wallet_id'],
            'title'              => $validatedData['title'],
            'amount'             => $validatedData['amount'],
            'admin_fee'          => $adminFee,
            'type'               => $validatedData['type'],
            'date'               => $validatedData['date'],
            'note'               => $validatedData['note'] ?? null,
            'receipt_image_path' => $imagepath,
            'is_ocr'             => $validatedData['is_ocr'] ?? false,
        ]);

        $wallet = Wallet::find($validatedData['wallet_id']);

        if ($wallet) {
            if ($validatedData['type'] === 'expense') {
                $wallet->balance -= ($validatedData['amount'] + $adminFee);
                $wallet->save();
            } 
            elseif ($validatedData['type'] === 'income') {
                $wallet->balance += $validatedData['amount'];
                $wallet->save();
            } 
            elseif ($validatedData['type'] === 'transfer') {
                // 1. decrease the balance of the source wallet by the amount + admin fee
                $wallet->balance -= ($validatedData['amount'] + $adminFee);
                $wallet->save();

                // 2. increase the balance of the destination wallet by the amount (without admin fee)
                $destinationWallet = Wallet::find($validatedData['to_wallet_id']);
                if ($destinationWallet) {
                    $destinationWallet->balance += $validatedData['amount'];
                    $destinationWallet->save();
                }
            }
        }
        
        BudgetController::checkBudgetAndNotify($request);

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
        $transaction = $request->user()->transactions()->find($id);

        if(!$transaction) {
            return response()->json(['success' => false, 'message' => 'Transaction not found'], 404);
        }

        $validatedData = $request->validate([
            'category_id'        => 'sometimes|exists:categories,id',
            'wallet_id'          => 'sometimes|exists:wallets,id',
            'title'              => 'sometimes|string|max:255',
            'amount'             => 'sometimes|numeric|min:0',
            'admin_fee'          => 'sometimes|numeric|min:0',
            'type'               => 'sometimes|in:income,expense',
            'date'               => 'sometimes|date',
            'note'               => 'nullable|string',
            'is_ocr'             => 'nullable|boolean',
            'receipt_image'      => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        if ($transaction->wallet_id) {
            $oldWallet = Wallet::find($transaction->wallet_id);
            if ($oldWallet) {
                if ($transaction->type === 'expense') {
                    $oldWallet->balance += ($transaction->amount + $transaction->admin_fee);
                } elseif ($transaction->type === 'income') {
                    $oldWallet->balance -= $transaction->amount; 
                } elseif ($transaction->type === 'transfer') {
                    $oldWallet->balance += ($transaction->amount + $transaction->admin_fee);
                    if ($transaction->to_wallet_id) {
                        $oldDestWallet = Wallet::find($transaction->to_wallet_id);
                        if ($oldDestWallet) {
                            $oldDestWallet->balance -= $transaction->amount;
                            $oldDestWallet->save();
                        }
                    }
                }
                $oldWallet->save();
            }
        }

        if ($request->hasFile('receipt_image')) {
            // Delete the old receipt image if it exists
            if ($transaction->receipt_image_path) {
                Storage::disk('public')->delete($transaction->receipt_image_path);
            }
            // Store the new uploaded file in the 'public/receipts' directory and get the path
            $validatedData['receipt_image_path'] = $request->file('receipt_image')->store('receipts', 'public');
        }

        $transaction->update($validatedData);

        $transaction->refresh();

        if ($transaction->wallet_id) {
            $newWallet = Wallet::find($transaction->wallet_id);
            if ($newWallet) {
                if ($transaction->type === 'expense') {
                    $newWallet->balance -= ($transaction->amount + $transaction->admin_fee);
                } elseif ($transaction->type === 'income') {
                    // Perbaikan: Income hanya menambah amount aslinya
                    $newWallet->balance += $transaction->amount; 
                } elseif ($transaction->type === 'transfer') {
                    // Perbaikan: Terapkan aturan transfer yang baru
                    $newWallet->balance -= ($transaction->amount + $transaction->admin_fee);
                    if ($transaction->to_wallet_id) {
                        $newDestWallet = Wallet::find($transaction->to_wallet_id);
                        if ($newDestWallet) {
                            $newDestWallet->balance += $transaction->amount;
                            $newDestWallet->save();
                        }
                    }
                }
                $newWallet->save();
            }
        }  

        return response()->json(['success' => true, 'message' => 'Transaction updated successfully', 'data' => $transaction->load('category')], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $id)
    {
        $transaction = $request->user()->transactions()->find($id);

        if(!$transaction) {
            return response()->json(['message' => 'Transaction not found'], 404);
        }

        // 1. return the money to the wallet if the transaction is an expense or income
        if ($transaction->wallet_id) {
            $wallet = Wallet::find($transaction->wallet_id);
            if ($wallet) {
                if ($transaction->type === 'expense') {
                    // If it was an expense, return the money
                    $wallet->balance += ($transaction->amount + $transaction->admin_fee);
                } 
                elseif ($transaction->type === 'income') {
                    // If it was an income, withdraw the money
                    $wallet->balance -= $transaction->amount;
                } 
                elseif ($transaction->type === 'transfer') {
                    // If it was a transfer, return the money to the source wallet
                    $wallet->balance += ($transaction->amount + $transaction->admin_fee);
                    
                    // And withdraw the money that was added to the destination wallet
                    if ($transaction->to_wallet_id) {
                        $destinationWallet = Wallet::find($transaction->to_wallet_id);
                        if ($destinationWallet) {
                            $destinationWallet->balance -= $transaction->amount;
                            $destinationWallet->save();
                        }
                    }
                }
                $wallet->save();
            }
        }

        // 2. Check if the transaction has an associated receipt image and delete it from storage
        if ($transaction->receipt_image_path) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($transaction->receipt_image_path);
        }

        // 3. Delete the transaction from the database
        $transaction->delete();

        return response()->json(['message' => 'Transaction deleted successfully'], 200);
    }

    public function scan(Request $request)
{
    $request->validate([
        'receipt_image' => 'required|image|mimes:jpeg,png,jpg|max:4096',
    ]);

    $image = $request->file('receipt_image');
    $base64Image = base64_encode(file_get_contents($image->path()));
    $mimeType = $image->getMimeType();

    // Tambahkan jaring pengaman ekstra
    if ($mimeType === 'application/octet-stream') {
        $mimeType = 'image/jpeg'; // Anggap sebagai JPEG (standar kamera HP)
    }

    $apiKey = env('GEMINI_API_KEY');
    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key={$apiKey}";

    $response = Http::post($url, [
        'contents' => [
            [
                'parts' => [
                    [
                        'text' => 'Analisis gambar struk belanja ini. Ekstrak informasi berikut dan kembalikan HANYA dalam format JSON yang valid dengan key berikut:
- "title": Nama toko atau merchant dengan awalan "Belanja di " (contoh: "Belanja di Indomaret")
- "amount": total akhir yang harus dibayar (number murni tanpa desimal/simbol)
- "admin_fee": biaya PPN, pajak, atau admin jika ada (number murni, 0 jika tidak ada)
- "date": tanggal transaksi (string, format YYYY-MM-DD)
- "payment_method": Tebak metode pembayaran dalam 1 kata kunci saja (contoh: "BCA", "Mandiri", "OVO", "Gopay", atau "Cash").
- "note": Daftar barang yang dibeli. Setiap item gunakan baris baru (\n) dengan format "- [Nama Item] (x[Jumlah]) : [Harga]".
Jangan tambahkan teks apa pun selain JSON.'
                    ],
                    [
                        'inlineData' => [
                            'mimeType' => $mimeType,
                            'data'     => $base64Image
                        ]
                    ]
                ]
            ]
        ]
    ]);

    if ($response->successful()) {
        $geminiData = $response->json();
        $extractedText = $geminiData['candidates'][0]['content']['parts'][0]['text'] ?? '{}';
        $cleanText = preg_replace('/```json|```/', '', $extractedText);
        $parsedData = json_decode(trim($cleanText), true);

        return response()->json([
            'success' => true,
            'message' => 'Receipt scanned successfully',
            'data'    => [
                'title'          => $parsedData['title'] ?? 'Scan Struk Baru',
                'amount'         => floatval($parsedData['amount'] ?? 0),
                'admin_fee'      => floatval($parsedData['admin_fee'] ?? 0),
                'date'           => $parsedData['date'] ?? now()->format('Y-m-d'),
                'note'           => $parsedData['note'] ?? null,
                'payment_method' => $parsedData['payment_method'] ?? 'Cash', 
                'type'           => 'expense',
                'is_ocr'         => true
            ]
        ], 200);
    }

    return response()->json([
        'success' => false,
        'message' => 'Gagal membaca struk',
        'error'   => $response->json()
    ], 500);
}
}
