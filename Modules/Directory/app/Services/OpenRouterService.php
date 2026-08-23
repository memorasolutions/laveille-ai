<?php

namespace Modules\Directory\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Core\Services\OpenRouterPrivacy;

/**
 * ACTION : appels de recherche (sonar-pro) et de rédaction/classification OpenRouter du module
 * Directory. Tous passent par OpenRouterPrivacy::applyTo() (provider.data_collection=deny +
 * provider.zdr=true, rétention nulle - règle non négociable posée le 2026-08-13).
 * RAISON : qwen/qwen3-max, utilisé en dur jusqu'au 2026-08-23 pour generate()/classifyPricing(),
 * n'a AUCUN fournisseur conforme à cette politique (mesuré en production : HTTP 404 "No
 * endpoints found matching your data policy (Zero data retention)") - l'enrichissement était
 * cassé en silence depuis le 2026-08-13. La rédaction passe désormais par une CASCADE de modèles
 * conformes (config('directory.openrouter_writer_models')) : sur un refus de politique de
 * données, passage IMMÉDIAT au modèle suivant (définitif, inutile de retenter) ; sur une erreur
 * transitoire, réessai du MÊME modèle avant de passer au suivant. Tout échec est journalisé sur
 * le canal dédié 'directory_enrichment' (config/logging.php, niveau 'info' fixé en dur,
 * indépendant de LOG_LEVEL - sinon avalé en production, exactement la panne qui a motivé ce
 * correctif).
 */
class OpenRouterService
{
    private string $apiUrl = 'https://openrouter.ai/api/v1/chat/completions';

    public function search(string $query): string
    {
        return $this->call(['perplexity/sonar-pro'], [
            ['role' => 'user', 'content' => $query],
        ]);
    }

    public function classifyPricing(string $userPrompt): string
    {
        $messages = [
            ['role' => 'system', 'content' => 'You are a pricing classifier. Output ONLY valid JSON matching the requested schema. Never include preamble, markdown fences, or explanations outside the JSON.'],
            ['role' => 'user', 'content' => $userPrompt],
        ];

        return $this->call($this->writerModels(), $messages);
    }

    public function generate(string $prompt, string $systemPrompt = ''): string
    {
        $messages = [];
        if ($systemPrompt) {
            $messages[] = ['role' => 'system', 'content' => $systemPrompt];
        }
        $messages[] = ['role' => 'user', 'content' => $prompt];

        return $this->call($this->writerModels(), $messages);
    }

    public function summarize(string $text, int $maxWords = 200): string
    {
        return $this->generate(
            "Résume ce texte en français en maximum {$maxWords} mots. Indique la langue originale si ce n'est pas du français :\n\n{$text}"
        );
    }

    /**
     * Cascade de modèles de rédaction/classification - voir Modules/Directory/config/config.php
     * (clé openrouter_writer_models) pour l'ordre retenu et sa justification. Le repli codé ici
     * ne sert que si la clé de config venait à manquer entièrement ; il reprend volontairement le
     * même premier choix que la config plutôt que qwen/qwen3-max, à l'origine de ce correctif.
     *
     * @return array<int, string>
     */
    private function writerModels(): array
    {
        return config('directory.openrouter_writer_models', ['deepseek/deepseek-v4-flash']);
    }

    /**
     * Essaie chaque modèle de la cascade dans l'ordre et retourne le premier résultat non vide.
     * Journalise sur le canal dédié et retourne '' si tous les modèles échouent.
     *
     * @param  array<int, string>  $models
     * @param  array<int, array<string, string>>  $messages
     */
    private function call(array $models, array $messages, int $maxRetries = 2): string
    {
        $apiKey = config('directory.openrouter_api_key');
        if (! $apiKey) {
            Log::channel('directory_enrichment')->warning('OpenRouterService : clé API manquante');

            return '';
        }

        foreach ($models as $model) {
            $result = $this->callModel($model, $messages, $apiKey, $maxRetries);
            if ($result !== '') {
                return $result;
            }
        }

        if ($models !== []) {
            Log::channel('directory_enrichment')->warning(
                'OpenRouterService : cascade épuisée, tous les modèles ont échoué',
                ['models' => $models]
            );
        }

        return '';
    }

    /**
     * Tente UN modèle. Réessaie jusqu'à $maxRetries fois pour une erreur transitoire (5xx, 429,
     * exception réseau). Un refus de politique de données (HTTP 404, "data policy" dans le corps
     * - aucun fournisseur ne sert ce modèle en rétention nulle) est définitif pour ce modèle :
     * retour immédiat sans réessai, pour laisser call() passer au modèle suivant sans délai.
     *
     * @param  array<int, array<string, string>>  $messages
     */
    private function callModel(string $model, array $messages, string $apiKey, int $maxRetries): string
    {
        $attempt = 0;

        while ($attempt <= $maxRetries) {
            try {
                $response = Http::timeout(60)
                    ->withHeaders([
                        'Authorization' => "Bearer {$apiKey}",
                        'HTTP-Referer' => 'https://laveille.ai',
                        'X-Title' => 'LaVeille',
                    ])
                    ->post($this->apiUrl, OpenRouterPrivacy::applyTo([
                        'model' => $model,
                        'messages' => $messages,
                    ]));

                if ($response->successful()) {
                    return $response->json('choices.0.message.content') ?? '';
                }

                $reason = $this->extractFailureReason($response);

                if ($this->isDataPolicyRefusal($response, $reason)) {
                    Log::channel('directory_enrichment')->warning(
                        "OpenRouterService : modèle {$model} refusé par la politique de données (HTTP {$response->status()}), passage au modèle suivant sans réessai",
                        ['model' => $model, 'status' => $response->status(), 'reason' => $reason]
                    );

                    return '';
                }

                Log::channel('directory_enrichment')->warning(
                    "OpenRouterService : erreur API pour le modèle {$model} (HTTP {$response->status()})",
                    ['model' => $model, 'status' => $response->status(), 'reason' => $reason, 'attempt' => $attempt + 1]
                );
            } catch (\Throwable $e) {
                Log::channel('directory_enrichment')->warning(
                    "OpenRouterService : exception pour le modèle {$model}",
                    ['model' => $model, 'error' => $e->getMessage(), 'attempt' => $attempt + 1]
                );
            }

            $attempt++;
            if ($attempt <= $maxRetries) {
                sleep(1);
            }
        }

        return '';
    }

    /**
     * Extrait le motif réel d'un échec depuis le corps de la réponse (jamais un symptôme
     * générique) : d'abord `error.message` (format d'erreur OpenRouter standard), sinon le corps
     * brut tronqué.
     */
    private function extractFailureReason(Response $response): string
    {
        $message = $response->json('error.message');
        if (is_string($message) && $message !== '') {
            return $message;
        }

        $body = trim($response->body());

        return $body !== '' ? mb_substr($body, 0, 300) : 'corps de réponse vide';
    }

    /**
     * Refus de politique de données OpenRouter : HTTP 404 dont le corps mentionne "data policy"
     * (ex. "No endpoints found matching your data policy (Zero data retention)"). Définitif pour
     * le modèle interrogé - jamais transitoire, jamais utile de réessayer.
     */
    private function isDataPolicyRefusal(Response $response, string $reason): bool
    {
        return $response->status() === 404 && str_contains(strtolower($reason), 'data policy');
    }
}
