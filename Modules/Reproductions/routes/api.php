<?php

use Illuminate\Support\Facades\Route;
use Modules\Reproductions\Http\Controllers\Api\ReproductionController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    // Endpoints utilitaires (avant apiResource pour éviter les conflits)
    Route::get('reproductions/couples/actifs', [ReproductionController::class, 'couplesActifs'])
        ->name('reproductions.couples-actifs');

    Route::get('couples/{uuid}/reproductions', [ReproductionController::class, 'reproductionsByCouple'])
        ->name('reproductions.by-couple');

    // CRUD Reproductions
    Route::apiResource('reproductions', ReproductionController::class)
        ->parameters(['reproductions' => 'uuid'])
        ->names('reproductions');

    // Actions spécifiques du cycle de reproduction
    Route::post('reproductions/{uuid}/eclosion', [ReproductionController::class, 'declareEclosion'])
        ->name('reproductions.eclosion');

    Route::post('reproductions/{uuid}/sevrage', [ReproductionController::class, 'declareSevrage'])
        ->name('reproductions.sevrage');

    Route::post('reproductions/{uuid}/enregistrer-pigeonneaux', [ReproductionController::class, 'enregistrerPigeonneaux'])
        ->name('reproductions.enregistrer-pigeonneaux');
});
