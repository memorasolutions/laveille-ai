<?php

use Illuminate\Support\Facades\Route;
use Modules\Dictionary\Http\Controllers\DictionaryController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('dictionaries', DictionaryController::class)->names('dictionary');
});
