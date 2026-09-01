<?php

use App\Http\Controllers\Api\AnalyticsController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BudgetController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ExportController;
use App\Http\Controllers\Api\PasswordResetController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\WalletController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Endpoint publicly accessible for user registration and login
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Route for sending OTP to user's email for password reset
Route::post('/forgot-password', [PasswordResetController::class, 'sendOtp']);

// Route for resetting password using OTP, accessible without authentication
Route::post('/reset-password', [PasswordResetController::class, 'resetPassword']);

// Endpoint accessible only for authenticated users
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::post('/profile/update', [ProfileController::class, 'update']);

    // Category routes
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::post('/categories', [CategoryController::class, 'store']);
    Route::get('/categories/{id}', [CategoryController::class, 'show']);
    Route::put('/categories/{id}', [CategoryController::class, 'update']);
    Route::delete('/categories/{id}', [CategoryController::class, 'destroy']);
    
    // Transaction routes
    Route::get('/transactions', [TransactionController::class, 'index']);
    Route::post('/transactions', [TransactionController::class, 'store']);
    Route::put('/transactions/{id}', [TransactionController::class, 'update']);
    Route::delete('/transactions/{id}', [TransactionController::class, 'destroy']);
    Route::post('/transactions/scan-voice', [TransactionController::class, 'scanVoice']);

    // Budget routes
    Route::get('/budgets', [BudgetController::class, 'index']);
    Route::post('/budgets', [BudgetController::class, 'store']);
    Route::put('/budgets/{id}', [BudgetController::class, 'update']);
    Route::delete('/budgets/{id}', [BudgetController::class, 'destroy']);

    // Wallet routes
    Route::get('/wallets', [WalletController::class, 'index']);
    Route::post('/wallets', [WalletController::class, 'store']);
    Route::put('/wallets/{id}', [WalletController::class, 'update']);
    Route::delete('/wallets/{id}', [WalletController::class, 'destroy']);

    // Analytics routes
    Route::get('/analytics', [AnalyticsController::class, 'getMonthlyReport']);
    Route::get('/analytics/monthly', [AnalyticsController::class, 'getMonthlySummary']);
    Route::get('/analytics/monthly-report', [AnalyticsController::class, 'getMonthlyReport']);
    Route::get('/analytics/report', [AnalyticsController::class, 'getMonthlyReport']);

    // Export routes (PDF & Excel)
    Route::get('/export/pdf', [ExportController::class, 'exportPDF']);
    Route::get('/export/excel', [ExportController::class, 'exportExcel']);

    // Scan transactions route
    Route::post('/transactions/scan', [TransactionController::class, 'scan']);

    Route::post('/update-fcm-token', [AuthController::class, 'updateFcmToken']);
});
