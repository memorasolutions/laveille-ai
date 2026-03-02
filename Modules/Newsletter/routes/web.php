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
use Modules\Newsletter\Http\Controllers\Admin\MarketingTemplateController;
use Modules\Newsletter\Http\Controllers\Admin\WorkflowController;
use Modules\Newsletter\Http\Controllers\Admin\NewsletterAdminController;
use Modules\Newsletter\Http\Controllers\NewsletterController;

Route::middleware('web')->group(function () {
    Route::post('/newsletter/subscribe', [NewsletterController::class, 'subscribe'])->middleware('throttle:5,1')->name('newsletter.subscribe');
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
        // Templates marketing
        Route::get('/templates', [MarketingTemplateController::class, 'index'])->name('templates.index');
        Route::get('/templates/create', [MarketingTemplateController::class, 'create'])->name('templates.create');
        Route::post('/templates', [MarketingTemplateController::class, 'store'])->name('templates.store');
        Route::get('/templates/{template}/edit', [MarketingTemplateController::class, 'edit'])->name('templates.edit');
        Route::put('/templates/{template}', [MarketingTemplateController::class, 'update'])->name('templates.update');
        Route::delete('/templates/{template}', [MarketingTemplateController::class, 'destroy'])->name('templates.destroy');
        Route::get('/templates/{template}/preview', [MarketingTemplateController::class, 'preview'])->name('templates.preview');
        // Workflows
        Route::get('/workflows', [WorkflowController::class, 'index'])->name('workflows.index');
        Route::get('/workflows/create', [WorkflowController::class, 'create'])->name('workflows.create');
        Route::post('/workflows', [WorkflowController::class, 'store'])->name('workflows.store');
        Route::get('/workflows/{workflow}', [WorkflowController::class, 'show'])->name('workflows.show');
        Route::get('/workflows/{workflow}/edit', [WorkflowController::class, 'edit'])->name('workflows.edit');
        Route::put('/workflows/{workflow}', [WorkflowController::class, 'update'])->name('workflows.update');
        Route::delete('/workflows/{workflow}', [WorkflowController::class, 'destroy'])->name('workflows.destroy');
        Route::post('/workflows/{workflow}/activate', [WorkflowController::class, 'activate'])->name('workflows.activate');
        Route::post('/workflows/{workflow}/pause', [WorkflowController::class, 'pause'])->name('workflows.pause');
    });
