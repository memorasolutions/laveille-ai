<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Export\Http\Controllers\ExportController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('exports', ExportController::class)->names('export');
});
