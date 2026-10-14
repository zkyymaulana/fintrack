<?php

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('API transfer keeps its response shape and destination for web history and reversal', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $source = $user->wallets()->create(['name' => 'Utama', 'balance' => '1000']);
    $destination = $user->wallets()->create(['name' => 'Tabungan', 'balance' => '100']);
    $foreign = $other->wallets()->create(['name' => 'Lain', 'balance' => '500']);
    $category = Category::create(['name' => 'Pindah Akun', 'type' => 'expense']);
    Sanctum::actingAs($user);

    $payload = [
        'title' => 'Pindah uang', 'type' => 'transfer', 'amount' => '200', 'admin_fee' => '2',
        'date' => now()->toDateString(), 'category_id' => $category->id,
        'wallet_id' => $source->id, 'to_wallet_id' => $destination->id,
    ];
    $this->postJson('/api/transactions', array_merge($payload, ['to_wallet_id' => $foreign->id]))
        ->assertUnprocessable()->assertJsonValidationErrors('to_wallet_id');
    $this->postJson('/api/transactions', array_merge($payload, ['wallet_id' => $foreign->id]))
        ->assertUnprocessable()->assertJsonValidationErrors('wallet_id');

    $response = $this->postJson('/api/transactions', $payload)->assertCreated()
        ->assertJsonPath('message', 'Transaction created successfully')
        ->assertJsonPath('data.to_wallet_id', $destination->id);
    $id = $response->json('data.id');
    expect((float) $source->fresh()->balance)->toBe(798.0);
    expect((float) $destination->fresh()->balance)->toBe(300.0);

    $this->putJson('/api/transactions/'.$id, ['wallet_id' => $foreign->id])
        ->assertUnprocessable()->assertJsonValidationErrors('wallet_id');

    $this->deleteJson('/api/wallets/'.$destination->id)->assertStatus(400);
    $this->deleteJson('/api/transactions/'.$id)->assertOk()->assertJsonPath('message', 'Transaction deleted successfully');
    expect((float) $source->fresh()->balance)->toBe(1000.0);
    expect((float) $destination->fresh()->balance)->toBe(100.0);
});
