<?php

// Author: MEMORA solutions, https://memora.solutions ; info@memora.ca

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Catalog\Http\Controllers\CourseRequestController;
use Modules\Catalog\Http\Controllers\PublicCatalogController;

$frontMiddleware = \Nwidart\Modules\Facades\Module::find('FrontTheme')?->isEnabled()
    ? ['web', \Modules\FrontTheme\Http\Middleware\SetFrontendTheme::class]
    : ['web'];

Route::middleware($frontMiddleware)->name('catalog.')->group(function () {
    Route::get('/cours', [PublicCatalogController::class, 'index'])->name('cours');

    // Demande de cours personnalisé. Les routes sont TOUJOURS déclarées (la config du module n'est
    // pas encore fusionnée au moment du mappage des routes) ; le drapeau catalog.request_enabled est
    // appliqué DANS le contrôleur au moment de la requête (abort 404 si désactivé) - désactivable sans casse.
    Route::get('/cours/demande', [CourseRequestController::class, 'create'])->name('request');
    Route::post('/cours/demande', [CourseRequestController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('request.store');
});
