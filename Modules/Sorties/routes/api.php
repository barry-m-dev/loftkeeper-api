<?php

use Illuminate\Support\Facades\Route;
use Modules\Sorties\Http\Controllers\SortiesController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('sorties', SortiesController::class)->names('sorties');
});
