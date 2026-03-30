<?php

declare(strict_types=1);

namespace Modules\News\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiSummaryService
{
    private const DEFAULT_MODELS = [
        'google/gemma-3-27b-it:free',
        'deepseek/deepseek-chat',
        'google/gemini-2.0-flash-001',
    ];

    public function summarize(string $text, string $language = 'fr'): ?string
    {
        $apiKey = config('services.openrouter.api_key', env('OPENROUTER_API_KEY'));
        if (! $apiKey) {
            Log::warning('OPENROUTER_API_KEY non configurée pour le résumé IA.');

            return null;
        }

        $models = config('services.openrouter.summary_models', self::DEFAULT_MODELS);
        $prompt = $language === 'fr'
            ? "Résume cet article en 2-3 phrases en français. Sois concis et informatif.\n\n"
            : "Summarize this article in 2-3 sentences in English. Be concise and informative.\n\n";

        $truncatedText = mb_substr($text, 0, 2000);

        foreach ($models as $index => $model) {
            try {
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer '.$apiKey,
                ])->timeout(30)->post('https://openrouter.ai/api/v1/chat/completions', [
                    'model' => $model,
                    'messages' => [['role' => 'user', 'content' => $prompt.$truncatedText]],
                    'temperature' => 0.5,
                ]);

                $data = $response->json();

                if ($response->successful() && isset($data['choices'][0]['message']['content'])) {
                    return trim($data['choices'][0]['message']['content']);
                }

                $errorMessage = $data['error']['message'] ?? 'Réponse invalide';
                Log::warning("OpenRouter summary failed [{$model}]: {$errorMessage}");

            } catch (\Throwable $e) {
                Log::warning("AI summary exception [{$model}]: {$e->getMessage()}");
            }

            if ($index < count($models) - 1) {
                sleep(1);
            }
        }

        Log::error('Tous les modèles de résumé IA ont échoué.');

        return null;
    }
}
