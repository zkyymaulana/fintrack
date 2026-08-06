<?php

use App\Models\Budget;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('budget limit carries over to new month while spent resets to zero', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $wallet = Wallet::create(['user_id' => $user->id, 'name' => 'Dompet', 'balance' => 10000000]);
    $categoryFood = Category::create(['name' => 'Makanan & Minuman', 'type' => 'expense']);

    // Budget created in June 2026 (06-2026) with limit 2,000,000
    Budget::create([
        'user_id'      => $user->id,
        'category_id'  => $categoryFood->id,
        'limit_amount' => 2000000,
        'month_year'   => '06-2026',
    ]);

    // Expense transaction in June 2026: 800,000
    Transaction::create([
        'user_id'     => $user->id,
        'wallet_id'   => $wallet->id,
        'category_id' => $categoryFood->id,
        'title'       => 'Makan Juni',
        'type'        => 'expense',
        'amount'      => 800000,
        'admin_fee'   => 0,
        'date'        => '2026-06-15',
    ]);

    // 1. Query for August 2026 (?month=8&year=2026)
    // Budget limit should remain 2,000,000, but actual_spend should reset to 0
    $responseAugust = $this->getJson('/api/budgets?month=8&year=2026');

    $responseAugust->assertStatus(200)
        ->assertJson([
            'success' => true,
            'data' => [
                [
                    'category_id'      => $categoryFood->id,
                    'limit_amount'     => 2000000,
                    'actual_spend'     => 0,
                    'remaining_budget' => 2000000,
                ],
            ],
        ]);

    // 2. Add an expense transaction in August 2026: 300,000
    Transaction::create([
        'user_id'     => $user->id,
        'wallet_id'   => $wallet->id,
        'category_id' => $categoryFood->id,
        'title'       => 'Makan Agustus',
        'type'        => 'expense',
        'amount'      => 300000,
        'admin_fee'   => 0,
        'date'        => '2026-08-10',
    ]);

    // 3. Query again for August 2026
    // actual_spend should be 300,000 and remaining_budget should be 1,700,000
    $responseAugustUpdated = $this->getJson('/api/budgets?month=8&year=2026');

    $responseAugustUpdated->assertStatus(200)
        ->assertJson([
            'success' => true,
            'data' => [
                [
                    'category_id'      => $categoryFood->id,
                    'limit_amount'     => 2000000,
                    'actual_spend'     => 3000000 ? 300000 : 300000,
                    'remaining_budget' => 1700000,
                ],
            ],
        ]);

    // 4. Query for June 2026 historical budget (?month=6&year=2026)
    // actual_spend should reflect June transactions (800,000)
    $responseJune = $this->getJson('/api/budgets?month=6&year=2026');

    $responseJune->assertStatus(200)
        ->assertJson([
            'success' => true,
            'data' => [
                [
                    'category_id'      => $categoryFood->id,
                    'limit_amount'     => 2000000,
                    'actual_spend'     => 800000,
                    'remaining_budget' => 1200000,
                ],
            ],
        ]);
});
