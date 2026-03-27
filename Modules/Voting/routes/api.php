<?php

use Illuminate\Support\Facades\Route;
use Modules\Voting\Http\Controllers\VotingController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('votings', VotingController::class)->names('voting');
});
