<?php

use App\Models\Category;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    $this->user = User::factory()->create();
    $this->other = User::factory()->create();
    $this->income = Category::create(['name' => 'Gaji', 'type' => 'income', 'icon' => 'income']);
    $this->expense = Category::create(['name' => 'Belanja', 'type' => 'expense', 'icon' => 'expense']);
    $this->source = $this->user->wallets()->create(['name' => 'Utama', 'balance' => '1000.00']);
    $this->destination = $this->user->wallets()->create(['name' => 'Tabungan', 'balance' => '200.00']);
    $this->foreign = $this->other->wallets()->create(['name' => 'Pribadi', 'balance' => '9999.00']);
});

function financePayload(array $override = []): array
{
    return array_merge([
        'title' => 'Belanja pasar',
        'type' => 'expense',
        'amount' => '125.25',
        'admin_fee' => '2.50',
        'date' => now()->toDateString(),
        'category_id' => test()->expense->id,
        'wallet_id' => test()->source->id,
        'to_wallet_id' => null,
        'note' => 'Kebutuhan rumah',
    ], $override);
}

function walletBalance(Wallet $wallet): string
{
    return bcadd((string) $wallet->fresh()->balance, '0', 2);
}

test('guests cannot access finance pages', function () {
    $this->get('/wallets')->assertRedirect('/login');
    $this->get('/transactions')->assertRedirect('/login');
    $this->get('/transactions/create')->assertRedirect('/login');
});

test('wallet CRUD is scoped to the signed in user and used wallets cannot be removed', function () {
    $this->actingAs($this->user, 'web');
    $this->get('/wallets')->assertInertia(fn (Assert $page) => $page
        ->component('Wallets/Index')->has('wallets', 2)->where('totalBalance', '1200'));
    $this->get('/wallets/'.$this->foreign->id.'/edit')->assertNotFound();
    $this->put('/wallets/'.$this->foreign->id, ['name' => 'Bocor', 'balance' => '0'])->assertNotFound();
    $this->delete('/wallets/'.$this->foreign->id)->assertNotFound();

    $this->post('/wallets', ['name' => 'Tunai', 'balance' => '50.25'])->assertRedirect('/wallets');
    $created = $this->user->wallets()->where('name', 'Tunai')->firstOrFail();
    $this->put('/wallets/'.$created->id, ['name' => 'Kas', 'balance' => '75.50'])->assertRedirect('/wallets');
    expect(walletBalance($created))->toBe('75.50');
    $this->delete('/wallets/'.$created->id)->assertRedirect('/wallets');
    expect($created->fresh()->trashed())->toBeTrue();

    $this->post('/transactions', financePayload())->assertRedirect('/transactions');
    $this->delete('/wallets/'.$this->source->id)->assertSessionHasErrors('wallet');
    expect($this->source->fresh()->trashed())->toBeFalse();
});

test('transaction CRUD updates balances including admin fees and reverses changes', function () {
    $this->actingAs($this->user, 'web');
    $this->post('/transactions', financePayload())->assertRedirect('/transactions');
    $transaction = $this->user->transactions()->firstOrFail();
    expect(walletBalance($this->source))->toBe('872.25');

    $this->put('/transactions/'.$transaction->id, financePayload([
        'type' => 'income', 'title' => 'Gaji', 'amount' => '300.10', 'admin_fee' => '0',
        'category_id' => $this->income->id, 'wallet_id' => $this->destination->id,
    ]))->assertRedirect('/transactions');
    expect(walletBalance($this->source))->toBe('1000.00');
    expect(walletBalance($this->destination))->toBe('500.10');

    $this->delete('/transactions/'.$transaction->id)->assertRedirect('/transactions');
    expect(walletBalance($this->destination))->toBe('200.00');
    $this->assertDatabaseMissing('transactions', ['id' => $transaction->id]);
});

test('transfer moves money atomically and stores the destination for later reversal', function () {
    $this->actingAs($this->user, 'web');
    $this->post('/transactions', financePayload([
        'type' => 'transfer', 'title' => 'Pindah dana', 'amount' => '250.30', 'admin_fee' => '1.20',
        'to_wallet_id' => $this->destination->id,
    ]))->assertRedirect('/transactions');
    $transaction = $this->user->transactions()->firstOrFail();
    expect($transaction->to_wallet_id)->toBe($this->destination->id);
    expect(walletBalance($this->source))->toBe('748.50');
    expect(walletBalance($this->destination))->toBe('450.30');
    $this->delete('/wallets/'.$this->destination->id)->assertSessionHasErrors('wallet');

    $this->put('/transactions/'.$transaction->id, financePayload([
        'type' => 'transfer', 'title' => 'Pindah lagi', 'amount' => '100.00', 'admin_fee' => '0.50',
        'wallet_id' => $this->destination->id, 'to_wallet_id' => $this->source->id,
    ]))->assertRedirect('/transactions');
    expect(walletBalance($this->source))->toBe('1100.00');
    expect(walletBalance($this->destination))->toBe('99.50');

    $this->delete('/transactions/'.$transaction->id)->assertRedirect('/transactions');
    expect(walletBalance($this->source))->toBe('1000.00');
    expect(walletBalance($this->destination))->toBe('200.00');
});

test('transactions reject foreign wallets and mismatched categories without changing balances', function () {
    $this->actingAs($this->user, 'web');
    $this->post('/transactions', financePayload(['wallet_id' => $this->foreign->id]))->assertSessionHasErrors('wallet_id');
    $this->post('/transactions', financePayload(['category_id' => $this->income->id]))->assertSessionHasErrors('category_id');
    $this->post('/transactions', financePayload(['type' => 'transfer', 'to_wallet_id' => $this->foreign->id]))->assertSessionHasErrors('to_wallet_id');
    $this->post('/transactions', financePayload(['type' => 'transfer', 'to_wallet_id' => $this->source->id]))->assertSessionHasErrors('to_wallet_id');
    $this->assertDatabaseCount('transactions', 0);
    expect(walletBalance($this->foreign))->toBe('9999.00');
});

test('a user cannot read edit or delete another users transaction', function () {
    $transaction = $this->other->transactions()->create([
        'title' => 'Rahasia', 'type' => 'expense', 'amount' => '20', 'admin_fee' => '0',
        'date' => now()->toDateString(), 'category_id' => $this->expense->id, 'wallet_id' => $this->foreign->id,
    ]);
    $this->actingAs($this->user, 'web');
    $this->get('/transactions/'.$transaction->id.'/edit')->assertNotFound();
    $this->put('/transactions/'.$transaction->id, financePayload())->assertNotFound();
    $this->delete('/transactions/'.$transaction->id)->assertNotFound();
    $this->get('/transactions')->assertInertia(fn (Assert $page) => $page->component('Transactions/Index')->has('transactions.data', 0));
    $this->assertDatabaseHas('transactions', ['id' => $transaction->id]);
});

test('dashboard and transaction filters only show current user data', function () {
    $this->actingAs($this->user, 'web');
    $this->post('/transactions', financePayload())->assertRedirect('/transactions');
    $this->post('/transactions', financePayload(['type' => 'income', 'title' => 'Gaji Oktober', 'amount' => '500', 'admin_fee' => '0', 'category_id' => $this->income->id]))->assertRedirect('/transactions');
    $this->other->transactions()->create([
        'title' => 'Milik orang lain', 'type' => 'income', 'amount' => '900', 'admin_fee' => '0',
        'date' => now()->toDateString(), 'category_id' => $this->income->id, 'wallet_id' => $this->foreign->id,
    ]);

    $this->get('/dashboard')->assertInertia(fn (Assert $page) => $page->component('Dashboard')
        ->where('walletCount', 2)->where('transactionCount', 2)->where('monthlyIncome', '500')
        ->where('monthlyExpense', '127.75')->has('recentTransactions', 2));
    $this->get('/transactions?type=income&search=Gaji')->assertInertia(fn (Assert $page) => $page->component('Transactions/Index')
        ->has('transactions.data', 1)->where('transactions.data.0.title', 'Gaji Oktober')->etc());
    $this->get('/transactions?month='.now()->subMonth()->format('Y-m'))->assertInertia(fn (Assert $page) => $page->has('transactions.data', 0));
});
