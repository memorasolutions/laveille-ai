<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

use Modules\Faq\Http\Controllers\PublicFaqController;

// Route publique FAQ (nécessite FrontTheme)
if (\Nwidart\Modules\Facades\Module::find('FrontTheme')?->isEnabled()) {
    Route::middleware(['web', \Modules\FrontTheme\Http\Middleware\SetFrontendTheme::class])->group(function () {
        Route::get('/faq', [PublicFaqController::class, 'index'])->name('faq.index');
    });
}
