<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Services\WebTransactionService;
use App\Support\TransactionData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class TransactionController extends Controller
{
    public function index(Request $request): Response|JsonResponse
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'type' => ['nullable', Rule::in(['income', 'expense', 'transfer'])],
            'month' => ['nullable', 'date_format:Y-m'],
            'wallet_id' => ['nullable', 'integer'],
            'category_id' => ['nullable', 'integer'],
        ]);
        $query = $request->user()->transactions()->with(['category:id,name', 'wallet:id,name', 'toWallet:id,name']);
        if ($search = $filters['search'] ?? null) {
            $query->where(fn ($q) => $q->where('title', 'like', '%'.$search.'%')->orWhere('note', 'like', '%'.$search.'%'));
        }
        if ($type = $filters['type'] ?? null) {
            $query->where('type', $type);
        }
        if ($month = $filters['month'] ?? null) {
            $query->whereBetween('date', [$month.'-01', date('Y-m-t', strtotime($month.'-01'))]);
        }
        if ($wallet = $filters['wallet_id'] ?? null) {
            $query->where(fn ($q) => $q->where('wallet_id', $wallet)->orWhere('to_wallet_id', $wallet));
        }
        if ($category = $filters['category_id'] ?? null) {
            $query->where('category_id', $category);
        }

        $transactions = $query->orderByDesc('date')->orderByDesc('id')->paginate(15)->withQueryString()->through(fn ($transaction) => TransactionData::from($transaction));
        if ($request->expectsJson()) {
            return response()->json([
                'data' => $transactions->items(),
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
                'total' => $transactions->total(),
            ]);
        }

        return Inertia::render('Transactions/Index', [
            'transactions' => $transactions,
            'filters' => $filters,
            'wallets' => $request->user()->wallets()->orderBy('name')->get(['id', 'name', 'wallet_type']),
            'categories' => Category::orderBy('name')->get(['id', 'name', 'type']),
        ]);
    }

    public function create(Request $request): Response
    {
        return $this->form($request);
    }

    public function store(Request $request, WebTransactionService $service): RedirectResponse
    {
        $service->save($request->user(), $this->data($request));

        return redirect()->route('transactions.index')->with('success', 'Transaksi berhasil ditambahkan.');
    }

    public function scan(Request $request)
    {
        $validated = $request->validate([
            'receipt_image' => ['required', 'image', 'mimes:jpeg,png,jpg', 'max:4096'],
        ]);
        $image = $validated['receipt_image'];
        $apiKey = config('services.gemini.key') ?: env('GEMINI_API_KEY');
        if (! $apiKey) {
            throw ValidationException::withMessages(['receipt_image' => 'Pemindaian struk belum dikonfigurasi.']);
        }

        $response = Http::timeout(60)->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key={$apiKey}", [
            'contents' => [[
                'parts' => [
                    ['text' => 'Analisis gambar struk belanja ini. Ekstrak informasi dan kembalikan HANYA JSON valid dengan key: "title" (nama merchant diawali "Belanja di "), "amount" (total akhir dibayar sebagai number), "admin_fee" (biaya pajak/admin atau 0), "date" (YYYY-MM-DD), "payment_method" (satu kata seperti BCA, OVO, Cash), dan "note" (daftar barang, satu item per baris dengan format "- [Nama] (x[Jumlah]) : [Harga]").'],
                    ['inlineData' => ['mimeType' => $image->getMimeType() ?: 'image/jpeg', 'data' => base64_encode(file_get_contents($image->getRealPath()))]],
                ],
            ]],
        ]);

        if (! $response->successful()) {
            throw ValidationException::withMessages(['receipt_image' => 'Struk tidak dapat dibaca. Coba unggah gambar yang lebih jelas.']);
        }

        $raw = $response->json('candidates.0.content.parts.0.text', '{}');
        $parsed = json_decode(trim(preg_replace('/```(?:json)?|```/', '', $raw)), true);
        if (! is_array($parsed)) {
            throw ValidationException::withMessages(['receipt_image' => 'Hasil pembacaan struk tidak valid. Coba lagi.']);
        }

        return response()->json(['data' => [
            'title' => $parsed['title'] ?? 'Scan Struk Baru',
            'amount' => (string) ($parsed['amount'] ?? 0),
            'admin_fee' => (string) ($parsed['admin_fee'] ?? 0),
            'date' => $parsed['date'] ?? now()->format('Y-m-d'),
            'note' => $parsed['note'] ?? '',
            'payment_method' => $parsed['payment_method'] ?? 'Cash',
            'type' => 'expense',
            'is_ocr' => true,
        ]]);
    }

    public function edit(Request $request, string $transaction): Response
    {
        $owned = $request->user()->transactions()->with(['category:id,name', 'wallet:id,name', 'toWallet:id,name'])->findOrFail($transaction);

        return $this->form($request, TransactionData::from($owned));
    }

    public function update(Request $request, string $transaction, WebTransactionService $service): RedirectResponse
    {
        $owned = $request->user()->transactions()->findOrFail($transaction);
        $service->save($request->user(), $this->data($request), $owned);

        return redirect()->route('transactions.index')->with('success', 'Transaksi berhasil diperbarui.');
    }

    public function destroy(Request $request, string $transaction, WebTransactionService $service): RedirectResponse
    {
        $owned = $request->user()->transactions()->findOrFail($transaction);
        $service->delete($request->user(), $owned);

        return redirect()->route('transactions.index')->with('success', 'Transaksi berhasil dihapus.');
    }

    private function form(Request $request, ?array $transaction = null): Response
    {
        return Inertia::render('Transactions/Form', [
            'transaction' => $transaction,
            'wallets' => $request->user()->wallets()->orderBy('name')->get(['id', 'name', 'wallet_type']),
            'categories' => Category::orderBy('name')->get(['id', 'name', 'type']),
        ]);
    }

    private function rules(Request $request): array
    {
        $type = $request->input('type');
        $categoryType = $type === 'income' ? 'income' : 'expense';
        $walletRule = Rule::exists('wallets', 'id')->where('user_id', $request->user()->id)->whereNull('deleted_at');

        return [
            'type' => ['required', Rule::in(['income', 'expense', 'transfer'])],
            'title' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01', 'decimal:0,2', 'max:9999999999999.99'],
            'admin_fee' => ['required', 'numeric', 'min:0', 'decimal:0,2', 'max:9999999999999.99'],
            'date' => ['required', 'date_format:Y-m-d'],
            'note' => ['nullable', 'string', 'max:5000'],
            'category_id' => [$type === 'transfer' ? 'nullable' : 'required', 'nullable', Rule::exists('categories', 'id')->where('type', $categoryType)],
            'wallet_id' => ['required', $walletRule],
            'to_wallet_id' => [$type === 'transfer' ? 'required' : 'prohibited', 'nullable', Rule::exists('wallets', 'id')->where('user_id', $request->user()->id)->whereNull('deleted_at'), 'different:wallet_id'],
            'receipt_image' => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
            'remove_receipt_image' => ['nullable', 'boolean'],
            'is_ocr' => ['nullable', 'boolean'],
        ];
    }

    private function data(Request $request): array
    {
        $data = $request->validate($this->rules($request));
        if ($data['type'] === 'transfer') {
            unset($data['category_id']);
            $data['category_id'] = Category::where('type', 'expense')->orderBy('id')->value('id');
            abort_if(! $data['category_id'], 422, 'Kategori pengeluaran diperlukan untuk menyimpan transaksi transfer.');
        }
        $data['to_wallet_id'] = $data['type'] === 'transfer' ? $data['to_wallet_id'] : null;
        $data['is_ocr'] = $data['is_ocr'] ?? false;

        return $data;
    }
}
