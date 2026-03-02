<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\AI\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\AI\Enums\ConversationStatus;
use Modules\AI\Enums\MessageRole;
use Modules\AI\Models\AiConversation;
use Modules\AI\Models\AiMessage;
use Modules\AI\Services\AiService;
use Modules\AI\Services\RagService;
use Modules\Settings\Models\Setting;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ChatStreamController extends Controller
{
    public function stream(Request $request): StreamedResponse
    {
        $validated = $request->validate([
            'message' => 'required|string|max:1000',
            'conversation_id' => 'nullable|integer',
        ]);

        $userMessage = $validated['message'];
        $userId = auth()->id();

        try {
            $conversation = $this->resolveConversation($validated['conversation_id'] ?? null, $userId, $userMessage);

            AiMessage::create([
                'conversation_id' => $conversation->id,
                'role' => MessageRole::User,
                'content' => $userMessage,
            ]);

            $basePrompt = Setting::get('ai.system_prompt', 'You are a helpful assistant.');
            $systemPrompt = app(RagService::class)->buildSystemPrompt($basePrompt, $userMessage);

            $history = $conversation->messages()->orderBy('created_at')->limit(20)->get();
            $messages = [['role' => 'system', 'content' => $systemPrompt]];
            foreach ($history as $msg) {
                $messages[] = ['role' => $msg->role->value, 'content' => $msg->content];
            }

            $aiService = app(AiService::class);
            $fullResponse = '';

            return response()->stream(function () use ($aiService, $messages, $conversation, &$fullResponse) {
                foreach ($aiService->streamChat($messages) as $chunk) {
                    if ($chunk !== '') {
                        $fullResponse .= $chunk;
                        echo 'data: '.json_encode(['content' => $chunk, 'conversation_id' => $conversation->id])."\n\n";
                        if (ob_get_level() > 0) {
                            ob_flush();
                        }
                        flush();
                    }

                    if (connection_aborted()) {
                        break;
                    }
                }

                echo "data: [DONE]\n\n";
                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();

                if ($fullResponse !== '') {
                    AiMessage::create([
                        'conversation_id' => $conversation->id,
                        'role' => MessageRole::Assistant,
                        'content' => $fullResponse,
                    ]);
                }
            }, 200, [
                'Content-Type' => 'text/event-stream',
                'Cache-Control' => 'no-cache',
                'Connection' => 'keep-alive',
                'X-Accel-Buffering' => 'no',
            ]);
        } catch (\Exception $e) {
            return response()->stream(function () {
                echo 'data: '.json_encode(['error' => true, 'message' => 'Une erreur est survenue.'])."\n\n";
                echo "data: [DONE]\n\n";
                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();
            }, 200, [
                'Content-Type' => 'text/event-stream',
                'Cache-Control' => 'no-cache',
            ]);
        }
    }

    private function resolveConversation(?int $conversationId, ?int $userId, string $message): AiConversation
    {
        if ($conversationId) {
            $conversation = AiConversation::where('id', $conversationId)
                ->where('user_id', $userId)
                ->firstOrFail();

            return $conversation;
        }

        return AiConversation::create([
            'user_id' => $userId,
            'title' => mb_substr($message, 0, 50),
            'status' => ConversationStatus::AiActive,
            'model' => app(AiService::class)->getModelForTask('chatbot'),
        ]);
    }
}
