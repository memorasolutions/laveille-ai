<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\AI\Http\Controllers\ChatStreamController;
use Modules\AI\Http\Controllers\Admin\AgentDashboardController;
use Modules\AI\Http\Controllers\Admin\AiAnalyticsController;
use Modules\AI\Http\Controllers\Admin\ConversationController;
use Modules\AI\Http\Controllers\Admin\ModerationController;
use Modules\Core\Http\Middleware\SetBackofficeTheme;
use Modules\Core\Http\Middleware\EnsureIsAdmin;

// Public SSE route for AI streaming
Route::middleware(['auth', 'throttle:30,1'])->group(function () {
    Route::get('/ai/stream', [ChatStreamController::class, 'stream'])->name('ai.stream');
});

// Admin AI routes
Route::prefix('admin/ai')
    ->name('admin.ai.')
    ->middleware(['auth', 'two.factor', EnsureIsAdmin::class, SetBackofficeTheme::class, 'permission:manage_ai'])
    ->group(function () {
        // Conversations
        Route::get('/conversations', [ConversationController::class, 'index'])->name('conversations.index');
        Route::get('/conversations/{conversation}', [ConversationController::class, 'show'])->name('conversations.show');
        Route::delete('/conversations/{conversation}', [ConversationController::class, 'destroy'])->name('conversations.destroy');

        // Agent dashboard
        Route::get('/agent-dashboard', [AgentDashboardController::class, 'index'])->name('agent.index');
        Route::post('/agent-dashboard/claim/{conversation}', [AgentDashboardController::class, 'claim'])->name('agent.claim');
        Route::post('/agent-dashboard/reply/{conversation}', [AgentDashboardController::class, 'reply'])->name('agent.reply');
        Route::post('/agent-dashboard/close/{conversation}', [AgentDashboardController::class, 'close'])->name('agent.close');
        Route::post('/agent-dashboard/release/{conversation}', [AgentDashboardController::class, 'release'])->name('agent.release');

        // Analytics
        Route::get('/analytics', [AiAnalyticsController::class, 'index'])->name('analytics');

        // Moderation batch
        Route::post('/moderation/batch', [ModerationController::class, 'batch'])->name('moderation.batch');
    });
