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

test('budgets endpoint defaults to current month and year when no parameters provided', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $category = Category::create(['name' => 'Transport', 'type' => 'expense']);
    $now = now();
    $currentMonthYear = sprintf('%02d-%04d', $now->month, $now->year);

    Budget::create([
        'user_id'      => $user->id,
        'category_id'  => $category->id,
        'limit_amount' => 500000,
        'month_year'   => '01-2020',
    ]);

    $response = $this->getJson('/api/budgets');

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'period'  => $currentMonthYear,
            'month'   => (int) $now->month,
            'year'    => (int) $now->year,
            'data'    => [
                [
                    'category_id'  => $category->id,
                    'limit_amount' => 500000,
                    'actual_spend' => 0,
                    'month_year'   => $currentMonthYear,
                ]
            ]
        ]);
});

test('user can update budget limit and rename category safely migrating transactions of that month only', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $wallet = Wallet::create(['user_id' => $user->id, 'name' => 'Dompet', 'balance' => 5000000]);
    $oldCategory = Category::create(['name' => 'Makanan Lama', 'type' => 'expense', 'icon' => 'fastfood']);
    
    $budget = Budget::create([
        'user_id'      => $user->id,
        'category_id'  => $oldCategory->id,
        'limit_amount' => 1500000,
        'month_year'   => '09-2026',
    ]);

    // Transaction in August 2026 (past month) - SHOULD NOT BE MIGRATED
    $pastTx = Transaction::create([
        'user_id'     => $user->id,
        'wallet_id'   => $wallet->id,
        'category_id' => $oldCategory->id,
        'title'       => 'Makan Agustus',
        'type'        => 'expense',
        'amount'      => 100000,
        'date'        => '2026-08-20',
    ]);

    // Transaction in September 2026 (current month) - MUST BE MIGRATED
    $currentTx = Transaction::create([
        'user_id'     => $user->id,
        'wallet_id'   => $wallet->id,
        'category_id' => $oldCategory->id,
        'title'       => 'Makan September',
        'type'        => 'expense',
        'amount'      => 200000,
        'date'        => '2026-09-05',
    ]);

    // Update budget category name to "Makanan & Minuman Baru"
    $response = $this->putJson("/api/budgets/{$budget->id}", [
        'name'         => 'Makanan & Minuman Baru',
        'limit_amount' => 2500000,
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Budget updated successfully',
            'data'    => [
                'id'           => $budget->id,
                'limit_amount' => 2500000,
                'category'     => [
                    'name' => 'Makanan & Minuman Baru',
                ],
            ],
        ]);

    // Verify old category in DB unchanged
    $oldCategoryFresh = Category::find($oldCategory->id);
    expect($oldCategoryFresh->name)->toBe('Makanan Lama');

    // Verify budget points to new category
    $budgetFresh = Budget::find($budget->id);
    expect($budgetFresh->category_id)->not->toBe($oldCategory->id);
    expect($budgetFresh->category->name)->toBe('Makanan & Minuman Baru');

    // Verify September transaction was moved to new category
    $currentTxFresh = Transaction::find($currentTx->id);
    expect($currentTxFresh->category_id)->toBe($budgetFresh->category_id);

    // Verify August transaction remains in old category
    $pastTxFresh = Transaction::find($pastTx->id);
    expect($pastTxFresh->category_id)->toBe($oldCategory->id);
});

test('updating budget with identical category name does not create new category', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $category = Category::create(['name' => 'Belanja', 'type' => 'expense']);
    $budget = Budget::create([
        'user_id'      => $user->id,
        'category_id'  => $category->id,
        'limit_amount' => 1000000,
        'month_year'   => '09-2026',
    ]);

    $categoriesCountBefore = Category::count();

    $response = $this->putJson("/api/budgets/{$budget->id}", [
        'name'         => 'Belanja',
        'limit_amount' => 1200000,
    ]);

    $response->assertStatus(200);

    expect(Category::count())->toBe($categoriesCountBefore);
    $budgetFresh = Budget::find($budget->id);
    expect($budgetFresh->category_id)->toBe($category->id);
    expect((float) $budgetFresh->limit_amount)->toBe(1200000.0);
});

test('user can delete budget using destroy endpoint', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $category = Category::create(['name' => 'Hiburan', 'type' => 'expense']);
    $budget = Budget::create([
        'user_id'      => $user->id,
        'category_id'  => $category->id,
        'limit_amount' => 750000,
        'month_year'   => '09-2026',
    ]);

    $response = $this->deleteJson("/api/budgets/{$budget->id}");

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Budget deleted successfully',
        ]);

    expect(Budget::find($budget->id))->toBeNull();
});

test('cannot delete budget of another user', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    Sanctum::actingAs($user1);

    $category = Category::create(['name' => 'Hiburan', 'type' => 'expense']);
    $budget = Budget::create([
        'user_id'      => $user2->id,
        'category_id'  => $category->id,
        'limit_amount' => 750000,
        'month_year'   => '09-2026',
    ]);

    $response = $this->deleteJson("/api/budgets/{$budget->id}");

    $response->assertStatus(404)
        ->assertJson([
            'success' => false,
            'message' => 'Budget not found',
        ]);

    expect(Budget::find($budget->id))->not->toBeNull();
});
