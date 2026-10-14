<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class WalletController extends Controller
{
    public function index(Request $request): Response
    {
        $wallets = $request->user()->wallets()->orderBy('name')->get(['id', 'name', 'balance']);

        return Inertia::render('Wallets/Index', [
            'wallets' => $wallets,
            'totalBalance' => (string) $request->user()->wallets()->sum('balance'),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Wallets/Form', ['wallet' => null]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate($this->rules());
        $request->user()->wallets()->create($data);

        return redirect()->route('wallets.index')->with('success', 'Dompet berhasil ditambahkan.');
    }

    public function edit(Request $request, string $wallet): Response
    {
        return Inertia::render('Wallets/Form', [
            'wallet' => $request->user()->wallets()->findOrFail($wallet)->only(['id', 'name', 'balance']),
        ]);
    }

    public function update(Request $request, string $wallet): RedirectResponse
    {
        $ownedWallet = $request->user()->wallets()->findOrFail($wallet);
        $ownedWallet->update($request->validate($this->rules()));

        return redirect()->route('wallets.index')->with('success', 'Dompet berhasil diperbarui.');
    }

    public function destroy(Request $request, string $wallet): RedirectResponse
    {
        $ownedWallet = $request->user()->wallets()->findOrFail($wallet);
        $used = $request->user()->transactions()
            ->where(fn ($query) => $query->where('wallet_id', $ownedWallet->id)->orWhere('to_wallet_id', $ownedWallet->id))
            ->exists();

        if ($used) {
            throw ValidationException::withMessages(['wallet' => 'Dompet yang pernah digunakan dalam transaksi tidak dapat dihapus.']);
        }

        $ownedWallet->delete();

        return redirect()->route('wallets.index')->with('success', 'Dompet berhasil dihapus.');
    }

    private function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'balance' => ['required', 'numeric', 'min:0', 'decimal:0,2', 'max:9999999999999.99'],
        ];
    }
}
