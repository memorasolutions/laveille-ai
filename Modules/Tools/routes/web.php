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
use Modules\Tools\Http\Middleware\EnsureCrosswordTester;

Route::middleware('web')->group(function () {
    Route::get('/outils', [PublicToolController::class, 'index'])->name('tools.index');

    // Mots croisés - routes API spécifiques + fiche (S80 #63 : ouvert au public, lockdown #48 retiré)
    // Note : middleware EnsureCrosswordTester conservé en code (Modules/Tools/Http/Middleware/) pour réactivation rapide si besoin
    Route::get('/outils/mots-croises', [PublicToolController::class, 'show'])
        ->defaults('slug', 'mots-croises')
        ->name('tools.crossword.fiche');

    Route::post('/outils/mots-croises/generate', [PublicCrosswordController::class, 'generate'])
        ->middleware('throttle:30,60')
        ->name('tools.crossword.generate');
    // S80 cleanup : route ai-suggest-pairs retirée (bouton UI retiré S79, dead code)
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

// Espace user authentifie : mes mots croises sauvegardes (S80 #63 : tester guard retiré, lockdown #48 ouvert au public)
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
