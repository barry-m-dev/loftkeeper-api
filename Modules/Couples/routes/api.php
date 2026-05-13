<?php

use Illuminate\Support\Facades\Route;
use Modules\Couples\Http\Controllers\CouplesController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('couples', CouplesController::class)->names('couples');
});
