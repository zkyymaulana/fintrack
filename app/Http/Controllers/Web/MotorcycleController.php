<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Motorcycle;
use App\Services\MotorcycleMaintenanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class MotorcycleController extends Controller
{
    public function index(Request $request): Response
    {
        $motorcycles = $request->user()->motorcycles()->orderBy('name')->get();
        $motorcycles = $motorcycles->map(function (Motorcycle $motorcycle): array {
            $items = $motorcycle->maintenanceItems()
                ->where('is_active', true)
                ->withMax('records', 'odometer_km')
                ->get();
            $nextDue = $items->map(fn ($item) => ($item->records_max_odometer_km ?? $item->baseline_odometer_km) + $item->interval_km)->sort()->first();

            return [
                ...$motorcycle->only(['id', 'name', 'brand', 'model', 'license_plate', 'current_odometer_km']),
                'active_maintenance_count' => $items->count(),
                'next_due_km' => $nextDue,
                'km_until_next_service' => $nextDue === null ? null : $nextDue - $motorcycle->current_odometer_km,
            ];
        });

        return Inertia::render('Motorcycles/Index', ['motorcycles' => $motorcycles]);
    }

    public function create(): Response
    {
        return Inertia::render('Motorcycles/Form', ['motorcycle' => null]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate($this->rules(true));
        $motorcycle = DB::transaction(function () use ($request, $data): Motorcycle {
            $motorcycle = $request->user()->motorcycles()->create($data);
            $motorcycle->odometerReadings()->create([
                'odometer_km' => $motorcycle->current_odometer_km,
                'recorded_at' => now(),
                'source' => 'initial',
                'note' => 'Odometer awal',
            ]);

            return $motorcycle;
        });

        return redirect()->route('motorcycles.show', $motorcycle)->with('success', 'Motor berhasil ditambahkan.');
    }

    public function show(Request $request, string $motorcycle): Response
    {
        $motorcycle = $request->user()->motorcycles()->findOrFail($motorcycle);
        $items = $motorcycle->maintenanceItems()
            ->withMax('records', 'odometer_km')
            ->orderBy('name')
            ->get()
            ->map(function ($item) use ($motorcycle): array {
                $lastServiced = (int) ($item->records_max_odometer_km ?? $item->baseline_odometer_km);
                $nextDue = $lastServiced + $item->interval_km;

                return [
                    ...$item->only(['id', 'name', 'kind', 'interval_km', 'baseline_odometer_km', 'note', 'is_active']),
                    'last_serviced_km' => $lastServiced,
                    'next_due_km' => $nextDue,
                    'km_remaining' => $nextDue - $motorcycle->current_odometer_km,
                    'status' => $motorcycle->current_odometer_km >= $nextDue
                        ? 'due'
                        : ($nextDue - $motorcycle->current_odometer_km <= 200 ? 'soon' : 'ok'),
                ];
            });
        $records = $motorcycle->maintenanceRecords()
            ->with('transaction.wallet')
            ->orderByDesc('performed_on')
            ->orderByDesc('id')
            ->limit(100)
            ->get()
            ->map(fn ($record): array => [
                ...$record->only(['id', 'title', 'odometer_km', 'performed_on', 'cost', 'note']),
                'cost' => $record->transaction?->amount ?? $record->cost,
                'wallet_name' => $record->transaction?->wallet?->name,
            ]);
        $readings = $motorcycle->odometerReadings()
            ->orderByDesc('recorded_at')
            ->limit(10)
            ->get(['id', 'odometer_km', 'recorded_at', 'source', 'note']);

        return Inertia::render('Motorcycles/Show', [
            'motorcycle' => $motorcycle->only(['id', 'name', 'brand', 'model', 'license_plate', 'current_odometer_km']),
            'maintenanceItems' => $items,
            'maintenanceRecords' => $records,
            'odometerReadings' => $readings,
        ]);
    }

    public function edit(Request $request, string $motorcycle): Response
    {
        return Inertia::render('Motorcycles/Form', [
            'motorcycle' => $request->user()->motorcycles()->findOrFail($motorcycle)
                ->only(['id', 'name', 'brand', 'model', 'license_plate', 'current_odometer_km']),
        ]);
    }

    public function update(Request $request, string $motorcycle): RedirectResponse
    {
        $ownedMotorcycle = $request->user()->motorcycles()->findOrFail($motorcycle);
        $ownedMotorcycle->update($request->validate($this->rules(false)));

        return redirect()->route('motorcycles.show', $ownedMotorcycle)->with('success', 'Data motor berhasil diperbarui.');
    }

    public function destroy(Request $request, string $motorcycle): RedirectResponse
    {
        $request->user()->motorcycles()->findOrFail($motorcycle)->delete();

        return redirect()->route('motorcycles.index')->with('success', 'Motor diarsipkan. Riwayat servis tetap tersimpan.');
    }

    public function updateOdometer(Request $request, string $motorcycle, MotorcycleMaintenanceService $maintenance): RedirectResponse
    {
        $data = $request->validate([
            'odometer_km' => ['required', 'integer', 'min:0', 'max:2000000'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);
        $ownedMotorcycle = $request->user()->motorcycles()->findOrFail($motorcycle);
        $maintenance->recordOdometer($request->user(), $ownedMotorcycle, (int) $data['odometer_km'], $data['note'] ?? null);

        return redirect()->route('motorcycles.show', $ownedMotorcycle)->with('success', 'Odometer berhasil diperbarui.');
    }

    private function rules(bool $creating): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'brand' => ['nullable', 'string', 'max:100'],
            'model' => ['nullable', 'string', 'max:100'],
            'license_plate' => ['nullable', 'string', 'max:20'],
            'current_odometer_km' => $creating
                ? ['required', 'integer', 'min:0', 'max:2000000']
                : ['prohibited'],
        ];
    }
}
