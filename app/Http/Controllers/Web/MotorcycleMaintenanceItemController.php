<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Motorcycle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class MotorcycleMaintenanceItemController extends Controller
{
    public function create(Request $request, string $motorcycle): Response
    {
        $ownedMotorcycle = $request->user()->motorcycles()->findOrFail($motorcycle);

        return Inertia::render('Motorcycles/MaintenanceItemForm', [
            'motorcycle' => $ownedMotorcycle->only(['id', 'name', 'current_odometer_km']),
            'item' => null,
        ]);
    }

    public function store(Request $request, string $motorcycle): RedirectResponse
    {
        $ownedMotorcycle = $request->user()->motorcycles()->findOrFail($motorcycle);
        $ownedMotorcycle->maintenanceItems()->create($request->validate($this->rules($ownedMotorcycle)));

        return redirect()->route('motorcycles.show', $ownedMotorcycle)->with('success', 'Jadwal perawatan berhasil ditambahkan.');
    }

    public function edit(Request $request, string $motorcycle, string $item): Response
    {
        $ownedMotorcycle = $request->user()->motorcycles()->findOrFail($motorcycle);
        $maintenanceItem = $ownedMotorcycle->maintenanceItems()->findOrFail($item);

        return Inertia::render('Motorcycles/MaintenanceItemForm', [
            'motorcycle' => $ownedMotorcycle->only(['id', 'name', 'current_odometer_km']),
            'item' => $maintenanceItem->only(['id', 'name', 'kind', 'interval_km', 'baseline_odometer_km', 'note', 'is_active']),
        ]);
    }

    public function update(Request $request, string $motorcycle, string $item): RedirectResponse
    {
        $ownedMotorcycle = $request->user()->motorcycles()->findOrFail($motorcycle);
        $maintenanceItem = $ownedMotorcycle->maintenanceItems()->findOrFail($item);
        $maintenanceItem->update($request->validate($this->rules($ownedMotorcycle)));

        return redirect()->route('motorcycles.show', $ownedMotorcycle)->with('success', 'Jadwal perawatan berhasil diperbarui.');
    }

    public function destroy(Request $request, string $motorcycle, string $item): RedirectResponse
    {
        $ownedMotorcycle = $request->user()->motorcycles()->findOrFail($motorcycle);
        $ownedMotorcycle->maintenanceItems()->findOrFail($item)->delete();

        return redirect()->route('motorcycles.show', $ownedMotorcycle)->with('success', 'Jadwal dihapus. Riwayat servis sebelumnya tetap tersimpan.');
    }

    private function rules(Motorcycle $motorcycle): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'kind' => ['required', Rule::in(['part', 'service'])],
            'interval_km' => ['required', 'integer', 'min:1', 'max:2000000'],
            'baseline_odometer_km' => ['required', 'integer', 'min:0', 'max:'.$motorcycle->current_odometer_km],
            'note' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
