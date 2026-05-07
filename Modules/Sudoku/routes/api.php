<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Sudoku\Http\Controllers\Api\PuzzleApiController;
use Modules\Sudoku\Http\Controllers\Api\SavedPresetApiController;
use Modules\Sudoku\Http\Controllers\Api\ScoreApiController;

Route::prefix('sudoku')->group(function () {
    Route::get('puzzle/{difficulty}', [PuzzleApiController::class, 'today']);
    Route::get('puzzle/{date}/{difficulty}', [PuzzleApiController::class, 'byDate'])
        ->where('date', '\d{4}-\d{2}-\d{2}');

    // #197 : regenerer une grille fraiche pour une difficulte donnee (throttle 6/min)
    Route::post('regenerate/{difficulty}', [PuzzleApiController::class, 'regenerate'])
        ->middleware('throttle:6,1');

    Route::post('score', [ScoreApiController::class, 'submit'])
        ->middleware('throttle:10,1');
    Route::get('leaderboards', [ScoreApiController::class, 'leaderboards']);

    Route::post('preset', [SavedPresetApiController::class, 'save']);
    Route::get('preset/{puzzle_id}', [SavedPresetApiController::class, 'restore'])
        ->where('puzzle_id', '\d+');
    Route::delete('preset/{puzzle_id}', [SavedPresetApiController::class, 'destroy'])
        ->where('puzzle_id', '\d+');
});
