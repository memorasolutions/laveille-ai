<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Tools\Http\Controllers\SavedPromptController;
use Modules\Tools\Http\Controllers\SavedDrawPresetController;
use Modules\Tools\Http\Controllers\SavedCrosswordPresetController;
use Modules\Tools\Http\Controllers\SavedQrPresetController;
use Modules\Tools\Http\Controllers\SavedWheelPresetController;
use Modules\Tools\Http\Controllers\SavedTeamPresetController;
use Modules\Tools\Http\Controllers\ToolPreferenceController;
use Modules\Tools\Http\Middleware\EnsureToolNotUnderConstruction;

// Scaffold nwidart jamais implémenté (ToolsController vide, aucune méthode d'écriture réelle) -
// route API supprimée par cohérence sécurité (2026-07-23), même motif que Export/Translation/
// Backup (v1.117.23). Les vraies routes API Tools (prompts/presets ci-dessous) ne sont pas
// affectées, déjà correctement scopées par utilisateur.

// Round 47 (2026-07-27) : throttle:60,1 SANS préfixe utilise sha1($user->getAuthIdentifier()) seul
// comme clé de limiteur (voir ThrottleRequests::resolveRequestSignature()) - ce même throttle nu est
// aussi utilisé par plusieurs autres modules du site (Academy, Search, Sso, AI, ShortUrl, Newsletter,
// Authors, Privacy), qui partageaient donc TOUS le même compteur 60/min par utilisateur. Un utilisateur
// actif ailleurs sur le site pouvait épuiser silencieusement son quota et recevoir un 429 inattendu ici
// (autosave des cartes, chargement du profil). Préfixe dédié = bucket isolé pour Modules/Tools.
Route::middleware(['web', 'auth', 'throttle:60,1,tools-api'])->group(function () {
    // Gate ajoutée après passe adversariale (2026-07-26) : ces routes n'étaient pas couvertes
    // par le gate is_under_construction de /outils/constructeur-prompts lui-même.
    Route::middleware(EnsureToolNotUnderConstruction::class.':constructeur-prompts')->group(function () {
        Route::get('/prompts', [SavedPromptController::class, 'index'])->name('api.prompts.index');
        Route::get('/prompts/{id}', [SavedPromptController::class, 'show'])->name('api.prompts.show');
        Route::post('/prompts', [SavedPromptController::class, 'store'])->name('api.prompts.store');
        Route::put('/prompts/{id}', [SavedPromptController::class, 'update'])->name('api.prompts.update');
        Route::delete('/prompts/{id}', [SavedPromptController::class, 'destroy'])->name('api.prompts.destroy');
        Route::post('/prompts/{id}/duplicate', [SavedPromptController::class, 'duplicate'])->name('api.prompts.duplicate');
    });

    Route::get('/team-presets', [SavedTeamPresetController::class, 'index'])->name('api.team-presets.index');
    Route::post('/team-presets', [SavedTeamPresetController::class, 'store'])->name('api.team-presets.store');
    Route::put('/team-presets/{id}', [SavedTeamPresetController::class, 'update'])->name('api.team-presets.update');
    Route::delete('/team-presets/{id}', [SavedTeamPresetController::class, 'destroy'])->name('api.team-presets.destroy');

    Route::get('/draw-presets', [SavedDrawPresetController::class, 'index'])->name('api.draw-presets.index');
    Route::post('/draw-presets', [SavedDrawPresetController::class, 'store'])->name('api.draw-presets.store');
    Route::put('/draw-presets/{id}', [SavedDrawPresetController::class, 'update'])->name('api.draw-presets.update');
    Route::delete('/draw-presets/{id}', [SavedDrawPresetController::class, 'destroy'])->name('api.draw-presets.destroy');

    Route::get('/qr-presets', [SavedQrPresetController::class, 'index'])->name('api.qr-presets.index');
    Route::post('/qr-presets', [SavedQrPresetController::class, 'store'])->name('api.qr-presets.store');
    Route::put('/qr-presets/{id}', [SavedQrPresetController::class, 'update'])->name('api.qr-presets.update');
    Route::delete('/qr-presets/{id}', [SavedQrPresetController::class, 'destroy'])->name('api.qr-presets.destroy');

    Route::get('/wheel-presets', [SavedWheelPresetController::class, 'index'])->name('api.wheel-presets.index');
    Route::post('/wheel-presets', [SavedWheelPresetController::class, 'store'])->name('api.wheel-presets.store');
    Route::put('/wheel-presets/{id}', [SavedWheelPresetController::class, 'update'])->name('api.wheel-presets.update');
    Route::delete('/wheel-presets/{id}', [SavedWheelPresetController::class, 'destroy'])->name('api.wheel-presets.destroy');

    Route::get('/crossword-presets', [SavedCrosswordPresetController::class, 'index'])->name('api.crossword-presets.index');
    Route::post('/crossword-presets', [SavedCrosswordPresetController::class, 'store'])->name('api.crossword-presets.store');
    Route::put('/crossword-presets/{publicId}', [SavedCrosswordPresetController::class, 'update'])->name('api.crossword-presets.update');
    Route::delete('/crossword-presets/{publicId}', [SavedCrosswordPresetController::class, 'destroy'])->name('api.crossword-presets.destroy');

    // Gate générique (sans slug fixe) : lit {tool} dynamiquement, bloque n'importe quel outil
    // en révision/construction pour les non-superadmins - pas seulement constructeur-prompts.
    Route::get('/tool-preferences/{tool}', [ToolPreferenceController::class, 'show'])
        ->where('tool', '[a-z0-9\-]+')
        ->middleware(EnsureToolNotUnderConstruction::class)
        ->name('api.tool-preferences.show');
    Route::post('/tool-preferences/{tool}', [ToolPreferenceController::class, 'update'])
        ->where('tool', '[a-z0-9\-]+')
        ->middleware(EnsureToolNotUnderConstruction::class)
        ->name('api.tool-preferences.update');
});
