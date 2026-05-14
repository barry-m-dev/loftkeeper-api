<?php

use Illuminate\Support\Facades\Route;
use Modules\Couples\Http\Controllers\Api\CoupleController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('couples', CoupleController::class)->parameters(['couples' => 'uuid']);
    Route::post('couples/{uuid}/rompre', [CoupleController::class, 'rompre'])->name('couples.rompre');
});
