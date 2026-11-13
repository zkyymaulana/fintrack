<?php

use App\Models\Category;
use App\Models\User;
use App\Models\Wallet;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->category = Category::create(['name' => 'Tagihan', 'type' => 'expense']);
    $this->wallet = $this->user->wallets()->create(['name' => 'Rekening', 'balance' => '1000.00']);
});

afterEach(function () {
    CarbonImmutable::setTestNow();
});

test('wallet schedule is disabled by default and can be configured from its form', function () {
    expect($this->wallet->fresh()->auto_deduction_enabled)->toBeFalse();

    $this->withoutVite()->actingAs($this->user, 'web')->get('/wallets/'.$this->wallet->id.'/edit')
        ->assertInertia(fn (Assert $page) => $page
            ->where('wallet.auto_deduction_enabled', false)
            ->has('categories', 1));

    $this->post('/wallets', [
        'name' => 'Dompet baru',
        'wallet_type' => 'regular',
        'balance' => '2500',
        'auto_deduction_enabled' => true,
        'auto_deduction_amount' => '250',
        'auto_deduction_category_id' => (string) $this->category->id,
        'auto_deduction_title' => 'Tagihan internet',
        'auto_deduction_note' => 'Internet rumah',
        'auto_deduction_frequency' => 'monthly',
        'auto_deduction_next_run_on' => '2026-11-20',
    ])->assertRedirect('/wallets');

    $scheduled = Wallet::where('name', 'Dompet baru')->firstOrFail();
    expect($scheduled->auto_deduction_enabled)->toBeTrue()
        ->and(bccomp((string) $scheduled->auto_deduction_amount, '250.00', 2))->toBe(0)
        ->and($scheduled->auto_deduction_day_of_month)->toBe(20)
        ->and($scheduled->auto_deduction_next_run_on)->toBe('2026-11-20');
});

test('due schedule creates one expense and subtracts its amount exactly once', function () {
    CarbonImmutable::setTestNow('2026-11-13 12:00:00');
    $this->wallet->update([
        'auto_deduction_enabled' => true,
        'auto_deduction_amount' => '125.50',
        'auto_deduction_category_id' => $this->category->id,
        'auto_deduction_title' => 'Langganan musik',
        'auto_deduction_note' => 'Paket bulanan',
        'auto_deduction_frequency' => 'weekly',
        'auto_deduction_next_run_on' => '2026-11-13',
    ]);

    Artisan::call('wallet-schedules:process');
    Artisan::call('wallet-schedules:process');

    expect(bccomp((string) $this->wallet->fresh()->balance, '874.50', 2))->toBe(0)
        ->and($this->wallet->fresh()->auto_deduction_next_run_on)->toBe('2026-11-20')
        ->and($this->wallet->fresh()->auto_deduction_last_status)->toBe('charged')
        ->and($this->user->transactions()->count())->toBe(1);

    $transaction = $this->user->transactions()->firstOrFail();
    expect($transaction->type)->toBe('expense')
        ->and($transaction->title)->toBe('Langganan musik')
        ->and($transaction->note)->toBe('Paket bulanan')
        ->and(bccomp((string) $transaction->amount, '125.50', 2))->toBe(0)
        ->and($transaction->date)->toBe('2026-11-13');
});

test('insufficient balance skips the scheduled expense without making the wallet negative', function () {
    CarbonImmutable::setTestNow('2026-11-13 12:00:00');
    $this->wallet->update([
        'auto_deduction_enabled' => true,
        'auto_deduction_amount' => '1200',
        'auto_deduction_category_id' => $this->category->id,
        'auto_deduction_title' => 'Tagihan bulanan',
        'auto_deduction_frequency' => 'daily',
        'auto_deduction_next_run_on' => '2026-11-13',
    ]);

    Artisan::call('wallet-schedules:process');

    expect(bccomp((string) $this->wallet->fresh()->balance, '1000.00', 2))->toBe(0)
        ->and($this->wallet->fresh()->auto_deduction_next_run_on)->toBe('2026-11-14')
        ->and($this->wallet->fresh()->auto_deduction_last_status)->toBe('insufficient_balance')
        ->and($this->user->transactions()->count())->toBe(0);
});

test('monthly schedule keeps its chosen day through shorter months', function () {
    CarbonImmutable::setTestNow('2026-03-31 12:00:00');
    $this->wallet->update([
        'balance' => '1000',
        'auto_deduction_enabled' => true,
        'auto_deduction_amount' => '100',
        'auto_deduction_category_id' => $this->category->id,
        'auto_deduction_title' => 'Iuran rutin',
        'auto_deduction_frequency' => 'monthly',
        'auto_deduction_next_run_on' => '2026-01-31',
        'auto_deduction_day_of_month' => 31,
    ]);

    Artisan::call('wallet-schedules:process');

    expect($this->user->transactions()->orderBy('date')->pluck('date')->all())
        ->toBe(['2026-01-31', '2026-02-28', '2026-03-31'])
        ->and(bccomp((string) $this->wallet->fresh()->balance, '700.00', 2))->toBe(0)
        ->and($this->wallet->fresh()->auto_deduction_next_run_on)->toBe('2026-04-30');
});

test('schedule can be edited and canceled without deleting its wallet or past transactions', function () {
    $this->wallet->update([
        'auto_deduction_enabled' => true,
        'auto_deduction_amount' => '100',
        'auto_deduction_category_id' => $this->category->id,
        'auto_deduction_title' => 'Iuran lama',
        'auto_deduction_frequency' => 'monthly',
        'auto_deduction_next_run_on' => '2026-11-20',
        'auto_deduction_day_of_month' => 20,
    ]);
    $this->user->transactions()->create([
        'wallet_id' => $this->wallet->id,
        'category_id' => $this->category->id,
        'title' => 'Iuran yang sudah tercatat',
        'amount' => '100',
        'admin_fee' => '0',
        'type' => 'expense',
        'date' => '2026-11-01',
    ]);

    $this->actingAs($this->user, 'web')->put('/wallets/'.$this->wallet->id, [
        'name' => 'Rekening',
        'wallet_type' => 'regular',
        'balance' => '900',
        'auto_deduction_enabled' => true,
        'auto_deduction_amount' => '300',
        'auto_deduction_category_id' => (string) $this->category->id,
        'auto_deduction_title' => 'Iuran baru',
        'auto_deduction_frequency' => 'monthly',
        'auto_deduction_next_run_on' => '2026-11-25',
    ])->assertRedirect('/wallets');

    expect($this->wallet->fresh()->auto_deduction_title)->toBe('Iuran baru')
        ->and(bccomp((string) $this->wallet->fresh()->auto_deduction_amount, '300', 2))->toBe(0)
        ->and($this->wallet->fresh()->auto_deduction_day_of_month)->toBe(25);

    $this->delete('/wallets/'.$this->wallet->id.'/schedule')->assertRedirect('/wallets');

    expect($this->wallet->fresh()->auto_deduction_enabled)->toBeFalse()
        ->and($this->wallet->fresh()->auto_deduction_next_run_on)->toBeNull()
        ->and($this->wallet->exists)->toBeTrue()
        ->and($this->user->transactions()->count())->toBe(1);
});
