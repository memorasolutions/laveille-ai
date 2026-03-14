<?php

use Illuminate\Support\Facades\Route;
use Modules\Roadmap\Http\Controllers\RoadmapController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('roadmaps', RoadmapController::class)->names('roadmap');
});
