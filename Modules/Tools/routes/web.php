<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

use Illuminate\Support\Facades\Route;
use Modules\Tools\Http\Controllers\Admin\ToolAdminController;
use Modules\Tools\Http\Controllers\PublicCrosswordController;
use Modules\Tools\Http\Controllers\PublicToolController;
use Modules\Tools\Http\Controllers\UserCrosswordController;

Route::middleware('web')->group(function () {
    Route::get('/outils', [PublicToolController::class, 'index'])->name('tools.index');

    // Mots croisés - routes API spécifiques (la fiche est gérée par PublicToolController via slug DB)
    Route::post('/outils/mots-croises/generate', [PublicCrosswordController::class, 'generate'])
        ->middleware('throttle:30,60')
        ->name('tools.crossword.generate');
    Route::post('/outils/mots-croises/ai-suggest-pairs', [PublicCrosswordController::class, 'aiSuggestPairs'])
        ->middleware('throttle:10,60')
        ->name('tools.crossword.ai-suggest-pairs');
    Route::post('/outils/mots-croises/pdf-blank', [PublicCrosswordController::class, 'pdfBlank'])
        ->middleware('throttle:30,60')
        ->name('tools.crossword.pdf-blank');
    Route::post('/outils/mots-croises/pdf-solution', [PublicCrosswordController::class, 'pdfSolution'])
        ->middleware('throttle:30,60')
        ->name('tools.crossword.pdf-solution');
    Route::post('/outils/mots-croises/csv-export', [PublicCrosswordController::class, 'csvExport'])
        ->middleware('throttle:30,60')
        ->name('tools.crossword.csv-export');
    Route::post('/outils/mots-croises/csv-import', [PublicCrosswordController::class, 'csvImport'])
        ->middleware('throttle:30,60')
        ->name('tools.crossword.csv-import');
    Route::get('/outils/mots-croises/csv-template', [PublicCrosswordController::class, 'csvTemplate'])
        ->name('tools.crossword.csv-template');
    Route::get('/jeumc/{publicId}', [PublicCrosswordController::class, 'play'])->name('tools.crossword.play');
    // Backward compatibility : ancien path /jeu/{publicId} -> redirect 301 vers /jeumc/ (S79+ libère /jeu/ pour outils interactifs futurs)
    Route::get('/jeu/{publicId}', fn (string $publicId) => redirect('/jeumc/'.$publicId, 301));

    Route::get('/outils/{slug}', [PublicToolController::class, 'show'])->name('tools.show');
});

// Espace user authentifie : mes mots croises sauvegardes
Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/user/mots-croises', [UserCrosswordController::class, 'index'])->name('user.crosswords.index');
    Route::get('/user/mots-croises/{publicId}/edit', [UserCrosswordController::class, 'edit'])->name('user.crosswords.edit');
});

Route::middleware(['web', 'auth', \Modules\Core\Http\Middleware\EnsureIsAdmin::class])->prefix('admin')->name('admin.')->group(function () {
    Route::get('tools', [ToolAdminController::class, 'index'])->name('tools.index');
    Route::get('tools/create', [ToolAdminController::class, 'create'])->name('tools.create');
    Route::post('tools', [ToolAdminController::class, 'store'])->name('tools.store');
    Route::get('tools/{tool}/edit', [ToolAdminController::class, 'edit'])->name('tools.edit');
    Route::put('tools/{tool}', [ToolAdminController::class, 'update'])->name('tools.update');
    Route::post('tools/{tool}/toggle', [ToolAdminController::class, 'toggleActive'])->name('tools.toggle');
});
