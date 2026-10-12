<?php

// Author: MEMORA solutions, https://memora.solutions ; info@memora.ca

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Catalog\Http\Controllers\PublicCatalogController;

$frontMiddleware = \Nwidart\Modules\Facades\Module::find('FrontTheme')?->isEnabled()
    ? ['web', \Modules\FrontTheme\Http\Middleware\SetFrontendTheme::class]
    : ['web'];

Route::middleware($frontMiddleware)->name('catalog.')->group(function () {
    Route::get('/cours', [PublicCatalogController::class, 'index'])->name('cours');
});
