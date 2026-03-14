<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\AI\Http\Controllers\Admin;

use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Modules\AI\Models\AiConversation;
use Modules\AI\Models\AiMessage;
use Modules\AI\Models\ChannelMessage;
use Modules\AI\Models\CsatSurvey;
use Modules\AI\Models\Ticket;

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

        // Helpdesk stats
        $totalTickets = Ticket::count();
        $openTickets = Ticket::where('status', 'open')->count();
        $resolvedTickets = Ticket::where('status', 'resolved')->count();
        $ticketsByPriority = Ticket::selectRaw('priority, COUNT(*) as count')
            ->groupBy('priority')
            ->get();

        // CSAT stats
        $csatAvg = CsatSurvey::averageScore();
        $csatTotal = CsatSurvey::count();
        $csatTrend = CsatSurvey::where('created_at', '>=', now()->subDays(30))
            ->selectRaw('DATE(created_at) as date, AVG(score) as avg_score, COUNT(*) as count')
            ->groupByRaw('DATE(created_at)')
            ->orderByRaw('DATE(created_at)')
            ->get();

        // Channel stats
        $channelMessages = ChannelMessage::where('created_at', '>=', now()->subDays(30))->count();

        return view('ai::admin.analytics.index', compact(
            'totalConversations',
            'activeConversations',
            'totalMessages',
            'avgMessagesPerConversation',
            'dailyActivity',
            'modelUsage',
            'feedbackStats',
            'totalTickets',
            'openTickets',
            'resolvedTickets',
            'ticketsByPriority',
            'csatAvg',
            'csatTotal',
            'csatTrend',
            'channelMessages'
        ));
    }
}
