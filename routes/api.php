<?php

use App\Http\Controllers\Api\AnalyticsController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BudgetController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\WalletController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Endpoint publicly accessible for user registration and login
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Endpoint accessible only for authenticated users
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::post('/profile/update', [ProfileController::class, 'update']);

    //Category routes
    Route::get('/categories', [CategoryController::class, 'index']);
    
    // Transaction routes
    Route::get('/transactions', [TransactionController::class, 'index']);
    Route::post('/transactions', [TransactionController::class, 'store']);
    Route::put('/transactions/{id}', [TransactionController::class, 'update']);
    Route::delete('/transactions/{id}', [TransactionController::class, 'destroy']);

    // Budget routes
    Route::get('/budgets', [BudgetController::class, 'index']);
    Route::post('/budgets', [BudgetController::class, 'store']);

    // Wallet routes
    Route::get('/wallets', [WalletController::class, 'index']);
    Route::post('/wallets', [WalletController::class, 'store']);
    Route::put('/wallets/{id}', [WalletController::class, 'update']);
    Route::delete('/wallets/{id}', [WalletController::class, 'destroy']);

    // Analytics routes
    Route::get('/analytics/monthly', [AnalyticsController::class, 'getMonthlySummary']);

    // Scan transactions route
    Route::post('/transactions/scan', [TransactionController::class, 'scan']);

    Route::post('/update-fcm-token', [AuthController::class, 'updateFcmToken']);

    Route::get('/set-test-token', function () {
    \App\Models\User::find(1)->update([
        'fcm_token' => 'eg9kzz3FTNmj-Ge73bF8U-:APA91bHmYAr9_IweIOWrxH9OmAwsKnMosKUYBhY1WZRO1inphgDWlX3hRhwBiuUM9nQMNRsCHu7k9bcs1ttaABtHwkTUZufeafqqn2lSfpAVi2UajT6aO84'
    ]);
    return response()->json(['message' => 'Token saved']);
    });

    Route::get('/test-notif', function () {
    $user = \App\Models\User::find(1);
    
    if (!$user->fcm_token) {
        return response()->json(['error' => 'FCM token not found']);
    }

    \App\Http\Controllers\Api\AuthController::sendNotification(
        $user->fcm_token,
        '🔔 Test Notifikasi',
        'Halo dari FinTrack! Notifikasi berhasil.'
    );

    return response()->json(['message' => 'Notifikasi terkirim!']);
});
});
