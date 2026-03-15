<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\AI\Http\Controllers\Admin\AgentDashboardController;
use Modules\AI\Http\Controllers\Admin\AiAnalyticsController;
use Modules\AI\Http\Controllers\Admin\AiAssistController;
use Modules\AI\Http\Controllers\Admin\CannedReplyController;
use Modules\AI\Http\Controllers\Admin\ChannelController;
use Modules\AI\Http\Controllers\Admin\ConversationController;
use Modules\AI\Http\Controllers\Admin\CsatSurveyController;
use Modules\AI\Http\Controllers\Admin\ModerationController;
use Modules\AI\Http\Controllers\Admin\ProactiveTriggerController;
use Modules\AI\Http\Controllers\Admin\SlaPolicyController;
use Modules\AI\Http\Controllers\Admin\TicketController;
use Modules\AI\Http\Controllers\Admin\UnifiedInboxController;
use Modules\AI\Http\Controllers\ChatStreamController;
use Modules\AI\Http\Controllers\KnowledgeBaseController;
use Modules\AI\Http\Controllers\KnowledgeUrlController;
use Modules\AI\Http\Controllers\Webhooks\EmailWebhookController;
use Modules\Core\Http\Middleware\EnsureIsAdmin;
use Modules\Core\Http\Middleware\SetBackofficeTheme;

// Public SSE route for AI streaming
Route::middleware(['auth', 'throttle:30,1'])->group(function () {
    Route::get('/ai/stream', [ChatStreamController::class, 'stream'])->name('ai.stream');
});

// Admin AI routes
Route::prefix('admin/ai')
    ->name('admin.ai.')
    ->middleware(['auth', 'two.factor', EnsureIsAdmin::class, SetBackofficeTheme::class])
    ->group(function () {
        // Conversations - read-only routes
        Route::get('/conversations', [ConversationController::class, 'index'])->name('conversations.index')->middleware('permission:view_ai');
        Route::get('/conversations/{conversation}', [ConversationController::class, 'show'])->name('conversations.show')->middleware('permission:view_ai');
        Route::delete('/conversations/{conversation}', [ConversationController::class, 'destroy'])->name('conversations.destroy')->middleware('permission:manage_ai');

        // Agent dashboard
        Route::get('/agent-dashboard', [AgentDashboardController::class, 'index'])->name('agent.index')->middleware('permission:view_ai');
        Route::post('/agent-dashboard/claim/{conversation}', [AgentDashboardController::class, 'claim'])->name('agent.claim')->middleware('permission:manage_ai');
        Route::post('/agent-dashboard/reply/{conversation}', [AgentDashboardController::class, 'reply'])->name('agent.reply')->middleware('permission:manage_ai');
        Route::post('/agent-dashboard/close/{conversation}', [AgentDashboardController::class, 'close'])->name('agent.close')->middleware('permission:manage_ai');
        Route::post('/agent-dashboard/release/{conversation}', [AgentDashboardController::class, 'release'])->name('agent.release')->middleware('permission:manage_ai');
        Route::get('/agent-dashboard/{conversation}', [AgentDashboardController::class, 'show'])->name('agent.show')->middleware('permission:view_ai');
        Route::post('/agent-dashboard/{conversation}/note', [AgentDashboardController::class, 'addNote'])->name('agent.note')->middleware('permission:manage_ai');
        Route::get('/agent-dashboard/api/canned-replies', [AgentDashboardController::class, 'cannedRepliesJson'])->name('agent.canned-replies-json')->middleware('permission:view_ai');

        // Canned replies CRUD
        Route::get('/canned-replies', [CannedReplyController::class, 'index'])->name('canned-replies.index')->middleware('permission:manage_ai');
        Route::post('/canned-replies', [CannedReplyController::class, 'store'])->name('canned-replies.store')->middleware('permission:manage_ai');
        Route::put('/canned-replies/{cannedReply}', [CannedReplyController::class, 'update'])->name('canned-replies.update')->middleware('permission:manage_ai');
        Route::delete('/canned-replies/{cannedReply}', [CannedReplyController::class, 'destroy'])->name('canned-replies.destroy')->middleware('permission:manage_ai');

        // Analytics
        Route::get('/analytics', [AiAnalyticsController::class, 'index'])->name('analytics')->middleware('permission:view_ai');

        // Moderation batch
        Route::post('/moderation/batch', [ModerationController::class, 'batch'])->name('moderation.batch')->middleware('permission:manage_ai');

        // Knowledge Base CRUD
        Route::get('/knowledge', [KnowledgeBaseController::class, 'index'])->name('knowledge.index')->middleware('permission:view_ai');
        Route::get('/knowledge/create', [KnowledgeBaseController::class, 'create'])->name('knowledge.create')->middleware('permission:manage_ai');
        Route::post('/knowledge', [KnowledgeBaseController::class, 'store'])->name('knowledge.store')->middleware('permission:manage_ai');
        Route::get('/knowledge/{knowledge}', [KnowledgeBaseController::class, 'edit'])->name('knowledge.edit')->middleware('permission:manage_ai');
        Route::put('/knowledge/{knowledge}', [KnowledgeBaseController::class, 'update'])->name('knowledge.update')->middleware('permission:manage_ai');
        Route::delete('/knowledge/{knowledge}', [KnowledgeBaseController::class, 'destroy'])->name('knowledge.destroy')->middleware('permission:manage_ai');

        // Knowledge Base - Sources URLs
        Route::get('/urls', [KnowledgeUrlController::class, 'index'])->name('urls.index')->middleware('permission:view_ai');
        Route::get('/urls/create', [KnowledgeUrlController::class, 'create'])->name('urls.create')->middleware('permission:manage_ai');
        Route::post('/urls', [KnowledgeUrlController::class, 'store'])->name('urls.store')->middleware('permission:manage_ai');
        Route::post('/urls/check-robots', [KnowledgeUrlController::class, 'checkRobots'])->name('urls.check-robots')->middleware('permission:manage_ai');
        Route::get('/urls/{url}', [KnowledgeUrlController::class, 'edit'])->name('urls.edit')->middleware('permission:manage_ai');
        Route::put('/urls/{url}', [KnowledgeUrlController::class, 'update'])->name('urls.update')->middleware('permission:manage_ai');
        Route::post('/urls/{url}/scrape', [KnowledgeUrlController::class, 'scrape'])->name('urls.scrape')->middleware('permission:manage_ai');
        Route::delete('/urls/{url}', [KnowledgeUrlController::class, 'destroy'])->name('urls.destroy')->middleware('permission:manage_ai');

        // Helpdesk tickets
        Route::get('/tickets', [TicketController::class, 'index'])->name('tickets.index')->middleware('permission:view_ai');
        Route::get('/tickets/create', [TicketController::class, 'create'])->name('tickets.create')->middleware('permission:manage_ai');
        Route::post('/tickets', [TicketController::class, 'store'])->name('tickets.store')->middleware('permission:manage_ai');
        Route::get('/tickets/{ticket}', [TicketController::class, 'show'])->name('tickets.show')->middleware('permission:view_ai');
        Route::put('/tickets/{ticket}', [TicketController::class, 'update'])->name('tickets.update')->middleware('permission:manage_ai');
        Route::post('/tickets/{ticket}/reply', [TicketController::class, 'reply'])->name('tickets.reply')->middleware('permission:manage_ai');
        Route::post('/tickets/{ticket}/close', [TicketController::class, 'close'])->name('tickets.close')->middleware('permission:manage_ai');
        Route::post('/tickets/{ticket}/resolve', [TicketController::class, 'resolve'])->name('tickets.resolve')->middleware('permission:manage_ai');
        Route::post('/tickets/from-conversation/{conversation}', [TicketController::class, 'createFromConversation'])->name('tickets.from-conversation')->middleware('permission:manage_ai');

        // SLA policies
        Route::get('/sla', [SlaPolicyController::class, 'index'])->name('sla.index')->middleware('permission:manage_ai');
        Route::post('/sla', [SlaPolicyController::class, 'store'])->name('sla.store')->middleware('permission:manage_ai');
        Route::put('/sla/{slaPolicy}', [SlaPolicyController::class, 'update'])->name('sla.update')->middleware('permission:manage_ai');
        Route::delete('/sla/{slaPolicy}', [SlaPolicyController::class, 'destroy'])->name('sla.destroy')->middleware('permission:manage_ai');

        // Omnichannel - Channels CRUD
        Route::get('/channels', [ChannelController::class, 'index'])->name('channels.index')->middleware('permission:manage_ai');
        Route::post('/channels', [ChannelController::class, 'store'])->name('channels.store')->middleware('permission:manage_ai');
        Route::put('/channels/{channel}', [ChannelController::class, 'update'])->name('channels.update')->middleware('permission:manage_ai');
        Route::delete('/channels/{channel}', [ChannelController::class, 'destroy'])->name('channels.destroy')->middleware('permission:manage_ai');
        Route::patch('/channels/{channel}/toggle', [ChannelController::class, 'toggle'])->name('channels.toggle')->middleware('permission:manage_ai');

        // Omnichannel - Unified Inbox
        Route::get('/inbox', [UnifiedInboxController::class, 'index'])->name('inbox.index')->middleware('permission:view_ai');
        Route::get('/inbox/{channelMessage}', [UnifiedInboxController::class, 'show'])->name('inbox.show')->middleware('permission:view_ai');
        Route::post('/inbox/{channelMessage}/link', [UnifiedInboxController::class, 'linkToTicket'])->name('inbox.link')->middleware('permission:manage_ai');

        // Proactive triggers
        Route::get('/proactive-triggers', [ProactiveTriggerController::class, 'index'])->name('proactive-triggers.index')->middleware('permission:manage_ai');
        Route::post('/proactive-triggers', [ProactiveTriggerController::class, 'store'])->name('proactive-triggers.store')->middleware('permission:manage_ai');
        Route::put('/proactive-triggers/{trigger}', [ProactiveTriggerController::class, 'update'])->name('proactive-triggers.update')->middleware('permission:manage_ai');
        Route::delete('/proactive-triggers/{trigger}', [ProactiveTriggerController::class, 'destroy'])->name('proactive-triggers.destroy')->middleware('permission:manage_ai');
        Route::patch('/proactive-triggers/{trigger}/toggle', [ProactiveTriggerController::class, 'toggle'])->name('proactive-triggers.toggle')->middleware('permission:manage_ai');

        // AI Assist (smart replies, sentiment, rewrite)
        Route::post('/ai-assist/suggest/{ticket}', [AiAssistController::class, 'suggestReplies'])->name('ai-assist.suggest')->middleware('permission:manage_ai');
        Route::post('/ai-assist/sentiment', [AiAssistController::class, 'analyzeSentiment'])->name('ai-assist.sentiment')->middleware('permission:manage_ai');
        Route::post('/ai-assist/rewrite', [AiAssistController::class, 'rewriteReply'])->name('ai-assist.rewrite')->middleware('permission:manage_ai');

        // CSAT Surveys
        Route::get('/csat', [CsatSurveyController::class, 'index'])->name('csat.index')->middleware('permission:view_ai');
        Route::delete('/csat/{survey}', [CsatSurveyController::class, 'destroy'])->name('csat.destroy')->middleware('permission:manage_ai');
    });

// Public CSAT submit (for chatbot widget)
Route::post('/ai/csat/submit', [CsatSurveyController::class, 'submit'])
    ->name('ai.csat.submit')
    ->middleware(['auth', 'throttle:30,1']);

// Public proactive triggers check (for chatbot widget)
Route::post('/ai/proactive-triggers/check', [ProactiveTriggerController::class, 'check'])
    ->name('ai.proactive-triggers.check')
    ->middleware('throttle:60,1');

// Public webhook route (no auth)
Route::post('/ai/webhooks/email/{secret}', [EmailWebhookController::class, 'store'])
    ->name('ai.webhooks.email')
    ->middleware('throttle:60,1');
