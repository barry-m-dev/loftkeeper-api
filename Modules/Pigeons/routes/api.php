<?php

use Illuminate\Support\Facades\Route;
use Modules\Pigeons\Http\Controllers\Api\PigeonController;

/**
 * Routes API pour la gestion des pigeons
 * 
 * Toutes les routes sont protégées par auth:sanctum
 */
Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::prefix('pigeons')->group(function () {
        // Routes CRUD
        Route::get('/', [PigeonController::class, 'index'])->name('pigeons.index');
        Route::post('/', [PigeonController::class, 'store'])->name('pigeons.store');
        Route::get('/disponibles', [PigeonController::class, 'disponibles'])->name('pigeons.disponibles');
        Route::get('/for-parents', [PigeonController::class, 'forParents'])->name('pigeons.forParents');
        Route::get('/{uuid}/for-parent-edit', [PigeonController::class, 'forParentEdit'])
            ->name('pigeons.forParentEdit');
        Route::get('/{uuid}', [PigeonController::class, 'show'])->name('pigeons.show');
        Route::put('/{uuid}', [PigeonController::class, 'update'])->name('pigeons.update');
        Route::delete('/{uuid}', [PigeonController::class, 'destroy'])->name('pigeons.destroy');

        // Actions métier
        Route::get('/{uuid}/genealogy', [PigeonController::class, 'genealogy'])->name('pigeons.genealogy');
        Route::get('/{uuid}/historique', [PigeonController::class, 'historique'])->name('pigeons.historique');
    });
});
