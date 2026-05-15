<?php

use Illuminate\Support\Facades\Route;
use Modules\Sorties\Http\Controllers\Api\SortieController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::get('sorties', [SortieController::class, 'index'])->name('sorties.index');
    Route::post('pigeons/{pigeon}/sorties', [SortieController::class, 'store'])->name('sorties.store');
});
