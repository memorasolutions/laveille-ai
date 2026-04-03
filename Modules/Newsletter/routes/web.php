<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Core\Http\Middleware\EnsureIsAdmin;
use Modules\Core\Http\Middleware\SetBackofficeTheme;
use Modules\Newsletter\Http\Controllers\Admin\CampaignController;
use Modules\Newsletter\Http\Controllers\Admin\MarketingTemplateController;
use Modules\Newsletter\Http\Controllers\Admin\NewsletterAdminController;
use Modules\Newsletter\Http\Controllers\Admin\WorkflowController;
use Modules\Newsletter\Http\Controllers\NewsletterController;

Route::middleware('web')->group(function () {
    Route::post('/newsletter/subscribe', [NewsletterController::class, 'subscribe'])->middleware('throttle:5,1')->name('newsletter.subscribe');
    Route::get('/newsletter/confirm/{token}', [NewsletterController::class, 'confirm'])->name('newsletter.confirm');
    Route::get('/newsletter/unsubscribe/{token}', [NewsletterController::class, 'unsubscribe'])->name('newsletter.unsubscribe');

    // Version web de la newsletter (pour les clients email qui affichent mal le HTML)
    Route::get('/newsletter/web/{year}/{week}', [\Modules\Newsletter\Http\Controllers\NewsletterWebController::class, 'show'])
        ->where(['year' => '\d{4}', 'week' => '\d{1,2}'])
        ->name('newsletter.web')
        ->middleware('cacheResponse:3600');
    Route::get('/newsletter/web', [\Modules\Newsletter\Http\Controllers\NewsletterWebController::class, 'latest'])
        ->name('newsletter.web.latest')
        ->middleware('cacheResponse:3600');
});

Route::prefix('admin/newsletter')
    ->name('admin.newsletter.')
    ->middleware(['web', 'auth', 'two.factor', EnsureIsAdmin::class, SetBackofficeTheme::class])
    ->group(function () {
        // Newsletter subscribers - view/delete
        Route::get('/', [NewsletterAdminController::class, 'index'])->name('index')->middleware('permission:view_newsletter');
        Route::get('/export', [NewsletterAdminController::class, 'export'])->name('export')->middleware('permission:view_newsletter');
        Route::delete('/{subscriber}', [NewsletterAdminController::class, 'destroy'])->name('destroy')->middleware('permission:manage_newsletter');

        // Campagnes - view
        Route::get('/campaigns', [CampaignController::class, 'index'])->name('campaigns.index')->middleware('permission:view_campaigns');

        // Campagnes - create/send
        Route::middleware('permission:create_campaigns')->group(function () {
            Route::get('/campaigns/create', [CampaignController::class, 'create'])->name('campaigns.create');
            Route::post('/campaigns', [CampaignController::class, 'store'])->name('campaigns.store');
        });

        // Campagnes - edit/update/delete
        Route::middleware('permission:update_campaigns')->group(function () {
            Route::get('/campaigns/{campaign}/edit', [CampaignController::class, 'edit'])->name('campaigns.edit');
            Route::put('/campaigns/{campaign}', [CampaignController::class, 'update'])->name('campaigns.update');
        });
        Route::delete('/campaigns/{campaign}', [CampaignController::class, 'destroy'])->name('campaigns.destroy')->middleware('permission:delete_campaigns');

        // Campagnes - send (operational write)
        Route::post('/campaigns/{campaign}/send', [CampaignController::class, 'send'])->name('campaigns.send')->middleware('permission:manage_campaigns');

        // Templates marketing - view
        Route::get('/templates', [MarketingTemplateController::class, 'index'])->name('templates.index')->middleware('permission:view_campaigns');
        Route::get('/templates/{template}/preview', [MarketingTemplateController::class, 'preview'])->name('templates.preview')->middleware('permission:view_campaigns');

        // Templates marketing - create
        Route::middleware('permission:create_campaigns')->group(function () {
            Route::get('/templates/create', [MarketingTemplateController::class, 'create'])->name('templates.create');
            Route::post('/templates', [MarketingTemplateController::class, 'store'])->name('templates.store');
        });

        // Templates marketing - edit/update
        Route::middleware('permission:update_campaigns')->group(function () {
            Route::get('/templates/{template}/edit', [MarketingTemplateController::class, 'edit'])->name('templates.edit');
            Route::put('/templates/{template}', [MarketingTemplateController::class, 'update'])->name('templates.update');
        });

        // Templates marketing - delete
        Route::delete('/templates/{template}', [MarketingTemplateController::class, 'destroy'])->name('templates.destroy')->middleware('permission:delete_campaigns');

        // Workflows - view
        Route::middleware('permission:view_workflows')->group(function () {
            Route::get('/workflows', [WorkflowController::class, 'index'])->name('workflows.index');
        });

        // Workflows - create
        Route::middleware('permission:create_workflows')->group(function () {
            Route::get('/workflows/create', [WorkflowController::class, 'create'])->name('workflows.create');
            Route::post('/workflows', [WorkflowController::class, 'store'])->name('workflows.store');
        });

        Route::get('/workflows/{workflow}', [WorkflowController::class, 'show'])->name('workflows.show')->middleware('permission:view_workflows');

        // Workflows - edit/update
        Route::middleware('permission:update_workflows')->group(function () {
            Route::get('/workflows/{workflow}/edit', [WorkflowController::class, 'edit'])->name('workflows.edit');
            Route::put('/workflows/{workflow}', [WorkflowController::class, 'update'])->name('workflows.update');
            Route::post('/workflows/{workflow}/activate', [WorkflowController::class, 'activate'])->name('workflows.activate');
            Route::post('/workflows/{workflow}/pause', [WorkflowController::class, 'pause'])->name('workflows.pause');
        });

        // Workflows - delete
        Route::delete('/workflows/{workflow}', [WorkflowController::class, 'destroy'])->name('workflows.destroy')->middleware('permission:delete_workflows');
    });
