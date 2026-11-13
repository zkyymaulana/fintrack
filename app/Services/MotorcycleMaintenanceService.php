<?php

namespace App\Services;

use App\Models\Motorcycle;
use App\Models\MotorcycleMaintenanceRecord;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MotorcycleMaintenanceService
{
    public function __construct(private readonly WebTransactionService $transactions) {}

    public function recordOdometer(User $user, Motorcycle $motorcycle, int $odometerKm, ?string $note = null): void
    {
        DB::transaction(function () use ($user, $motorcycle, $odometerKm, $note): void {
            $motorcycle = $user->motorcycles()->lockForUpdate()->findOrFail($motorcycle->id);

            if ($odometerKm < $motorcycle->current_odometer_km) {
                throw ValidationException::withMessages([
                    'odometer_km' => 'Kilometer odometer tidak boleh lebih rendah dari catatan sebelumnya.',
                ]);
            }

            if ($odometerKm === $motorcycle->current_odometer_km) {
                return;
            }

            $motorcycle->current_odometer_km = $odometerKm;
            $motorcycle->save();

            $motorcycle->odometerReadings()->create([
                'odometer_km' => $odometerKm,
                'recorded_at' => now(),
                'source' => 'manual',
                'note' => $note,
            ]);
        });
    }

    public function recordMaintenance(User $user, Motorcycle $motorcycle, array $data): MotorcycleMaintenanceRecord
    {
        return DB::transaction(function () use ($user, $motorcycle, $data): MotorcycleMaintenanceRecord {
            $motorcycle = $user->motorcycles()->lockForUpdate()->findOrFail($motorcycle->id);
            $item = isset($data['maintenance_item_id'])
                ? $motorcycle->maintenanceItems()->lockForUpdate()->findOrFail($data['maintenance_item_id'])
                : null;
            $title = $item?->name ?? $data['title'];
            $odometerKm = (int) $data['odometer_km'];
            $performedOn = CarbonImmutable::parse($data['performed_on']);

            $record = $motorcycle->maintenanceRecords()->create([
                'maintenance_item_id' => $item?->id,
                'title' => $title,
                'odometer_km' => $odometerKm,
                'performed_on' => $performedOn->toDateString(),
                'cost' => $data['cost'] ?? null,
                'note' => $data['note'] ?? null,
            ]);

            if ($odometerKm > $motorcycle->current_odometer_km) {
                $motorcycle->current_odometer_km = $odometerKm;
                $motorcycle->save();
            }

            $motorcycle->odometerReadings()->create([
                'odometer_km' => $odometerKm,
                'recorded_at' => $performedOn->startOfDay(),
                'source' => 'maintenance',
                'note' => $title,
            ]);

            if (! empty($data['wallet_id'])) {
                $transaction = $this->transactions->save($user, [
                    'category_id' => $data['category_id'],
                    'wallet_id' => $data['wallet_id'],
                    'to_wallet_id' => null,
                    'title' => $title,
                    'amount' => (string) $data['cost'],
                    'admin_fee' => '0.00',
                    'type' => 'expense',
                    'date' => $performedOn->toDateString(),
                    'note' => ($data['note'] ?? null) ?: 'Perawatan motor: '.$motorcycle->name,
                    'is_ocr' => false,
                ]);
                $record->transaction()->associate($transaction);
                $record->save();
            }

            return $record;
        });
    }
}
