<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\AI\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\AI\Models\AiMessage;

class MessageFeedbackController extends Controller
{
    public function store(Request $request, int $messageId): JsonResponse
    {
        $validated = $request->validate([
            'feedback' => 'required|in:up,down',
            'comment' => 'nullable|string|max:500',
        ]);

        $message = AiMessage::findOrFail($messageId);

        if ((int) $message->conversation->user_id !== (int) auth()->id()) {
            abort(403);
        }

        $message->update([
            'feedback' => $validated['feedback'],
            'feedback_comment' => $validated['comment'] ?? null,
        ]);

        return response()->json(['success' => true, 'message' => 'Merci pour votre retour.']);
    }
}
