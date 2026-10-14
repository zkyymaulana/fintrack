<?php

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('production database seeding creates categories without the example account', function () {
    app()->detectEnvironment(fn () => 'production');

    $this->artisan('db:seed', ['--force' => true])->assertSuccessful();

    expect(Category::count())->toBeGreaterThan(0);
    expect(User::count())->toBe(0);
});
