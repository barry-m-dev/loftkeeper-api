<?php

use Illuminate\Support\Facades\Route;
use Modules\Pigeons\Http\Controllers\PigeonsController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('pigeons', PigeonsController::class)->names('pigeons');
});
