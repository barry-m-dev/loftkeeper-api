<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - Module Users
|--------------------------------------------------------------------------
|
| Routes pour l'authentification et la gestion des utilisateurs
|
*/

// Routes publiques d'authentification
Route::prefix('v1/auth')->group(function () {
    // Inscription et connexion
    Route::post('register', [\Modules\Users\Http\Controllers\Api\AuthController::class, 'register'])->name('auth.register');
    Route::post('login', [\Modules\Users\Http\Controllers\Api\AuthController::class, 'login'])->name('auth.login');

    // Vérification OTP
    Route::post('verify-otp', [\Modules\Users\Http\Controllers\Api\AuthController::class, 'verifyOtp'])->name('auth.verify-otp');
    Route::post('resend-otp', [\Modules\Users\Http\Controllers\Api\AuthController::class, 'resendOtp'])->name('auth.resend-otp');

    // Mot de passe oublié
    Route::post('forgot-password', [\Modules\Users\Http\Controllers\Api\AuthController::class, 'forgotPassword'])->name('auth.forgot-password');
    Route::post('reset-password', [\Modules\Users\Http\Controllers\Api\AuthController::class, 'resetPassword'])->name('auth.reset-password');

    // Refresh token (ne nécessite pas le middleware auth:sanctum car l'access token peut être expiré)
    Route::post('refresh', [\Modules\Users\Http\Controllers\Api\AuthController::class, 'refresh'])->name('auth.refresh');
});

// Routes protégées (nécessitent authentification)
Route::prefix('v1/auth')->middleware(['auth:sanctum'])->group(function () {
    Route::get('me', [\Modules\Users\Http\Controllers\Api\AuthController::class, 'me'])->name('auth.me');
    Route::post('logout', [\Modules\Users\Http\Controllers\Api\AuthController::class, 'logout'])->name('auth.logout');
});

// Routes Dashboard (protégées)
Route::prefix('v1/dashboard')->middleware(['auth:sanctum'])->group(function () {
    Route::get('kpis', [\Modules\Users\Http\Controllers\Api\DashboardController::class, 'kpis'])->name('dashboard.kpis');
});
