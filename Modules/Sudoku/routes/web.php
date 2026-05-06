<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Sudoku\Http\Controllers\LeaderboardController;
use Modules\Sudoku\Http\Controllers\PublicSudokuController;
use Modules\Sudoku\Http\Middleware\SudokuConstructionGate;

Route::prefix('outils/sudoku')->name('sudoku.')->middleware(SudokuConstructionGate::class)->group(function () {
    Route::get('/', [PublicSudokuController::class, 'play'])->name('play');
    Route::get('/leaderboards', [LeaderboardController::class, 'index'])->name('leaderboards');

    // #170 : Archive publique retiree (philosophie "a la demande, sans cout").
    // Mes parties = sauvegarde personnelle SavedSudokuPreset (auth obligatoire).
    Route::get('/mes-parties', [PublicSudokuController::class, 'mesParties'])
        ->middleware('auth')
        ->name('my-games');

    // Backward-compat : redirige les anciens liens /archive et /{date} vers /outils/sudoku
    Route::get('/archive', fn () => redirect()->route('sudoku.play'));
    Route::get('/{date}', fn () => redirect()->route('sudoku.play'))
        ->where('date', '\d{4}-\d{2}-\d{2}');
});
