<?php

use Illuminate\Support\Facades\Route;
use Modules\Sorties\Http\Controllers\SortiesController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('sorties', SortiesController::class)->names('sorties');
});
