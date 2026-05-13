<?php

use Illuminate\Support\Facades\Route;
use Modules\Reproductions\Http\Controllers\ReproductionsController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('reproductions', ReproductionsController::class)->names('reproductions');
});
