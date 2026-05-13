<?php

use Illuminate\Support\Facades\Route;
use Modules\Pigeons\Http\Controllers\PigeonsController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('pigeons', PigeonsController::class)->names('pigeons');
});
