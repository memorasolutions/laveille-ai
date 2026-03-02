<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\AI\Http\Controllers\Admin;

use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Modules\AI\Models\AiConversation;
use Modules\AI\Models\AiMessage;

class AiAnalyticsController extends Controller
{
    public function index(): View
    {
        $totalConversations = AiConversation::count();
        $activeConversations = AiConversation::where('status', 'ai_active')->count();
        $totalMessages = AiMessage::count();
        $avgMessagesPerConversation = round($totalMessages / max($totalConversations, 1), 1);

        $dailyActivity = AiMessage::where('created_at', '>=', now()->subDays(30))
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $modelUsage = AiMessage::whereNotNull('model')
            ->selectRaw('model, COUNT(*) as count')
            ->groupBy('model')
            ->orderByDesc('count')
            ->get();

        $feedbackStats = AiMessage::whereNotNull('feedback')
            ->selectRaw('feedback, COUNT(*) as count')
            ->groupBy('feedback')
            ->get();

        return view('ai::admin.analytics.index', compact(
            'totalConversations',
            'activeConversations',
            'totalMessages',
            'avgMessagesPerConversation',
            'dailyActivity',
            'modelUsage',
            'feedbackStats'
        ));
    }
}
