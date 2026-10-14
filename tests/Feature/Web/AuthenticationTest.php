<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
});

test('guests can open authentication pages but must log in for dashboard', function () {
    $this->get('/login')->assertOk()->assertInertia(fn (Assert $page) => $page->component('Auth/Login'));
    $this->get('/register')->assertOk()->assertInertia(fn (Assert $page) => $page->component('Auth/Register'));
    $this->get('/dashboard')->assertRedirect('/login');
});

test('a user can register and starts a web session', function () {
    $this->post('/register', [
        'name' => 'Maulana',
        'email' => 'maulana@example.test',
        'password' => 'rahasia123',
        'password_confirmation' => 'rahasia123',
    ])->assertRedirect('/dashboard');

    $user = User::where('email', 'maulana@example.test')->firstOrFail();
    $this->assertAuthenticatedAs($user, 'web');
    expect(Hash::check('rahasia123', $user->password))->toBeTrue();

    $this->get('/dashboard')->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('Dashboard')
        ->where('auth.user.id', $user->id)
        ->where('auth.user.name', 'Maulana')
        ->missing('auth.user.password')
        ->etc());
});

test('invalid login stays unauthenticated and a valid login can log out', function () {
    $user = User::factory()->create(['password' => 'rahasia123']);

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'salah123',
    ])->assertSessionHasErrors('email');
    $this->assertGuest('web');

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'rahasia123',
    ])->assertRedirect('/dashboard');
    $this->assertAuthenticatedAs($user, 'web');

    $this->post('/logout')->assertRedirect('/login');
    $this->assertGuest('web');
    $this->get('/dashboard')->assertRedirect('/login');
});

test('web registration rejects duplicate email addresses', function () {
    User::factory()->create(['email' => 'used@example.test']);

    $this->post('/register', [
        'name' => 'Another User',
        'email' => 'used@example.test',
        'password' => 'rahasia123',
        'password_confirmation' => 'rahasia123',
    ])->assertSessionHasErrors('email');

    $this->assertGuest('web');
    $this->assertDatabaseCount('users', 1);
});

test('an authenticated user is redirected away from guest pages', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'web')->get('/login')->assertRedirect('/dashboard');
    $this->get('/register')->assertRedirect('/dashboard');
});

test('logging out of the web does not revoke a mobile API token', function () {
    $user = User::factory()->create();
    $token = $user->createToken('mobile')->plainTextToken;

    $this->actingAs($user, 'web')->post('/logout')->assertRedirect('/login');

    $this->withToken($token)->getJson('/api/user')
        ->assertOk()
        ->assertJsonPath('id', $user->id);
});
