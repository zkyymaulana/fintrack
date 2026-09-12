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
            'message' => 'Monthly financial report generated successfully',
            'data' => [
                'period' => [
                    'formatted' => now()->format('F Y'),
                ],
                'summary' => [
                    'total_income'  => 15000000,
                    'total_expense' => 2000000,
                    'net_cash_flow' => 13000000,
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
            'message' => 'Monthly financial report generated successfully',
            'data' => [
                'period' => [
                    'formatted' => 'August 2026',
                ],
                'summary' => [
                    'total_income'  => 15000000,
                    'total_expense' => 3500000,
                    'net_cash_flow' => 11500000,
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
            'message' => 'Monthly financial report generated successfully',
            'data' => [
                'period' => [
                    'formatted' => 'August 2026',
                ],
                'summary' => [
                    'total_income'  => 0,
                    'total_expense' => 510000,
                    'net_cash_flow' => -510000,
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
            'message' => 'Monthly financial report generated successfully',
            'data' => [
                'period' => [
                    'formatted' => 'January 2025',
                ],
                'summary' => [
                    'total_income'  => 0,
                    'total_expense' => 0,
                    'net_cash_flow' => 0,
                ],
            ],
        ]);
});

test('auto detects saving and investment transfer transactions in monthly report', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $wallet1 = Wallet::create(['user_id' => $user->id, 'name' => 'BCA', 'balance' => 10000000]);
    $wallet2 = Wallet::create(['user_id' => $user->id, 'name' => 'Bibit', 'balance' => 0]);
    $category = Category::create(['name' => 'Transfer', 'type' => 'expense']);

    // Regular transfer (should NOT count as saving)
    Transaction::create([
        'user_id'      => $user->id,
        'wallet_id'    => $wallet1->id,
        'to_wallet_id' => $wallet2->id,
        'category_id'  => $category->id,
        'title'        => 'Transfer ke Dompet 2',
        'type'         => 'transfer',
        'amount'       => 500000,
        'admin_fee'    => 0,
        'date'         => now()->format('Y-m-d'),
    ]);

    // Transfer with keyword 'Bibit' in title (SHOULD count as saving)
    Transaction::create([
        'user_id'      => $user->id,
        'wallet_id'    => $wallet1->id,
        'to_wallet_id' => $wallet2->id,
        'category_id'  => $category->id,
        'title'        => 'Top Up Bibit Reksadana',
        'type'         => 'transfer',
        'amount'       => 1500000,
        'admin_fee'    => 0,
        'date'         => now()->format('Y-m-d'),
    ]);

    // Transfer with keyword 'saham' in note (SHOULD count as saving)
    Transaction::create([
        'user_id'      => $user->id,
        'wallet_id'    => $wallet1->id,
        'to_wallet_id' => $wallet2->id,
        'category_id'  => $category->id,
        'title'        => 'Beli Lot',
        'note'         => 'Beli Saham BBRI',
        'type'         => 'transfer',
        'amount'       => 2500000,
        'admin_fee'    => 0,
        'date'         => now()->format('Y-m-d'),
    ]);

    $response = $this->getJson('/api/analytics');

    $response->assertStatus(200);
    $data = $response->json('data');
    expect($data['summary']['total_saving'])->toEqual(4000000);
    expect($data['total_saving'])->toEqual(4000000);
    // Verifikasi mutlak: transfer saving tidak masuk ke income ataupun expense
    expect($data['summary']['total_income'])->toEqual(0);
    expect($data['summary']['total_expense'])->toEqual(0);
});

test('returns expense distribution with category groups and transaction item details', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $wallet = Wallet::create(['user_id' => $user->id, 'name' => 'BCA', 'balance' => 10000000]);
    $catWajib = Category::create(['name' => 'Wajib', 'type' => 'expense', 'icon' => 'receipt']);
    $catGayaHidup = Category::create(['name' => 'Gaya Hidup', 'type' => 'expense', 'icon' => 'local_cafe']);

    Transaction::create([
        'user_id'     => $user->id,
        'wallet_id'   => $wallet->id,
        'category_id' => $catWajib->id,
        'title'       => 'Bayar Listrik',
        'type'        => 'expense',
        'amount'      => 160880,
        'admin_fee'   => 0,
        'date'        => now()->format('Y-m-d'),
    ]);

    Transaction::create([
        'user_id'     => $user->id,
        'wallet_id'   => $wallet->id,
        'category_id' => $catWajib->id,
        'title'       => 'Beli Bensin',
        'type'        => 'expense',
        'amount'      => 50000,
        'admin_fee'   => 0,
        'date'        => now()->format('Y-m-d'),
    ]);

    Transaction::create([
        'user_id'     => $user->id,
        'wallet_id'   => $wallet->id,
        'category_id' => $catGayaHidup->id,
        'title'       => 'Nonton Bioskop',
        'type'        => 'expense',
        'amount'      => 75000,
        'admin_fee'   => 0,
        'date'        => now()->format('Y-m-d'),
    ]);

    $response = $this->getJson('/api/analytics');

    $response->assertStatus(200);
    $data = $response->json('data');

    expect($data)->toHaveKey('expense_distribution');
    $distribution = $data['expense_distribution'];
    expect($distribution)->toBeArray();
    expect(count($distribution))->toBe(2);

    $wajibGroup = collect($distribution)->firstWhere('category_name', 'Wajib');
    expect($wajibGroup)->not->toBeNull();
    expect($wajibGroup['total_amount'])->toEqual(210880);
    expect($wajibGroup['transactions_count'])->toBe(2);
    expect($wajibGroup['transactions'])->toBeArray();
    expect(count($wajibGroup['transactions']))->toBe(2);
    expect($wajibGroup['transactions'][0])->toHaveKeys(['id', 'title', 'amount', 'date']);
});
