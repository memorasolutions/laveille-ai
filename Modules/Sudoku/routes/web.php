<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Sudoku\Http\Controllers\LeaderboardController;
use Modules\Sudoku\Http\Controllers\PublicSudokuController;
use Modules\Sudoku\Http\Middleware\SudokuConstructionGate;

Route::prefix('outils/sudoku')->name('sudoku.')->middleware(SudokuConstructionGate::class)->group(function () {
    Route::get('/', [PublicSudokuController::class, 'play'])->name('play');
    Route::get('/leaderboards', [LeaderboardController::class, 'index'])->name('leaderboards');
    Route::get('/archive', [PublicSudokuController::class, 'archive'])->name('archive');
    Route::get('/{date}', [PublicSudokuController::class, 'showDate'])
        ->where('date', '\d{4}-\d{2}-\d{2}')
        ->name('date');
});
