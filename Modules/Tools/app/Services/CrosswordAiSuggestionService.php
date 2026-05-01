<?php

declare(strict_types=1);

namespace Modules\Tools\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Service de suggestion IA pour mots croisés.
 *
 * Utilise EXCLUSIVEMENT des modèles gratuits ($0/req) :
 * - google/gemma-3-27b-it:free (priorité 1)
 * - meta-llama/llama-3.3-70b-instruct:free (fallback)
 */
final class CrosswordAiSuggestionService
{
    private string $apiUrl = 'https://openrouter.ai/api/v1/chat/completions';

    /**
     * Modèles gratuits ordonnés (priorité décroissante).
     * Tous garantis $0 par requête.
     */
    private array $freeModels = [
        'google/gemma-3-27b-it:free',
        'meta-llama/llama-3.3-70b-instruct:free',
        'mistralai/mistral-small-3.2-24b-instruct:free',
    ];

    public function generatePairsForTheme(string $theme, int $count = 10): array
    {
        $theme = trim($theme);
        if ($theme === '') {
            return [];
        }
        $count = max(5, min($count, 15));

        $prompt = "Pour le thème \"{$theme}\", génère exactement {$count} paires question-réponse pour une grille de mots croisés en français québécois professionnel.\n\n"
            ."Contraintes STRICTES :\n"
            ."- Chaque \"answer\" : UN SEUL mot, 3 à 12 lettres, lettres uniquement (accents permis : é è à ê â ç ï ô ù û)\n"
            ."- Pas d'espace, tiret, chiffre, ponctuation dans answer\n"
            ."- Chaque \"clue\" : phrase courte 30-100 caractères, claire et précise\n"
            ."- Mots variés (pas tous commençant pareil)\n"
            ."- Niveau accessible adultes québécois\n\n"
            ."Retourne UNIQUEMENT du JSON valide, sans markdown ni texte autour, format strict :\n"
            .'{"pairs": [{"clue": "...", "answer": "..."}, ...]}'."\n\n"
            ."Exemple pour thème \"Capitales du monde\" : "
            .'{"pairs":[{"clue":"Capitale de la France","answer":"PARIS"},{"clue":"Capitale du Japon","answer":"TOKYO"}]}';

        foreach ($this->freeModels as $model) {
            $response = $this->call($model, [
                ['role' => 'system', 'content' => 'Tu réponds UNIQUEMENT avec du JSON valide, sans backticks markdown ni texte d\'introduction.'],
                ['role' => 'user', 'content' => $prompt],
            ]);

            if ($response === '') {
                continue;
            }

            $pairs = $this->parsePairs($response);
            if (! empty($pairs)) {
                return $pairs;
            }
        }

        return [];
    }

    private function parsePairs(string $response): array
    {
        if (! preg_match('/\{.*\}/s', $response, $matches)) {
            return [];
        }
        $decoded = json_decode($matches[0], true);
        if (! is_array($decoded) || ! isset($decoded['pairs']) || ! is_array($decoded['pairs'])) {
            return [];
        }

        $valid = [];
        foreach ($decoded['pairs'] as $pair) {
            if (! is_array($pair) || ! isset($pair['clue'], $pair['answer'])) {
                continue;
            }
            $clue = trim((string) $pair['clue']);
            $answer = mb_strtoupper(trim((string) $pair['answer']));

            if ($clue === '' || mb_strlen($clue) > 250) {
                continue;
            }
            $answerLen = mb_strlen($answer);
            if ($answerLen < 2 || $answerLen > 30) {
                continue;
            }
            if (! preg_match('/^[A-ZÀÂÄÉÈÊËÏÎÔÖÙÛÜŸÇ]+$/u', $answer)) {
                continue;
            }

            $valid[] = ['clue' => $clue, 'answer' => $answer];
        }

        return $valid;
    }

    private function call(string $model, array $messages): string
    {
        $apiKey = config('directory.openrouter_api_key') ?: env('OPENROUTER_API_KEY');
        if (! $apiKey) {
            Log::warning('CrosswordAiSuggestionService : OPENROUTER_API_KEY manquante');
            return '';
        }

        try {
            $response = Http::timeout(45)
                ->withHeaders([
                    'Authorization' => "Bearer {$apiKey}",
                    'HTTP-Referer' => 'https://laveille.ai',
                    'X-Title' => 'LaVeille Crossword',
                ])
                ->post($this->apiUrl, [
                    'model' => $model,
                    'messages' => $messages,
                    'temperature' => 0.5,
                    'max_tokens' => 1500,
                ]);

            if ($response->successful()) {
                return (string) ($response->json('choices.0.message.content') ?? '');
            }

            Log::warning('CrosswordAiSuggestionService : erreur API', [
                'model' => $model,
                'status' => $response->status(),
                'body' => mb_substr((string) $response->body(), 0, 500),
            ]);
        } catch (\Throwable $e) {
            Log::warning('CrosswordAiSuggestionService : exception', [
                'model' => $model,
                'error' => $e->getMessage(),
            ]);
        }

        return '';
    }
}
