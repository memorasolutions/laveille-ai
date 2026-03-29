<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\News\Http\Controllers\PublicNewsController;

$frontMiddleware = \Nwidart\Modules\Facades\Module::find('FrontTheme')?->isEnabled()
    ? ['web', \Modules\FrontTheme\Http\Middleware\SetFrontendTheme::class]
    : ['web'];

Route::middleware($frontMiddleware)
    ->prefix('actualites')
    ->name('news.')
    ->group(function () {
        Route::get('/', [PublicNewsController::class, 'index'])->name('index');
        Route::get('/{article}', [PublicNewsController::class, 'show'])->name('show');
    });
