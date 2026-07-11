<?php

use Illuminate\Support\Facades\Route;
use Modules\Journal\Http\Controllers\JournalController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('journals', JournalController::class)->names('journal');
});
