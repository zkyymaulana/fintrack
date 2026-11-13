<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Wallet;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class WalletController extends Controller
{
    public function index(Request $request): Response
    {
        $wallets = $request->user()->wallets()->orderBy('name')->get([
            'id', 'name', 'wallet_type', 'balance', 'auto_deduction_enabled', 'auto_deduction_amount',
            'auto_deduction_frequency', 'auto_deduction_next_run_on', 'auto_deduction_last_status',
        ]);

        return Inertia::render('Wallets/Index', [
            'wallets' => $wallets,
            'totalBalance' => (string) $request->user()->wallets()->sum('balance'),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Wallets/Form', [
            'wallet' => null,
            'categories' => Category::where('type', 'expense')->orderBy('name')->get(['id', 'name', 'type']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->scheduleData($request->validate($this->rules($request)));
        $data['wallet_type'] ??= 'regular';
        $request->user()->wallets()->create($data);

        return redirect()->route('wallets.index')->with('success', 'Dompet berhasil ditambahkan.');
    }

    public function edit(Request $request, string $wallet): Response
    {
        return Inertia::render('Wallets/Form', [
            'wallet' => $request->user()->wallets()->findOrFail($wallet)->only([
                'id', 'name', 'wallet_type', 'balance', 'auto_deduction_enabled', 'auto_deduction_amount',
                'auto_deduction_category_id', 'auto_deduction_title', 'auto_deduction_note',
                'auto_deduction_frequency', 'auto_deduction_next_run_on', 'auto_deduction_last_run_on',
                'auto_deduction_last_status',
            ]),
            'categories' => Category::where('type', 'expense')->orderBy('name')->get(['id', 'name', 'type']),
        ]);
    }

    public function update(Request $request, string $wallet): RedirectResponse
    {
        $ownedWallet = $request->user()->wallets()->findOrFail($wallet);
        $ownedWallet->update($this->scheduleData($request->validate($this->rules($request)), $ownedWallet));

        return redirect()->route('wallets.index')->with('success', 'Dompet berhasil diperbarui.');
    }

    public function destroySchedule(Request $request, string $wallet): RedirectResponse
    {
        $ownedWallet = $request->user()->wallets()->findOrFail($wallet);
        $ownedWallet->update([
            'auto_deduction_enabled' => false,
            'auto_deduction_amount' => null,
            'auto_deduction_category_id' => null,
            'auto_deduction_title' => null,
            'auto_deduction_note' => null,
            'auto_deduction_frequency' => null,
            'auto_deduction_next_run_on' => null,
            'auto_deduction_day_of_month' => null,
            'auto_deduction_last_run_on' => null,
            'auto_deduction_last_status' => null,
        ]);

        return redirect()->route('wallets.index')->with('success', 'Jadwal pengeluaran otomatis dibatalkan.');
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

    private function rules(Request $request): array
    {
        $scheduleEnabled = $request->boolean('auto_deduction_enabled');

        return [
            'name' => ['required', 'string', 'max:255'],
            'wallet_type' => ['sometimes', 'required', Rule::in(['regular', 'investment'])],
            'balance' => ['required', 'numeric', 'min:0', 'decimal:0,2', 'max:9999999999999.99'],
            'auto_deduction_enabled' => ['sometimes', 'boolean'],
            'auto_deduction_amount' => [Rule::requiredIf($scheduleEnabled), 'nullable', 'numeric', 'gt:0', 'decimal:0,2', 'max:9999999999999.99'],
            'auto_deduction_category_id' => [Rule::requiredIf($scheduleEnabled), 'nullable', 'integer', Rule::exists('categories', 'id')->where('type', 'expense')],
            'auto_deduction_title' => [Rule::requiredIf($scheduleEnabled), 'nullable', 'string', 'max:255'],
            'auto_deduction_note' => ['nullable', 'string', 'max:5000'],
            'auto_deduction_frequency' => [Rule::requiredIf($scheduleEnabled), 'nullable', Rule::in(['daily', 'weekly', 'monthly'])],
            'auto_deduction_next_run_on' => [Rule::requiredIf($scheduleEnabled), 'nullable', 'date_format:Y-m-d'],
        ];
    }

    private function scheduleData(array $data, ?Wallet $existing = null): array
    {
        $enabled = (bool) ($data['auto_deduction_enabled'] ?? false);
        $data['auto_deduction_enabled'] = $enabled;

        if (! $enabled) {
            foreach ([
                'auto_deduction_amount', 'auto_deduction_category_id', 'auto_deduction_title',
                'auto_deduction_note', 'auto_deduction_frequency', 'auto_deduction_next_run_on',
                'auto_deduction_day_of_month', 'auto_deduction_last_run_on', 'auto_deduction_last_status',
            ] as $field) {
                $data[$field] = null;
            }

            return $data;
        }

        $nextRunOn = $data['auto_deduction_next_run_on'];
        $keepMonthlyAnchor = $existing
            && $existing->auto_deduction_frequency === 'monthly'
            && $data['auto_deduction_frequency'] === 'monthly'
            && $existing->auto_deduction_next_run_on === $nextRunOn;
        $data['auto_deduction_day_of_month'] = $data['auto_deduction_frequency'] === 'monthly'
            ? ($keepMonthlyAnchor ? $existing->auto_deduction_day_of_month : CarbonImmutable::parse($nextRunOn)->day)
            : null;
        $data['auto_deduction_last_run_on'] = null;
        $data['auto_deduction_last_status'] = null;

        return $data;
    }
}
