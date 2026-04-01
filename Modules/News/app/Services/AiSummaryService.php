<?php

declare(strict_types=1);

namespace Modules\News\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiSummaryService
{
    private const DEFAULT_MODELS = [
        'deepseek/deepseek-chat',
        'openai/gpt-4o-mini',
        'google/gemma-3-27b-it:free',
    ];

    private const AI_KEYWORDS = [
        'intelligence artificielle', 'ia ', ' ai ', 'artificial intelligence', 'machine learning',
        'deep learning', 'chatgpt', 'openai', 'claude', 'gemini', 'llm', 'gpt', 'neural', 'algorithme',
        'robot', 'automatisation', 'données', 'data', 'cloud', 'cybersécurité', 'blockchain',
        'apprentissage automatique', 'modèle de langage', 'prompt', 'tech', 'numérique', 'digital',
        'startup', 'innovation', 'logiciel', 'software', 'app ', 'application', 'coding', 'développeur',
        'api', 'saas', 'fintech', 'edtech', 'biotech',
    ];

    public function isRelevant(string $title, string $text): bool
    {
        $combined = mb_strtolower($title . ' ' . $text);

        foreach (self::AI_KEYWORDS as $kw) {
            if (str_contains($combined, $kw)) {
                return true;
            }
        }

        return false;
    }

    public function structuredSummary(string $title, string $text, string $language = 'fr'): ?array
    {
        $apiKey = config('services.openrouter.api_key', env('OPENROUTER_API_KEY'));
        if (! $apiKey) {
            Log::warning('OPENROUTER_API_KEY non configurée.');
            return null;
        }

        $models = config('services.openrouter.summary_models', self::DEFAULT_MODELS);
        $truncatedText = mb_substr($text, 0, 3000);
        $prompt = $this->buildPrompt($title, $truncatedText, $language);

        foreach ($models as $index => $model) {
            try {
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $apiKey,
                ])->timeout(45)->post('https://openrouter.ai/api/v1/chat/completions', [
                    'model' => $model,
                    'messages' => [['role' => 'user', 'content' => $prompt]],
                    'temperature' => 0.3,
                ]);

                $data = $response->json();

                if ($response->successful() && isset($data['choices'][0]['message']['content'])) {
                    $content = trim($data['choices'][0]['message']['content']);

                    if (preg_match('/\{[\s\S]*\}/', $content, $matches)) {
                        $parsed = json_decode($matches[0], true);
                        if ($parsed && isset($parsed['score']) && isset($parsed['hook'])) {
                            $parsed['_model'] = $model;
                            return $parsed;
                        }
                    }

                    Log::warning("News AI: JSON invalide [{$model}]: " . mb_substr($content, 0, 200));
                } else {
                    Log::warning("News AI failed [{$model}]: " . ($data['error']['message'] ?? 'Réponse invalide'));
                }
            } catch (\Throwable $e) {
                Log::warning("News AI exception [{$model}]: {$e->getMessage()}");
            }

            if ($index < count($models) - 1) {
                sleep(1);
            }
        }

        Log::error('Tous les modèles de résumé IA ont échoué.');
        return null;
    }

    public function summarize(string $text, string $language = 'fr'): ?string
    {
        $result = $this->structuredSummary('', $text, $language);
        return $result ? ($result['hook'] ?? null) : null;
    }

    private function buildPrompt(string $title, string $text, string $language): string
    {
        $lang = $language === 'fr' ? 'français québécois' : 'English';

        return <<<PROMPT
Tu es un journaliste tech spécialisé pour un public québécois francophone.
Analyse cet article et retourne UNIQUEMENT un JSON valide (aucun texte avant ou après).

Structure exacte requise :
{
  "score": [1-10 pertinence pour une plateforme de veille IA et technologie],
  "score_justification": "[1 phrase expliquant la note]",
  "category": "[une seule parmi : IA générative, Cybersécurité, Cloud, Robotique, Données, Startup, Éducation tech, Développement, Hardware, Autre]",
  "impact": "[Élevé ou Moyen ou Faible]",
  "hook": "[1-2 phrases accrocheuses résumant l'essentiel, 25-35 mots, en {$lang}]",
  "key_points": ["[fait clé 1, 10-15 mots]", "[fait clé 2]", "[fait clé 3]"],
  "why_important": "[2-3 phrases, impact concret pour professionnels et entreprises québécoises, 30-50 mots, en {$lang}]",
  "audience": ["[2-4 audiences parmi : développeurs, entreprises, éducation, grand public, startups]"],
  "seo_title": "[titre reformulé SEO en {$lang}, max 60 caractères]",
  "meta_description": "[description SEO en {$lang}, max 155 caractères]",
  "faq_question": "[question que les gens se posent sur ce sujet, en {$lang}]",
  "faq_answer": "[réponse concise 1-2 phrases, en {$lang}]"
}

Règles :
- Score 7+ = pertinent pour veille IA/tech. Score 1-6 = pas assez pertinent.
- Toujours en {$lang}, accents corrects.
- JSON valide uniquement. Pas de markdown, pas de commentaires.

Titre : {$title}

Article :
{$text}
PROMPT;
    }
}
