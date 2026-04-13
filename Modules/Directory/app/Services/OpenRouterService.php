<?php

namespace Modules\Directory\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenRouterService
{
    private string $apiUrl = 'https://openrouter.ai/api/v1/chat/completions';

    public function search(string $query): string
    {
        return $this->call('perplexity/sonar-pro', [
            ['role' => 'user', 'content' => $query],
        ]);
    }

    public function generate(string $prompt, string $systemPrompt = ''): string
    {
        $messages = [];
        if ($systemPrompt) {
            $messages[] = ['role' => 'system', 'content' => $systemPrompt];
        }
        $messages[] = ['role' => 'user', 'content' => $prompt];

        return $this->call('qwen/qwen3-max', $messages);
    }

    public function summarize(string $text, int $maxWords = 200): string
    {
        return $this->generate(
            "Résume ce texte en français en maximum {$maxWords} mots. Indique la langue originale si ce n'est pas du français :\n\n{$text}"
        );
    }

    private function call(string $model, array $messages, int $maxRetries = 2): string
    {
        $apiKey = config('directory.openrouter_api_key') ?: env('OPENROUTER_API_KEY');
        if (! $apiKey) {
            Log::warning('OpenRouterService : clé API manquante');

            return '';
        }

        $attempt = 0;
        while ($attempt <= $maxRetries) {
            try {
                $response = Http::timeout(60)
                    ->withHeaders([
                        'Authorization' => "Bearer {$apiKey}",
                        'HTTP-Referer' => 'https://laveille.ai',
                        'X-Title' => 'LaVeille',
                    ])
                    ->post($this->apiUrl, [
                        'model' => $model,
                        'messages' => $messages,
                    ]);

                if ($response->successful()) {
                    return $response->json('choices.0.message.content') ?? '';
                }

                Log::warning("OpenRouterService : erreur API {$response->status()}", [
                    'model' => $model,
                    'attempt' => $attempt + 1,
                ]);
            } catch (\Throwable $e) {
                Log::warning("OpenRouterService : exception", [
                    'model' => $model,
                    'error' => $e->getMessage(),
                    'attempt' => $attempt + 1,
                ]);
            }

            $attempt++;
            if ($attempt <= $maxRetries) {
                sleep(1);
            }
        }

        return '';
    }
}
