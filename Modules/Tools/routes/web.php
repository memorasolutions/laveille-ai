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

// S80 : restriction tester unique sur l'outil mots-croisés (phase tests, accès Stéphane only)
$crosswordTesterOnly = function ($request, $next) {
    if (optional(auth()->user())->email !== 'chatgptpro@gomemora.com') {
        if ($request->expectsJson()) {
            return response()->json(['error' => 'Outil en construction. Accès réservé pendant la phase de tests.'], 403);
        }
        return response()->view('tools::public.tools.mots-croises-construction', [], 403);
    }
    return $next($request);
};

Route::middleware('web')->group(function () use ($crosswordTesterOnly) {
    Route::get('/outils', [PublicToolController::class, 'index'])->name('tools.index');

    // Mots croisés - routes API spécifiques + fiche (toutes wrappées par tester guard)
    Route::middleware([$crosswordTesterOnly])->group(function () {
        // Fiche outil (intercepte le slug avant le catch-all /outils/{slug})
        Route::get('/outils/mots-croises', [PublicToolController::class, 'show'])
            ->defaults('slug', 'mots-croises')
            ->name('tools.crossword.fiche');

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
    });

    Route::get('/outils/{slug}', [PublicToolController::class, 'show'])->name('tools.show');
});

// Espace user authentifie : mes mots croises sauvegardes (sous tester guard également)
Route::middleware(['web', 'auth', $crosswordTesterOnly])->group(function () {
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
