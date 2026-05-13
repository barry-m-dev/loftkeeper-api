<?php

use Illuminate\Support\Facades\Route;
use Modules\Cages\Http\Controllers\CagesController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('cages', CagesController::class)->names('cages');
});
