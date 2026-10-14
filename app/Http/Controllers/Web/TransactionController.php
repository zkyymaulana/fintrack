<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Services\WebTransactionService;
use App\Support\TransactionData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class TransactionController extends Controller
{
    public function index(Request $request): Response
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

        return Inertia::render('Transactions/Index', [
            'transactions' => $query->orderByDesc('date')->orderByDesc('id')->paginate(15)->withQueryString()->through(fn ($transaction) => TransactionData::from($transaction)),
            'filters' => $filters,
            'wallets' => $request->user()->wallets()->orderBy('name')->get(['id', 'name']),
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
            'wallets' => $request->user()->wallets()->orderBy('name')->get(['id', 'name']),
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
            'category_id' => ['required', Rule::exists('categories', 'id')->where('type', $categoryType)],
            'wallet_id' => ['required', $walletRule],
            'to_wallet_id' => [$type === 'transfer' ? 'required' : 'prohibited', 'nullable', Rule::exists('wallets', 'id')->where('user_id', $request->user()->id)->whereNull('deleted_at'), 'different:wallet_id'],
        ];
    }

    private function data(Request $request): array
    {
        $data = $request->validate($this->rules($request));
        $data['to_wallet_id'] = $data['type'] === 'transfer' ? $data['to_wallet_id'] : null;

        return $data;
    }
}
