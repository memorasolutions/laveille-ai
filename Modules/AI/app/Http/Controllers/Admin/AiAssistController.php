<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\AI\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\AI\Models\Ticket;
use Modules\AI\Services\AiService;
use Modules\AI\Services\SentimentService;
use Modules\AI\Services\SmartReplyService;

class AiAssistController extends Controller
{
    public function suggestReplies(Ticket $ticket, SmartReplyService $service): JsonResponse
    {
        return response()->json([
            'suggestions' => $service->suggestReplies($ticket),
        ]);
    }

    public function analyzeSentiment(Request $request, SentimentService $service): JsonResponse
    {
        $data = $request->validate([
            'text' => 'required|string|max:5000',
        ]);

        return response()->json($service->analyze($data['text']));
    }

    public function rewriteReply(Request $request, AiService $service): JsonResponse
    {
        $data = $request->validate([
            'content' => 'required|string|max:5000',
            'style' => 'required|in:professional,empathetic,concise',
        ]);

        return response()->json([
            'content' => $service->rewriteContent($data['content'], $data['style']),
        ]);
    }
}
