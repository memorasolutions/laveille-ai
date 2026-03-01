<?php

use Illuminate\Support\Facades\Route;
use Modules\Core\Http\Middleware\EnsureIsAdmin;
use Modules\Core\Http\Middleware\SetBackofficeTheme;
use Modules\Import\Http\Controllers\ImportController;

Route::prefix('admin')->name('admin.')
    ->middleware(['web', 'auth', 'two.factor', EnsureIsAdmin::class, SetBackofficeTheme::class, 'permission:manage_settings'])
    ->group(function () {
        Route::get('import', [ImportController::class, 'index'])->name('import.index');
        Route::post('import/preview', [ImportController::class, 'preview'])->name('import.preview');
        Route::post('import/execute', [ImportController::class, 'execute'])->name('import.execute');
        Route::get('import/template/{type}', [ImportController::class, 'template'])->name('import.template');
    });
