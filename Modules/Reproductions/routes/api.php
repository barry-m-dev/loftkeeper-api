<?php

use Illuminate\Support\Facades\Route;
use Modules\Reproductions\Http\Controllers\ReproductionsController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('reproductions', ReproductionsController::class)->names('reproductions');
});
