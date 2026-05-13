<?php

use Illuminate\Support\Facades\Route;
use Modules\Couples\Http\Controllers\CouplesController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('couples', CouplesController::class)->names('couples');
});
