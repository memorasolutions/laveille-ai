<?php

declare(strict_types=1);

namespace Modules\News\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Settings\Facades\Settings;

class AiSummaryService
{
    private const DEFAULT_MODELS = [
        'deepseek/deepseek-chat',
        'openai/gpt-4o-mini',
        'google/gemma-3-27b-it:free',
    ];

    /**
     * Vérifie si l'article est pertinent via mots-clés (pré-filtre gratuit).
     */
    public function isRelevant(string $title, string $text): bool
    {
        $combined = mb_strtolower($title . ' ' . $text);
        $keywords = ['intelligence artificielle', 'ia ', ' ai ', 'artificial intelligence', 'machine learning',
            'deep learning', 'chatgpt', 'openai', 'claude', 'gemini', 'llm', 'gpt', 'neural', 'algorithme',
            'robot', 'automatisation', 'données', 'data', 'cloud', 'cybersécurité', 'blockchain',
            'apprentissage automatique', 'modèle de langage', 'prompt', 'tech', 'numérique', 'digital',
            'startup', 'innovation', 'logiciel', 'software', 'app ', 'application', 'coding', 'développeur',
            'api', 'saas', 'fintech', 'edtech', 'biotech'];

        foreach ($keywords as $kw) {
            if (str_contains($combined, $kw)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Score + résumé structuré en 1 seul appel API.
     * Retourne le JSON parsé ou null si échec.
     */
    public function scoreAndSummarize(string $title, string $text, string $language = 'fr'): ?array
    {
        $apiKey = config('services.openrouter.api_key', env('OPENROUTER_API_KEY'));
        if (! $apiKey) {
            Log::warning('OPENROUTER_API_KEY non configurée.');
            return null;
        }

        $models = config('services.openrouter.summary_models', self::DEFAULT_MODELS);
        $truncatedText = mb_substr($text, 0, 2000);
        $minScore = (int) Settings::get('news.min_relevance_score', 7);

        $prompt = <<<PROMPT
Tu es un journaliste tech spécialisé pour un public québécois francophone.
Analyse cet article et retourne UNIQUEMENT un JSON valide (aucun texte avant ou après).

{
  "score": [1-10 pertinence IA/tech pour une plateforme de veille technologique],
  "score_justification": "[1 phrase expliquant la note]",
  "category": "[IA générative|Cybersécurité|Cloud|Robotique|Données|Startup|Éducation tech|Infrastructure|Autre]",
  "impact": "[Élevé|Moyen|Faible]",
  "hook": "[1-2 phrases accrocheuses résumant l'essentiel, 25-35 mots]",
  "key_points": ["[fait clé 1, 10-15 mots]", "[fait clé 2]", "[fait clé 3]"],
  "why_important": "[2-3 phrases : impact concret pour professionnels et entreprises québécoises, 30-50 mots]",
  "audience": ["[développeurs|entreprises|éducation|grand public]"],
  "seo_title": "[titre reformulé SEO, max 60 caractères]",
  "meta_description": "[description meta SEO, max 155 caractères]",
  "faq_question": "[question que les gens se posent sur ce sujet]",
  "faq_answer": "[réponse concise 1-2 phrases]"
}

Règles :
- Score 7+ = pertinent pour une plateforme de veille IA/tech québécoise
- Score 1-6 = pas assez pertinent
- Français québécois, accents corrects
- JSON valide uniquement

Titre : {$title}
Article :
{$truncatedText}
PROMPT;

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
                    // Nettoyer le markdown si présent
                    $content = preg_replace('/^```json?\s*/i', '', $content);
                    $content = preg_replace('/\s*```$/', '', $content);

                    $parsed = json_decode($content, true);
                    if ($parsed && isset($parsed['score'])) {
                        Log::info("News summary OK [{$model}]: score={$parsed['score']} - {$title}");
                        return $parsed;
                    }

                    Log::warning("News summary invalid JSON [{$model}]: " . mb_substr($content, 0, 200));
                } else {
                    $errorMessage = $data['error']['message'] ?? 'Réponse invalide';
                    Log::warning("News summary API error [{$model}]: {$errorMessage}");
                }
            } catch (\Throwable $e) {
                Log::warning("News summary exception [{$model}]: {$e->getMessage()}");
            }

            if ($index < count($models) - 1) {
                sleep(1);
            }
        }

        Log::error("Tous les modèles ont échoué pour : {$title}");
        return null;
    }

    /**
     * Ancien méthode gardée pour compatibilité. Utiliser scoreAndSummarize() pour les nouveaux articles.
     */
    public function summarize(string $text, string $language = 'fr'): ?string
    {
        $result = $this->scoreAndSummarize('', $text, $language);
        return $result ? ($result['hook'] ?? null) : null;
    }
}
