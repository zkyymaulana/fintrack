<?php

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('get monthly summary returns current month data when no parameters provided', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $wallet = Wallet::create(['user_id' => $user->id, 'name' => 'Dompet', 'balance' => 10000000]);
    $category = Category::create(['name' => 'Makanan & Minuman', 'type' => 'expense']);

    Transaction::create([
        'user_id'     => $user->id,
        'wallet_id'   => $wallet->id,
        'category_id' => $category->id,
        'title'       => 'Gaji',
        'type'        => 'income',
        'amount'      => 15000000,
        'admin_fee'   => 0,
        'date'        => now()->format('Y-m-d'),
    ]);

    Transaction::create([
        'user_id'     => $user->id,
        'wallet_id'   => $wallet->id,
        'category_id' => $category->id,
        'title'       => 'Makan Siang',
        'type'        => 'expense',
        'amount'      => 2000000,
        'admin_fee'   => 0,
        'date'        => now()->format('Y-m-d'),
    ]);

    $response = $this->getJson('/api/analytics/monthly');

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Monthly analytics fetched successfully',
            'data' => [
                'period' => now()->format('F Y'),
                'summary' => [
                    'income'      => 15000000,
                    'expense'     => 2000000,
                    'total_spend' => 2000000,
                    'balance'     => 13000000,
                ],
                'expense_by_category' => [
                    [
                        'category' => 'Makanan & Minuman',
                        'total'    => 2000000,
                    ],
                ],
            ],
        ]);
});

test('get monthly summary filters by month and year query parameters', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $wallet = Wallet::create(['user_id' => $user->id, 'name' => 'Dompet', 'balance' => 10000000]);
    $categoryFood = Category::create(['name' => 'Makanan & Minuman', 'type' => 'expense']);
    $categoryShopping = Category::create(['name' => 'Belanja', 'type' => 'expense']);

    // August 2026 transactions
    Transaction::create([
        'user_id'     => $user->id,
        'wallet_id'   => $wallet->id,
        'category_id' => $categoryFood->id,
        'title'       => 'Gaji Agustus',
        'type'        => 'income',
        'amount'      => 15000000,
        'admin_fee'   => 0,
        'date'        => '2026-08-10',
    ]);

    Transaction::create([
        'user_id'     => $user->id,
        'wallet_id'   => $wallet->id,
        'category_id' => $categoryFood->id,
        'title'       => 'Resto',
        'type'        => 'expense',
        'amount'      => 2000000,
        'admin_fee'   => 0,
        'date'        => '2026-08-15',
    ]);

    Transaction::create([
        'user_id'     => $user->id,
        'wallet_id'   => $wallet->id,
        'category_id' => $categoryShopping->id,
        'title'       => 'Baju',
        'type'        => 'expense',
        'amount'      => 1500000,
        'admin_fee'   => 0,
        'date'        => '2026-08-20',
    ]);

    // July 2026 transaction (different month)
    Transaction::create([
        'user_id'     => $user->id,
        'wallet_id'   => $wallet->id,
        'category_id' => $categoryFood->id,
        'title'       => 'Resto Juli',
        'type'        => 'expense',
        'amount'      => 500000,
        'admin_fee'   => 0,
        'date'        => '2026-07-05',
    ]);

    $response = $this->getJson('/api/analytics/monthly?month=8&year=2026');

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Monthly analytics fetched successfully',
            'data' => [
                'period' => 'August 2026',
                'summary' => [
                    'income'      => 15000000,
                    'expense'     => 3500000,
                    'total_spend' => 3500000,
                    'balance'     => 11500000,
                ],
                'expense_by_category' => [
                    [
                        'category' => 'Makanan & Minuman',
                        'total'    => 2000000,
                    ],
                    [
                        'category' => 'Belanja',
                        'total'    => 1500000,
                    ],
                ],
            ],
        ]);
});

test('get monthly summary filters by month_year parameter', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $wallet = Wallet::create(['user_id' => $user->id, 'name' => 'Dompet', 'balance' => 10000000]);
    $category = Category::create(['name' => 'Belanja', 'type' => 'expense']);

    Transaction::create([
        'user_id'     => $user->id,
        'wallet_id'   => $wallet->id,
        'category_id' => $category->id,
        'title'       => 'Supermarket',
        'type'        => 'expense',
        'amount'      => 500000,
        'admin_fee'   => 10000,
        'date'        => '2026-08-01',
    ]);

    $response = $this->getJson('/api/analytics/monthly?month_year=2026-08');

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Monthly analytics fetched successfully',
            'data' => [
                'period' => 'August 2026',
                'summary' => [
                    'income'      => 0,
                    'expense'     => 510000,
                    'total_spend' => 510000,
                    'balance'     => -510000,
                ],
                'expense_by_category' => [
                    [
                        'category' => 'Belanja',
                        'total'    => 510000,
                    ],
                ],
            ],
        ]);
});

test('get monthly summary returns empty array and zero values when no transactions in period', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->getJson('/api/analytics/monthly?month=1&year=2025');

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Monthly analytics fetched successfully',
            'data' => [
                'period' => 'January 2025',
                'summary' => [
                    'income'      => 0,
                    'expense'     => 0,
                    'total_spend' => 0,
                    'balance'     => 0,
                ],
                'expense_by_category' => [],
            ],
        ]);
});
