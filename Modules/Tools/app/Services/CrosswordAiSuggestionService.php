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
     * NB : gemma-3 ne supporte PAS system prompt -> on merge tout en user.
     */
    private array $freeModels = [
        'meta-llama/llama-3.3-70b-instruct:free',
        'qwen/qwen-2.5-72b-instruct:free',
        'google/gemma-3-27b-it:free',
        'microsoft/phi-4:free',
        'nousresearch/hermes-3-llama-3.1-405b:free',
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

        // Merge system+user en 1 user message (gemma-3 et certains modèles
        // free ne supportent pas le rôle system).
        $mergedPrompt = "Tu réponds UNIQUEMENT avec du JSON valide, sans backticks markdown ni texte d'introduction.\n\n".$prompt;

        foreach ($this->freeModels as $model) {
            $response = $this->call($model, [
                ['role' => 'user', 'content' => $mergedPrompt],
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
            Log::error('CrosswordAi parsePairs: aucun JSON dans réponse', ['response_preview' => mb_substr($response, 0, 200)]);
            return [];
        }
        $decoded = json_decode($matches[0], true);
        if (! is_array($decoded) || ! isset($decoded['pairs']) || ! is_array($decoded['pairs'])) {
            Log::error('CrosswordAi parsePairs: JSON invalide ou clef pairs manquante', [
                'json_error' => json_last_error_msg(),
                'preview' => mb_substr($matches[0], 0, 200),
            ]);
            return [];
        }

        $valid = [];
        $rejected = [];
        foreach ($decoded['pairs'] as $pair) {
            if (! is_array($pair) || ! isset($pair['clue'], $pair['answer'])) {
                $rejected[] = 'no_clue_or_answer_key';
                continue;
            }
            $clue = trim((string) $pair['clue']);
            // Normalisation answer : uppercase, retirer accents espaces parasites
            $rawAnswer = trim((string) $pair['answer']);
            $answer = mb_strtoupper($rawAnswer);
            // Retirer espaces internes (gemma peut renvoyer "NEW YORK")
            $answer = preg_replace('/\s+/u', '', $answer);

            if ($clue === '') {
                $rejected[] = 'empty_clue';
                continue;
            }
            if (mb_strlen($clue) > 250) {
                $rejected[] = 'clue_too_long';
                continue;
            }
            $answerLen = mb_strlen($answer);
            if ($answerLen < 2) {
                $rejected[] = "answer_too_short:$rawAnswer";
                continue;
            }
            if ($answerLen > 30) {
                $rejected[] = "answer_too_long:$rawAnswer";
                continue;
            }
            // Regex large : toute lettre unicode majuscule (\p{Lu}) ou tiret-cadrat exclu
            if (! preg_match('/^\p{Lu}+$/u', $answer)) {
                $rejected[] = "answer_invalid_chars:$answer";
                continue;
            }

            $valid[] = ['clue' => $clue, 'answer' => $answer];
        }

        if (! empty($rejected)) {
            Log::error('CrosswordAi parsePairs: rejets', [
                'valid_count' => count($valid),
                'rejected_count' => count($rejected),
                'rejected_reasons' => array_slice($rejected, 0, 10),
            ]);
        }

        return $valid;
    }

    private function call(string $model, array $messages): string
    {
        $apiKey = config('directory.openrouter_api_key') ?: env('OPENROUTER_API_KEY');
        if (! $apiKey) {
            Log::error('CrosswordAi call: OPENROUTER_API_KEY manquante');
            return '';
        }

        Log::error("CrosswordAi call: début appel model=$model");

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

            $status = $response->status();
            Log::error("CrosswordAi call: model=$model status=$status");

            if ($response->successful()) {
                $content = (string) ($response->json('choices.0.message.content') ?? '');
                Log::error("CrosswordAi call: model=$model content_len=".mb_strlen($content)." preview=".mb_substr($content, 0, 100));
                return $content;
            }

            Log::error('CrosswordAi call: erreur API', [
                'model' => $model,
                'status' => $status,
                'body' => mb_substr((string) $response->body(), 0, 500),
            ]);
        } catch (\Throwable $e) {
            Log::error('CrosswordAi call: exception', [
                'model' => $model,
                'error' => $e->getMessage(),
            ]);
        }

        return '';
    }
}
