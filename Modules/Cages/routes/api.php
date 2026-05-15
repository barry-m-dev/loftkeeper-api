<?php

use Illuminate\Support\Facades\Route;
use Modules\Cages\Http\Controllers\Api\CageController;

/**
 * Routes API pour la gestion des cages
 * 
 * Toutes les routes sont protégées par auth:sanctum
 */
Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    // Routes CRUD
    Route::prefix('cages')->group(function () {
        Route::get('/', [CageController::class, 'index'])->name('cages.index');
        Route::get('/grille', [CageController::class, 'grille'])->name('cages.grille');
        Route::get('/couples-disponibles', [CageController::class, 'couplesDisponibles'])->name('cages.couples-disponibles');
        Route::post('/', [CageController::class, 'store'])->name('cages.store');
        Route::get('/{uuid}', [CageController::class, 'show'])->name('cages.show');
        Route::put('/{uuid}', [CageController::class, 'update'])->name('cages.update');
        Route::delete('/{uuid}', [CageController::class, 'destroy'])->name('cages.destroy');

        // Actions métier
        Route::post('/{uuid}/affecter-pigeon', [CageController::class, 'affecterPigeon'])->name('cages.affecter-pigeon');
        Route::post('/{uuid}/affecter-couple', [CageController::class, 'affecterCouple'])->name('cages.affecter-couple');
        Route::post('/{uuid}/liberer', [CageController::class, 'liberer'])->name('cages.liberer');
    });
});
