<?php

use Illuminate\Support\Facades\Route;
use Modules\Sudoku\Http\Controllers\Api\PuzzleApiController;
use Modules\Sudoku\Http\Controllers\Api\ScoreApiController;

Route::prefix('sudoku')->group(function () {
    Route::get('puzzle/{difficulty}', [PuzzleApiController::class, 'today']);
    Route::post('score', [ScoreApiController::class, 'submit'])->middleware('throttle:10,1');
    Route::get('leaderboards', [ScoreApiController::class, 'leaderboards']);
});
