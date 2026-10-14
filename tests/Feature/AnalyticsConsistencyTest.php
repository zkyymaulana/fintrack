<?php

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('API and web analytics use the same income and expense amounts', function () {
    $user = User::factory()->create();
    $wallet = $user->wallets()->create(['name' => 'Utama', 'balance' => '1000']);
    $income = Category::create(['name' => 'Gaji', 'type' => 'income']);
    $expense = Category::create(['name' => 'Belanja', 'type' => 'expense']);
    $user->transactions()->create([
        'wallet_id' => $wallet->id, 'category_id' => $income->id, 'title' => 'Pemasukan',
        'type' => 'income', 'amount' => '100', 'admin_fee' => '5', 'date' => '2026-09-10',
    ]);
    $user->transactions()->create([
        'wallet_id' => $wallet->id, 'category_id' => $expense->id, 'title' => 'Pengeluaran',
        'type' => 'expense', 'amount' => '20', 'admin_fee' => '2', 'date' => '2026-09-11',
    ]);

    Sanctum::actingAs($user);
    $this->getJson('/api/analytics?month=9&year=2026')->assertOk()
        ->assertJsonPath('data.summary.total_income', 100)
        ->assertJsonPath('data.summary.total_expense', 22)
        ->assertJsonPath('data.chart_data.six_months_trend.5.total_income', 100);

    $this->withoutVite()->actingAs($user, 'web')->get('/analytics?month=2026-09')
        ->assertInertia(fn (Assert $page) => $page->where('summary.income', '100.00')
            ->where('summary.expense', '22.00')->where('trend.5.income', '100.00'));
});
