<?php

use Illuminate\Support\Facades\Route;
use Modules\Couples\Http\Controllers\Api\CoupleController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    // CRUD Couples
    Route::apiResource('couples', CoupleController::class)
        ->parameters(['couples' => 'uuid']);

    // Actions spécifiques
    Route::post('couples/{uuid}/rompre', [CoupleController::class, 'rompre'])
        ->name('couples.rompre');

    // Endpoints utilitaires
    Route::get('couples/disponibles/males', [CoupleController::class, 'malesDisponibles'])
        ->name('couples.males-disponibles');

    Route::get('couples/disponibles/femelles', [CoupleController::class, 'femellesDisponibles'])
        ->name('couples.femelles-disponibles');

    Route::get('couples/{uuid}/reproductions', [CoupleController::class, 'reproductions'])
        ->name('couples.reproductions');
});
