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
use Modules\Newsletter\Http\Controllers\Admin\PromptBuilderController;
use Modules\Newsletter\Http\Controllers\Admin\WorkflowController;
use Modules\Newsletter\Http\Controllers\NewsletterController;

Route::middleware('web')->group(function () {
    Route::post('/newsletter/subscribe', [NewsletterController::class, 'subscribe'])->middleware('throttle:5,1')->name('newsletter.subscribe');
    Route::get('/newsletter/confirm/{token}', [NewsletterController::class, 'confirm'])->name('newsletter.confirm')->middleware('throttle:30,1');
    Route::get('/newsletter/unsubscribe/{token}', [NewsletterController::class, 'unsubscribe'])->name('newsletter.unsubscribe')->middleware('throttle:30,1');
    Route::post('/newsletter/unsubscribe/{token}', [NewsletterController::class, 'unsubscribeOneClick'])
        ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class])
        ->name('newsletter.unsubscribe.oneclick');

    // Unsubscribe v2 — survey + pause/frequency/resubscribe (token = auth, throttle anti-abus)
    Route::post('/newsletter/feedback/{token}', [NewsletterController::class, 'saveFeedback'])
        ->middleware('throttle:10,1')
        ->name('newsletter.feedback');
    Route::post('/newsletter/pause/{token}', [NewsletterController::class, 'pauseSubscription'])
        ->middleware('throttle:10,1')
        ->name('newsletter.pause');
    Route::post('/newsletter/frequency/{token}', [NewsletterController::class, 'updateFrequency'])
        ->middleware('throttle:10,1')
        ->name('newsletter.frequency');
    Route::post('/newsletter/resubscribe/{token}', [NewsletterController::class, 'resubscribe'])
        ->middleware('throttle:10,1')
        ->name('newsletter.resubscribe');

    // Version web de la newsletter (pour les clients email qui affichent mal le HTML)
    Route::get('/newsletter/bienvenue', [\Modules\Newsletter\Http\Controllers\NewsletterWebController::class, 'welcome'])
        ->name('newsletter.welcome')
        ->middleware('cacheResponse:3600');
    Route::get('/newsletter/web/{year}/{week}', [\Modules\Newsletter\Http\Controllers\NewsletterWebController::class, 'show'])
        ->where(['year' => '\d{4}', 'week' => '\d{1,2}'])
        ->name('newsletter.web')
        ->middleware('cacheResponse:3600');
    Route::get('/newsletter/web', [\Modules\Newsletter\Http\Controllers\NewsletterWebController::class, 'latest'])
        ->name('newsletter.web.latest')
        ->middleware('cacheResponse:3600');
    Route::get('/infolettre', [\Modules\Newsletter\Http\Controllers\NewsletterWebController::class, 'archive'])
        ->name('newsletter.archive')
        ->middleware('cacheResponse:600');
});

Route::prefix('admin/newsletter')
    ->name('admin.newsletter.')
    ->middleware(['web', 'auth', 'two.factor', EnsureIsAdmin::class, SetBackofficeTheme::class])
    ->group(function () {
        // Newsletter subscribers - view/delete
        Route::get('/', [NewsletterAdminController::class, 'index'])->name('index')->middleware('permission:view_newsletter');
        Route::get('/stats', [\Modules\Newsletter\Http\Controllers\Admin\NewsletterStatsController::class, 'index'])->name('stats')->middleware('permission:view_newsletter');
        Route::get('/export', [NewsletterAdminController::class, 'export'])->name('export')->middleware('permission:view_newsletter');
        Route::delete('/{subscriber}', [NewsletterAdminController::class, 'destroy'])->name('destroy')->middleware('permission:delete_newsletter');

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

        // Campagnes - send (operational write, rattaché à update_campaigns : pas de permission manage_campaigns distincte seedée)
        Route::post('/campaigns/{campaign}/send', [CampaignController::class, 'send'])->name('campaigns.send')->middleware('permission:update_campaigns');

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

        // Générateur de prompt newsletter pour Claude Code CLI
        Route::prefix('prompt-builder')->name('prompt-builder.')->group(function () {
            // Lecture (view_newsletter)
            Route::get('/', [PromptBuilderController::class, 'index'])->name('index')->middleware('permission:view_newsletter');
            Route::get('/presets/{preset}', [PromptBuilderController::class, 'loadPreset'])->name('preset.load')->middleware('permission:view_newsletter');
            // Recherche DB pour les combobox (view_newsletter + throttle anti-abus)
            Route::get('/search', [PromptBuilderController::class, 'search'])->name('search')->middleware(['permission:view_newsletter', 'throttle:60,1']);
            // Génération + création preset (create_newsletter)
            Route::post('/compile', [PromptBuilderController::class, 'compile'])->name('compile')->middleware(['permission:create_newsletter', 'throttle:30,1']);
            Route::post('/presets', [PromptBuilderController::class, 'storePreset'])->name('preset.store')->middleware('permission:create_newsletter');
            // Modifier le preset par défaut (update_newsletter)
            Route::post('/presets/{preset}/default', [PromptBuilderController::class, 'setDefault'])->name('preset.default')->middleware('permission:update_newsletter');
            // Suppression preset (delete_newsletter)
            Route::delete('/presets/{preset}', [PromptBuilderController::class, 'destroyPreset'])->name('preset.destroy')->middleware('permission:delete_newsletter');
        });

        // Brouillon newsletter hebdomadaire (digest) - édition réservée à update_newsletter
        Route::get('/digest/draft', [\Modules\Newsletter\Http\Controllers\Admin\DigestDraftController::class, 'edit'])->name('digest.edit')->middleware('permission:update_newsletter');
        Route::put('/digest/draft/{issue}', [\Modules\Newsletter\Http\Controllers\Admin\DigestDraftController::class, 'update'])->name('digest.update')->middleware('permission:update_newsletter');
        Route::get('/digest/draft/{issue}/preview', [\Modules\Newsletter\Http\Controllers\Admin\DigestDraftController::class, 'preview'])->name('digest.preview')->middleware('permission:view_newsletter');
        Route::post('/digest/draft/{issue}/send', [\Modules\Newsletter\Http\Controllers\Admin\DigestDraftController::class, 'sendNow'])->name('digest.send')->middleware('permission:update_newsletter');

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
