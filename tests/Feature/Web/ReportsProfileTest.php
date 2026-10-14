<?php

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    $this->user = User::factory()->create(['password' => 'rahasia123']);
    $this->other = User::factory()->create();
    $this->category = Category::create(['name' => 'Gaji', 'type' => 'income']);
    $this->wallet = $this->user->wallets()->create(['name' => 'Pribadi', 'balance' => '1000']);
    $this->otherWallet = $this->other->wallets()->create(['name' => 'Asing', 'balance' => '1000']);
});

test('reports and profile require a web session', function () {
    $this->get('/reports')->assertRedirect('/login');
    $this->get('/reports/download/pdf?from=2026-09-01&to=2026-09-30')->assertRedirect('/login');
    $this->get('/reports/download/excel?from=2026-09-01&to=2026-09-30')->assertRedirect('/login');
    $this->get('/profile')->assertRedirect('/login');
    $this->patch('/profile', ['name' => 'Test', 'email' => 'test@example.test'])->assertRedirect('/login');
    $this->put('/profile/password', [])->assertRedirect('/login');
    $this->get('/profile/avatar')->assertRedirect('/login');
});

test('report preview and both downloads contain only account transactions with matching totals', function () {
    $expenseCategory = Category::create(['name' => 'Belanja', 'type' => 'expense']);
    $this->user->transactions()->create(['wallet_id' => $this->wallet->id, 'category_id' => $this->category->id, 'date' => '2026-09-02', 'type' => 'income', 'amount' => '500', 'admin_fee' => '8', 'title' => 'Gaji sendiri']);
    $this->user->transactions()->create(['wallet_id' => $this->wallet->id, 'category_id' => $expenseCategory->id, 'date' => '2026-09-03', 'type' => 'expense', 'amount' => '120', 'admin_fee' => '7.50', 'title' => 'Belanja sendiri']);
    $this->user->transactions()->create(['wallet_id' => $this->wallet->id, 'to_wallet_id' => $this->wallet->id, 'category_id' => $expenseCategory->id, 'date' => '2026-09-04', 'type' => 'transfer', 'amount' => '30', 'admin_fee' => '2', 'title' => 'Transfer sendiri']);
    $this->user->transactions()->create(['wallet_id' => $this->wallet->id, 'category_id' => $expenseCategory->id, 'date' => '2026-08-30', 'type' => 'expense', 'amount' => '999', 'title' => 'Di luar periode']);
    $this->other->transactions()->create(['wallet_id' => $this->otherWallet->id, 'category_id' => $this->category->id, 'date' => '2026-09-05', 'type' => 'income', 'amount' => '9999', 'title' => 'Rahasia akun lain']);
    $this->actingAs($this->user, 'web');

    $this->get('/reports?from=2026-09-01&to=2026-09-30')->assertInertia(fn (Assert $page) => $page
        ->component('Reports')->where('count', 3)->where('summary.income', '500.00')
        ->where('summary.expense', '127.50')->where('summary.net', '372.50')
        ->has('recentTransactions', 3)->missing('recentTransactions.3'));

    $pdf = $this->get('/reports/download/pdf?from=2026-09-01&to=2026-09-30')->assertOk();
    expect($pdf->headers->get('content-disposition'))->toContain('Laporan_FinTrack_2026-09-01_2026-09-30.pdf');
    expect($pdf->getContent())->toStartWith('%PDF-');

    $excel = $this->get('/reports/download/excel?from=2026-09-01&to=2026-09-30')->assertOk();
    expect($excel->headers->get('content-disposition'))->toContain('Laporan_FinTrack_2026-09-01_2026-09-30.xlsx');
    $archive = new ZipArchive;
    expect($archive->open($excel->baseResponse->getFile()->getPathname()))->toBeTrue();
    $content = ($archive->getFromName('xl/sharedStrings.xml') ?: '').($archive->getFromName('xl/worksheets/sheet1.xml') ?: '');
    $archive->close();
    expect($content)->toContain('Gaji sendiri')->toContain('Belanja sendiri')->toContain('Transfer sendiri')
        ->not->toContain('Rahasia akun lain')->not->toContain('Di luar periode');
});

test('report dates and format are validated', function () {
    $this->actingAs($this->user, 'web');
    $this->get('/reports?from=2026-09-30&to=2026-09-01')->assertSessionHasErrors('to');
    $this->get('/reports?from=2025-01-01&to=2026-09-30')->assertSessionHasErrors('to');
    $this->getJson('/reports/download/pdf?from=2026-09-30&to=2026-09-01')->assertUnprocessable()->assertJsonValidationErrors('to');
    $this->getJson('/reports/download/excel?from=2026-09-01')->assertUnprocessable()->assertJsonValidationErrors('to');
    $this->get('/reports/download/csv?from=2026-09-01&to=2026-09-30')->assertNotFound();
});

test('user supplied spreadsheet text remains text', function () {
    $this->user->transactions()->create(['wallet_id' => $this->wallet->id, 'category_id' => $this->category->id, 'date' => '2026-09-02', 'type' => 'income', 'amount' => '5', 'title' => '=1+2']);
    $this->actingAs($this->user, 'web');
    $response = $this->get('/reports/download/excel?from=2026-09-01&to=2026-09-30')->assertOk();
    $sheet = IOFactory::load($response->baseResponse->getFile()->getPathname())->getActiveSheet();
    expect($sheet->getCell('B8')->getValue())->toBe('=1+2');
    expect($sheet->getCell('B8')->getDataType())->toBe(DataType::TYPE_STRING);
});

test('profile edits validate account data and current password', function () {
    $this->actingAs($this->user, 'web')->get('/profile')->assertInertia(fn (Assert $page) => $page
        ->component('Profile')->where('profile.email', $this->user->email)->missing('profile.password'));
    $this->patch('/profile', ['name' => 'Nama Baru', 'email' => $this->other->email])->assertSessionHasErrors('email');
    $this->patch('/profile', ['name' => 'Nama Baru', 'email' => 'baru@example.test'])->assertRedirect();
    expect($this->user->fresh()->name)->toBe('Nama Baru');
    $this->put('/profile/password', ['current_password' => 'salah', 'password' => 'passwordbaru', 'password_confirmation' => 'passwordbaru'])->assertSessionHasErrors('current_password');
    $this->put('/profile/password', ['current_password' => 'rahasia123', 'password' => 'passwordbaru', 'password_confirmation' => 'beda'])->assertSessionHasErrors('password');
    $this->put('/profile/password', ['current_password' => 'rahasia123', 'password' => 'passwordbaru', 'password_confirmation' => 'passwordbaru'])->assertRedirect();
    expect(Hash::check('passwordbaru', $this->user->fresh()->password))->toBeTrue();
});

test('avatar upload serving and deletion are scoped to the signed in user', function () {
    Storage::fake('public');
    $this->actingAs($this->user, 'web');
    $this->post('/profile/avatar', ['avatar' => UploadedFile::fake()->image('foto.jpg', 60, 60)])->assertRedirect();
    $path = $this->user->fresh()->avatar_path;
    Storage::disk('public')->assertExists($path);
    $this->get('/profile/avatar')->assertOk()->assertHeader('Cache-Control', 'no-store, private');
    $this->get('/profile')->assertInertia(fn (Assert $page) => $page->where('auth.user.avatar_url', fn ($url) => str_contains($url, '/profile/avatar')));
    $this->post('/profile/avatar', ['avatar' => UploadedFile::fake()->create('foto.svg', 1, 'image/svg+xml')])->assertSessionHasErrors('avatar');
    $this->post('/profile/avatar', ['avatar' => UploadedFile::fake()->image('pengganti.png', 60, 60)])->assertRedirect();
    Storage::disk('public')->assertMissing($path);
    $path = $this->user->fresh()->avatar_path;
    Storage::disk('public')->assertExists($path);
    $this->actingAs($this->other, 'web')->get('/profile/avatar')->assertNotFound();
    $this->actingAs($this->user, 'web')->delete('/profile/avatar')->assertRedirect();
    Storage::disk('public')->assertMissing($path);
    expect($this->user->fresh()->avatar_path)->toBeNull();
});
