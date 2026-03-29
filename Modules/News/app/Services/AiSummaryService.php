<?php

declare(strict_types=1);

namespace Modules\News\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiSummaryService
{
    public function summarize(string $text, string $language = 'fr'): ?string
    {
        $apiKey = config('services.openrouter.api_key', env('OPENROUTER_API_KEY'));

        if (! $apiKey) {
            Log::warning('OPENROUTER_API_KEY non configurée pour le résumé IA.');

            return null;
        }

        $prompt = $language === 'fr'
            ? "Résume cet article en 2-3 phrases en français. Sois concis et informatif.\n\n"
            : "Summarize this article in 2-3 sentences in English. Be concise and informative.\n\n";

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$apiKey,
            ])->timeout(30)->post('https://openrouter.ai/api/v1/chat/completions', [
                'model' => 'google/gemma-3-27b-it:free',
                'messages' => [
                    ['role' => 'user', 'content' => $prompt.mb_substr($text, 0, 2000)],
                ],
                'temperature' => 0.5,
            ]);

            $data = $response->json();

            if ($response->successful() && isset($data['choices'][0]['message']['content'])) {
                return trim($data['choices'][0]['message']['content']);
            }

            Log::warning('OpenRouter summary error: '.($data['error']['message'] ?? 'Réponse invalide'));

            return null;

        } catch (\Throwable $e) {
            Log::error('AI summary exception: '.$e->getMessage());

            return null;
        }
    }
}
