<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Services\MotorcycleMaintenanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class MotorcycleMaintenanceRecordController extends Controller
{
    public function create(Request $request, string $motorcycle): Response
    {
        $ownedMotorcycle = $request->user()->motorcycles()->findOrFail($motorcycle);
        $selectedItem = $request->integer('item');

        if ($selectedItem) {
            $ownedMotorcycle->maintenanceItems()->where('is_active', true)->findOrFail($selectedItem);
        }

        return Inertia::render('Motorcycles/MaintenanceRecordForm', [
            'motorcycle' => $ownedMotorcycle->only(['id', 'name', 'current_odometer_km']),
            'maintenanceItems' => $ownedMotorcycle->maintenanceItems()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'kind']),
            'selectedItemId' => $selectedItem ?: null,
            'wallets' => $request->user()->wallets()->orderBy('name')->get(['id', 'name']),
            'categories' => Category::where('type', 'expense')->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request, string $motorcycle, MotorcycleMaintenanceService $maintenance): RedirectResponse
    {
        $ownedMotorcycle = $request->user()->motorcycles()->findOrFail($motorcycle);
        $walletSelected = $request->filled('wallet_id');
        $hasMaintenanceItem = $request->filled('maintenance_item_id');
        $costRules = [$walletSelected ? 'required' : 'nullable', 'numeric', $walletSelected ? 'gt:0' : 'min:0', 'decimal:0,2', 'max:9999999999999.99'];

        $data = $request->validate([
            'maintenance_item_id' => [
                'nullable', 'integer',
                Rule::exists('motorcycle_maintenance_items', 'id')->where('motorcycle_id', $ownedMotorcycle->id)->where('is_active', true)->whereNull('deleted_at'),
            ],
            'title' => [$hasMaintenanceItem ? 'nullable' : 'required', 'string', 'max:255'],
            'odometer_km' => ['required', 'integer', 'min:0', 'max:2000000'],
            'performed_on' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'cost' => $costRules,
            'note' => ['nullable', 'string', 'max:5000'],
            'wallet_id' => [
                'nullable', 'integer',
                Rule::exists('wallets', 'id')->where('user_id', $request->user()->id)->whereNull('deleted_at'),
            ],
            'category_id' => [
                Rule::requiredIf($walletSelected), 'nullable', 'integer',
                Rule::exists('categories', 'id')->where('type', 'expense'),
            ],
        ]);

        $maintenance->recordMaintenance($request->user(), $ownedMotorcycle, $data);

        return redirect()->route('motorcycles.show', $ownedMotorcycle)->with('success', 'Perawatan motor berhasil dicatat.');
    }
}
