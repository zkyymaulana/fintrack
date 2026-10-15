<?php

use App\Http\Controllers\Web\AnalyticsController;
use App\Http\Controllers\Web\Auth\RegisteredUserController;
use App\Http\Controllers\Web\Auth\SessionController;
use App\Http\Controllers\Web\BudgetController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\ProfileController;
use App\Http\Controllers\Web\ReportController;
use App\Http\Controllers\Web\TransactionController;
use App\Http\Controllers\Web\WalletController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/fintrack/dashboard');

Route::middleware('guest:web')->group(function (): void {
    Route::get('/login', [SessionController::class, 'create'])->name('login');
    Route::post('/login', [SessionController::class, 'store'])->middleware('throttle:6,1');
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store'])->middleware('throttle:5,1');
});

Route::middleware('auth:web')->group(function (): void {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::resource('wallets', WalletController::class)->except('show');
    Route::resource('transactions', TransactionController::class)->except('show');
    Route::resource('budgets', BudgetController::class)->except('show');
    Route::get('/analytics', AnalyticsController::class)->name('analytics.index');
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/download/{format}', [ReportController::class, 'download'])->name('reports.download');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'password'])->name('profile.password');
    Route::get('/profile/avatar', [ProfileController::class, 'showAvatar'])->name('profile.avatar.show');
    Route::post('/profile/avatar', [ProfileController::class, 'avatar'])->name('profile.avatar.store');
    Route::delete('/profile/avatar', [ProfileController::class, 'deleteAvatar'])->name('profile.avatar.destroy');
    Route::post('/logout', [SessionController::class, 'destroy'])->name('logout');
});
