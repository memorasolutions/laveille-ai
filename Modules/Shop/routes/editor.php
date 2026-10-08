<?php

/**
 * Routes de l'éditeur client. Drapeau OFF (shop.gelato_editor + shop.gelato_zero_erreur) = AUCUNE route enregistrée.
 *
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 */

use Illuminate\Support\Facades\Route;
use Modules\Shop\Gelato\Editor\GelatoEditor;
use Modules\Shop\Http\Controllers\GelatoEditorController;
use Modules\Shop\Http\Middleware\ShopMaintenanceMode;

if (! GelatoEditor::enabled()) {
    return;
}

Route::middleware(['web', ShopMaintenanceMode::class])
    ->prefix(config('shop.routes.prefix', 'boutique').'/editeur')
    ->as('shop.editor.')
    ->group(function () {
        Route::get('polices/{file}', [GelatoEditorController::class, 'font'])->name('font');
        Route::get('{product:slug}', [GelatoEditorController::class, 'show'])->name('show');
        Route::middleware('throttle:30,1')->group(function () {
            Route::post('televerser', [GelatoEditorController::class, 'upload'])->name('upload');
            Route::post('{product:slug}/rendu', [GelatoEditorController::class, 'render'])->name('render');
            Route::post('approuver', [GelatoEditorController::class, 'approve'])->name('approve');
        });
    });
