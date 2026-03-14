<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\AI\Services;

use Modules\AI\Models\Ticket;

class SmartReplyService
{
    public function __construct(
        protected AiService $aiService
    ) {}

    /**
     * @return array<int, string>
     */
    public function suggestReplies(Ticket $ticket, int $count = 3): array
    {
        $replies = $ticket->replies()->latest()->limit(5)->get();
        $replyContents = $replies->pluck('content')->implode("\n");

        $context = trim("Title: {$ticket->title}\nDescription: {$ticket->description}\nReplies:\n{$replyContents}");

        $ragContext = app(RagService::class)->getRelevantContext($ticket->description);

        $fullPrompt = "Ticket Context:\n{$context}\n\nKnowledge Base Context:\n{$ragContext}";

        $systemPrompt = "You are a support agent assistant. Based on the ticket context and knowledge base, generate {$count} professional reply suggestions in the same language as the ticket. Return ONLY a JSON array of strings. No markdown.";

        $response = $this->aiService->chat($fullPrompt, $systemPrompt);

        if (preg_match('/\[\s*".*?"\s*(,\s*".*?"\s*)*\]/s', $response, $matches)) {
            $decoded = json_decode($matches[0], true);
            if (is_array($decoded)) {
                return array_map('strval', $decoded);
            }
        }

        return [];
    }
}
