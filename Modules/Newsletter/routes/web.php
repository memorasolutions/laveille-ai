<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Core\Http\Middleware\EnsureIsAdmin;
use Modules\Core\Http\Middleware\SetBackofficeTheme;
use Modules\Newsletter\Http\Controllers\Admin\CampaignController;
use Modules\Newsletter\Http\Controllers\Admin\NewsletterAdminController;
use Modules\Newsletter\Http\Controllers\NewsletterController;

Route::middleware('web')->group(function () {
    Route::post('/newsletter/subscribe', [NewsletterController::class, 'subscribe'])->name('newsletter.subscribe');
    Route::get('/newsletter/confirm/{token}', [NewsletterController::class, 'confirm'])->name('newsletter.confirm');
    Route::get('/newsletter/unsubscribe/{token}', [NewsletterController::class, 'unsubscribe'])->name('newsletter.unsubscribe');
});

Route::prefix('admin/newsletter')
    ->name('admin.newsletter.')
    ->middleware(['web', 'auth', 'two.factor', EnsureIsAdmin::class, SetBackofficeTheme::class])
    ->group(function () {
        Route::get('/', [NewsletterAdminController::class, 'index'])->name('index');
        Route::get('/export', [NewsletterAdminController::class, 'export'])->name('export');
        Route::delete('/{subscriber}', [NewsletterAdminController::class, 'destroy'])->name('destroy');
        // Campagnes
        Route::get('/campaigns', [CampaignController::class, 'index'])->name('campaigns.index');
        Route::get('/campaigns/create', [CampaignController::class, 'create'])->name('campaigns.create');
        Route::post('/campaigns', [CampaignController::class, 'store'])->name('campaigns.store');
        Route::post('/campaigns/{campaign}/send', [CampaignController::class, 'send'])->name('campaigns.send');
    });
