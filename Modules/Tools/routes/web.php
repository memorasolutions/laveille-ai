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
    // 2026-05-05 #94 : index publique grilles partagées (sans publicId) — DOIT venir AVANT /jeumc/{identifier}
    Route::get('/jeumc', [PublicCrosswordController::class, 'index'])->name('tools.crossword.public-index');
    // 2026-05-05 #97 Phase 2 : QR PNG personnalisable — DOIT venir AVANT /jeumc/{identifier} fallback
    Route::get('/jeumc/{identifier}/qr.png', [PublicCrosswordController::class, 'qrPng'])
        ->where('identifier', '[a-zA-Z0-9_-]+')
        ->name('tools.crossword.qr');
    // 2026-05-05 #97 Phase 1 : {identifier} accepte custom_slug OU public_id (BC garantie via Model::findByShareIdentifier)
    Route::get('/jeumc/{identifier}', [PublicCrosswordController::class, 'play'])
        ->where('identifier', '[a-zA-Z0-9_-]+')
        ->name('tools.crossword.play');
    // Backward compatibility : ancien path /jeu/{identifier} -> redirect 301 vers /jeumc/ (S79+ libère /jeu/ pour outils interactifs futurs)
    Route::get('/jeu/{identifier}', fn (string $identifier) => redirect('/jeumc/'.$identifier, 301))
        ->where('identifier', '[a-zA-Z0-9_-]+');

    Route::get('/outils/{slug}', [PublicToolController::class, 'show'])->name('tools.show');
});

// Espace user authentifie : mes mots croises sauvegardes (S80 #63 : tester guard retiré, lockdown #48 ouvert au public)
Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/user/mots-croises', [UserCrosswordController::class, 'index'])->name('user.crosswords.index');
    Route::get('/user/mots-croises/{publicId}/edit', [UserCrosswordController::class, 'edit'])->name('user.crosswords.edit');
    // 2026-05-05 #97 Phase 1 : POST mise à jour custom_slug (lien personnalisé)
    Route::post('/user/mots-croises/{publicId}/slug', [UserCrosswordController::class, 'updateSlug'])
        ->where('publicId', '[a-zA-Z0-9_-]+')
        ->name('user.crosswords.update-slug');
    // 2026-05-05 #108 : GET check unicité slug (async feedback)
    Route::get('/api/crossword-presets/check-slug', [UserCrosswordController::class, 'checkSlug'])
        ->name('user.crosswords.check-slug');
    // 2026-05-05 #97 Phase 2 : POST mise à jour qr_options (couleurs, logo, ECC, dot_style)
    Route::post('/user/mots-croises/{publicId}/qr-options', [UserCrosswordController::class, 'updateQrOptions'])
        ->where('publicId', '[a-zA-Z0-9_-]+')
        ->name('user.crosswords.update-qr-options');
});

Route::middleware(['web', 'auth', \Modules\Core\Http\Middleware\EnsureIsAdmin::class])->prefix('admin')->name('admin.')->group(function () {
    Route::get('tools', [ToolAdminController::class, 'index'])->name('tools.index');
    Route::get('tools/create', [ToolAdminController::class, 'create'])->name('tools.create');
    Route::post('tools', [ToolAdminController::class, 'store'])->name('tools.store');
    Route::get('tools/{tool}/edit', [ToolAdminController::class, 'edit'])->name('tools.edit');
    Route::put('tools/{tool}', [ToolAdminController::class, 'update'])->name('tools.update');
    Route::post('tools/{tool}/toggle', [ToolAdminController::class, 'toggleActive'])->name('tools.toggle');
});
