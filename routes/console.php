<?php

use App\Services\WalletScheduleProcessor;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('wallet-schedules:process', function (): void {
    $this->info('Processed '.app(WalletScheduleProcessor::class)->processDue().' scheduled wallet deduction(s).');
})->purpose('Process due automatic wallet deductions');

Schedule::command('wallet-schedules:process')->everyMinute()->withoutOverlapping(5);
