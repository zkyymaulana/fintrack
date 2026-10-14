<?php

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    $this->user = User::factory()->create();
    $this->other = User::factory()->create();
    $this->expense = Category::create(['name' => 'Belanja', 'type' => 'expense']);
    $this->income = Category::create(['name' => 'Gaji', 'type' => 'income']);
    $this->investment = Category::create(['name' => 'Investasi', 'type' => 'expense']);
    $this->wallet = $this->user->wallets()->create(['name' => 'Utama', 'balance' => '1000']);
    $this->otherWallet = $this->other->wallets()->create(['name' => 'Lain', 'balance' => '1000']);
});

function planningTransaction(User $user, int $walletId, int $categoryId, string $date, string $type, string $amount, string $fee = '0', string $title = 'Transaksi'): void
{
    $user->transactions()->create([
        'wallet_id' => $walletId, 'category_id' => $categoryId, 'date' => $date,
        'type' => $type, 'amount' => $amount, 'admin_fee' => $fee, 'title' => $title,
    ]);
}

test('guests cannot open budgets or analytics', function () {
    $this->get('/budgets')->assertRedirect('/login');
    $this->get('/budgets/create')->assertRedirect('/login');
    $this->get('/analytics')->assertRedirect('/login');
});

test('budgets carry forward and a month override can be created updated and removed', function () {
    $this->user->budgets()->create(['category_id' => $this->expense->id, 'month_year' => '08-2026', 'limit_amount' => '200']);
    planningTransaction($this->user, $this->wallet->id, $this->expense->id, '2026-09-05', 'expense', '80', '5');
    $this->actingAs($this->user, 'web');

    $this->get('/budgets?month=2026-09')->assertInertia(fn (Assert $page) => $page->component('Budgets/Index')
        ->where('month', '2026-09')->has('budgets', 1)->where('budgets.0.inherited', true)
        ->where('budgets.0.spent', '85.00')->where('budgets.0.remaining', '115.00')
        ->where('totalLimit', '200.00'));

    $this->post('/budgets', ['month' => '2026-09', 'category_id' => $this->expense->id, 'limit_amount' => '150.00'])
        ->assertRedirect('/budgets?month=2026-09');
    $current = $this->user->budgets()->where('month_year', '09-2026')->firstOrFail();
    $this->post('/budgets', ['month' => '2026-09', 'category_id' => $this->expense->id, 'limit_amount' => '160.00'])
        ->assertRedirect('/budgets?month=2026-09');
    expect($this->user->budgets()->where('month_year', '09-2026')->count())->toBe(1);
    $this->put('/budgets/'.$current->id, ['limit_amount' => '180.00'])->assertRedirect('/budgets?month=2026-09');
    $this->get('/budgets?month=2026-09')->assertInertia(fn (Assert $page) => $page
        ->where('budgets.0.inherited', false)->where('budgets.0.limit_amount', '180.00')->where('budgets.0.remaining', '95.00'));
    $this->delete('/budgets/'.$current->id)->assertRedirect('/budgets?month=2026-09');
    $this->get('/budgets?month=2026-09')->assertInertia(fn (Assert $page) => $page->where('budgets.0.inherited', true));
});

test('budget validation and ownership protect every write', function () {
    $foreign = $this->other->budgets()->create(['category_id' => $this->expense->id, 'month_year' => '09-2026', 'limit_amount' => '999']);
    $this->actingAs($this->user, 'web');
    $this->get('/budgets/'.$foreign->id.'/edit')->assertNotFound();
    $this->put('/budgets/'.$foreign->id, ['limit_amount' => '1'])->assertNotFound();
    $this->delete('/budgets/'.$foreign->id)->assertNotFound();
    $this->post('/budgets', ['month' => '2026-09', 'category_id' => $this->income->id, 'limit_amount' => '100'])->assertSessionHasErrors('category_id');
    $this->post('/budgets', ['month' => '2026-13', 'category_id' => $this->expense->id, 'limit_amount' => '100'])->assertSessionHasErrors('month');
    $this->post('/budgets', ['month' => '2026-09', 'category_id' => $this->expense->id, 'limit_amount' => '-1'])->assertSessionHasErrors('limit_amount');
    expect((float) $foreign->fresh()->limit_amount)->toBe(999.0);
    $this->get('/budgets?month=2026-09')->assertInertia(fn (Assert $page) => $page->has('budgets', 0));
});

test('investment budgets count matching transfers while ordinary budgets count expenses', function () {
    $this->user->budgets()->create(['category_id' => $this->investment->id, 'month_year' => '09-2026', 'limit_amount' => '100']);
    $this->user->budgets()->create(['category_id' => $this->expense->id, 'month_year' => '09-2026', 'limit_amount' => '100']);
    planningTransaction($this->user, $this->wallet->id, $this->expense->id, '2026-09-06', 'transfer', '30', '2', 'Beli saham');
    planningTransaction($this->user, $this->wallet->id, $this->expense->id, '2026-09-07', 'transfer', '40', '1', 'Pindah biasa');
    planningTransaction($this->user, $this->wallet->id, $this->expense->id, '2026-09-08', 'expense', '20', '3');
    $this->actingAs($this->user, 'web')->get('/budgets?month=2026-09')->assertInertia(fn (Assert $page) => $page
        ->where('totalSpent', '55.00')->has('budgets', 2)
        ->where('budgets.0.category_name', 'Investasi')->where('budgets.0.spent', '32.00')
        ->where('budgets.1.category_name', 'Belanja')->where('budgets.1.spent', '23.00'));
});

test('monthly analytics separate transfers and keep other users out of summaries and charts', function () {
    planningTransaction($this->user, $this->wallet->id, $this->income->id, '2026-09-01', 'income', '500');
    planningTransaction($this->user, $this->wallet->id, $this->expense->id, '2026-09-02', 'expense', '120', '7.50');
    planningTransaction($this->user, $this->wallet->id, $this->expense->id, '2026-09-03', 'transfer', '30');
    planningTransaction($this->user, $this->wallet->id, $this->expense->id, '2026-08-20', 'expense', '25');
    planningTransaction($this->other, $this->otherWallet->id, $this->income->id, '2026-09-01', 'income', '999');
    $this->actingAs($this->user, 'web')->get('/analytics?month=2026-09')->assertInertia(fn (Assert $page) => $page
        ->component('Analytics')->where('start', '2026-09-01')->where('end', '2026-09-30')
        ->where('summary.income', '500.00')->where('summary.expense', '127.50')->where('summary.net', '372.50')
        ->has('trend', 6)->where('trend.4.expense', '25.00')->where('trend.5.income', '500.00')
        ->has('distribution', 1)->where('distribution.0.amount', '127.50')->where('distribution.0.count', 1));
});

test('custom range uses exact dates and rejects reversed or excessive periods', function () {
    planningTransaction($this->user, $this->wallet->id, $this->expense->id, '2026-09-05', 'expense', '50');
    planningTransaction($this->user, $this->wallet->id, $this->expense->id, '2026-09-15', 'expense', '20');
    planningTransaction($this->user, $this->wallet->id, $this->income->id, '2026-10-10', 'income', '100');
    planningTransaction($this->user, $this->wallet->id, $this->income->id, '2026-10-20', 'income', '200');
    $this->actingAs($this->user, 'web')->get('/analytics?mode=range&from=2026-09-10&to=2026-10-12')
        ->assertInertia(fn (Assert $page) => $page->where('summary.income', '100.00')
            ->where('summary.expense', '20.00')->where('summary.net', '80.00')
            ->has('trend', 2)->where('trend.0.expense', '20.00')->where('trend.1.income', '100.00'));
    $this->get('/analytics?mode=range&from=2026-10-12&to=2026-09-10')->assertSessionHasErrors('to');
    $this->get('/analytics?mode=range&from=2025-01-01&to=2026-10-12')->assertSessionHasErrors('to');
});

test('dashboard shows budget progress and six months of trend data', function () {
    $month = now()->format('m-Y');
    $date = now()->toDateString();
    $this->user->budgets()->create(['category_id' => $this->expense->id, 'month_year' => $month, 'limit_amount' => '200']);
    planningTransaction($this->user, $this->wallet->id, $this->expense->id, $date, 'expense', '75', '5');
    $this->actingAs($this->user, 'web')->get('/dashboard')->assertInertia(fn (Assert $page) => $page
        ->where('budgetOverview.totalLimit', '200.00')->where('budgetOverview.totalSpent', '80.00')
        ->where('budgetOverview.budgets.0.percentage', 40)->has('trend', 6));
});
