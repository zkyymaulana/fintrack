<?php

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('user can get list of categories', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    Category::create(['name' => 'Gaji', 'type' => 'income', 'icon' => 'payments']);
    Category::create(['name' => 'Makanan', 'type' => 'expense', 'icon' => 'restaurant']);

    $response = $this->getJson('/api/categories');

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Categories retrieved successfully',
        ])
        ->assertJsonCount(2, 'data');
});

test('user can filter categories by type', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    Category::create(['name' => 'Gaji', 'type' => 'income', 'icon' => 'payments']);
    Category::create(['name' => 'Makanan', 'type' => 'expense', 'icon' => 'restaurant']);

    $response = $this->getJson('/api/categories?type=expense');

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonFragment(['name' => 'Makanan', 'type' => 'expense']);
});

test('user can create a new category', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $payload = [
        'name' => 'Investasi',
        'type' => 'income',
        'icon' => 'trending_up',
    ];

    $response = $this->postJson('/api/categories', $payload);

    $response->assertStatus(201)
        ->assertJson([
            'success' => true,
            'message' => 'Category created successfully',
            'data' => [
                'name' => 'Investasi',
                'type' => 'income',
                'icon' => 'trending_up',
            ],
        ]);

    $this->assertDatabaseHas('categories', [
        'name' => 'Investasi',
        'type' => 'income',
        'icon' => 'trending_up',
    ]);
});

test('user can update a category', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $category = Category::create(['name' => 'Hiburan', 'type' => 'expense', 'icon' => 'movie']);

    $response = $this->putJson("/api/categories/{$category->id}", [
        'name' => 'Hiburan & Hobi',
        'icon' => 'sports_esports',
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'data' => [
                'name' => 'Hiburan & Hobi',
                'type' => 'expense',
                'icon' => 'sports_esports',
            ],
        ]);
});

test('user can delete a category if not in use', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $category = Category::create(['name' => 'Kategori Sementara', 'type' => 'expense', 'icon' => 'delete']);

    $response = $this->deleteJson("/api/categories/{$category->id}");

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Category deleted successfully',
        ]);

    $this->assertDatabaseMissing('categories', ['id' => $category->id]);
});
