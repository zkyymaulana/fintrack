<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Wallet;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class WalletScheduleProcessor
{
    public function processDue(): int
    {
        $walletIds = Wallet::query()
            ->where('auto_deduction_enabled', true)
            ->whereNotNull('auto_deduction_next_run_on')
            ->whereDate('auto_deduction_next_run_on', '<=', CarbonImmutable::today()->toDateString())
            ->orderBy('id')
            ->pluck('id');

        $processed = 0;
        foreach ($walletIds as $walletId) {
            $processed += DB::transaction(function () use ($walletId): int {
                $wallet = Wallet::query()->lockForUpdate()->find($walletId);
                if (! $wallet || ! $wallet->auto_deduction_enabled || ! $wallet->auto_deduction_next_run_on) {
                    return 0;
                }

                $today = CarbonImmutable::today();
                $dueOn = CarbonImmutable::parse($wallet->auto_deduction_next_run_on);
                $processed = 0;

                while ($dueOn->lessThanOrEqualTo($today) && $processed < 366) {
                    $amount = (string) $wallet->auto_deduction_amount;
                    $isTransfer = $wallet->auto_deduction_type === 'transfer';
                    $destination = null;
                    $categoryId = $wallet->auto_deduction_category_id;

                    if ($isTransfer) {
                        $categoryId = Category::where('type', 'expense')->orderBy('id')->value('id');
                        if ($wallet->auto_deduction_to_wallet_id && (int) $wallet->auto_deduction_to_wallet_id !== (int) $wallet->id) {
                            $destination = Wallet::query()
                                ->where('user_id', $wallet->user_id)
                                ->whereKey($wallet->auto_deduction_to_wallet_id)
                                ->lockForUpdate()
                                ->first();
                        }
                    }

                    if (! $categoryId) {
                        $wallet->auto_deduction_last_status = 'category_missing';
                    } elseif ($isTransfer && ! $destination) {
                        $wallet->auto_deduction_last_status = 'destination_missing';
                    } elseif (bccomp((string) $wallet->balance, $amount, 2) < 0) {
                        $wallet->auto_deduction_last_status = 'insufficient_balance';
                    } else {
                        $wallet->user->transactions()->create([
                            'category_id' => $categoryId,
                            'wallet_id' => $wallet->id,
                            'to_wallet_id' => $isTransfer ? $destination->id : null,
                            'title' => $wallet->auto_deduction_title,
                            'amount' => $amount,
                            'admin_fee' => '0.00',
                            'type' => $isTransfer ? 'transfer' : 'expense',
                            'date' => $dueOn->toDateString(),
                            'note' => $wallet->auto_deduction_note ?: ($isTransfer ? 'Transfer otomatis terjadwal.' : 'Pengeluaran otomatis terjadwal.'),
                            'is_ocr' => false,
                        ]);

                        $wallet->balance = bcsub((string) $wallet->balance, $amount, 2);
                        if ($isTransfer) {
                            $destination->balance = bcadd((string) $destination->balance, $amount, 2);
                            $destination->save();
                        }
                        $wallet->auto_deduction_last_status = 'charged';
                    }

                    $wallet->auto_deduction_last_run_on = $dueOn->toDateString();
                    $dueOn = $this->nextDate($dueOn, $wallet->auto_deduction_frequency, $wallet->auto_deduction_day_of_month);
                    $wallet->auto_deduction_next_run_on = $dueOn->toDateString();
                    $wallet->save();
                    $processed++;
                }

                return $processed;
            });
        }

        return $processed;
    }

    private function nextDate(CarbonImmutable $current, string $frequency, ?int $dayOfMonth): CarbonImmutable
    {
        return match ($frequency) {
            'daily' => $current->addDay(),
            'weekly' => $current->addWeek(),
            'monthly' => $this->nextMonthlyDate($current, $dayOfMonth ?? $current->day),
            default => $current->addDay(),
        };
    }

    private function nextMonthlyDate(CarbonImmutable $current, int $dayOfMonth): CarbonImmutable
    {
        $nextMonth = $current->addMonthNoOverflow()->startOfMonth();

        return $nextMonth->addDays(min($dayOfMonth, $nextMonth->daysInMonth) - 1);
    }
}
