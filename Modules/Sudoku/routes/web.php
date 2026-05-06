<?php

use Illuminate\Support\Facades\Route;
use Modules\Sudoku\Http\Controllers\LeaderboardController;
use Modules\Sudoku\Http\Controllers\PublicSudokuController;

Route::get('/sudoku', [PublicSudokuController::class, 'play'])->name('sudoku.play');
Route::get('/sudoku/leaderboards', [LeaderboardController::class, 'index'])->name('sudoku.leaderboards');
Route::get('/sudoku/archive', [PublicSudokuController::class, 'archive'])->name('sudoku.archive');
Route::get('/sudoku/{date}', [PublicSudokuController::class, 'showDate'])
    ->where('date', '\d{4}-\d{2}-\d{2}')
    ->name('sudoku.date');
