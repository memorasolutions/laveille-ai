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
Tu es un journaliste tech senior pour un public québécois francophone.
TOUT le contenu doit être en FRANÇAIS, même si l'article source est en anglais.
Analyse cet article et retourne UNIQUEMENT un JSON valide (aucun texte avant ou après).

{
  "score": [1-10 pertinence IA/tech pour une plateforme de veille technologique],
  "score_justification": "[1 phrase expliquant la note, en français]",
  "category": "[IA générative|Cybersécurité|Cloud|Robotique|Données|Startup|Éducation tech|Infrastructure|Autre]",
  "impact": "[Élevé|Moyen|Faible]",
  "hook": "[2-3 phrases accrocheuses résumant l'essentiel avec contexte et enjeux, 40-60 mots. Doit donner envie de lire.]",
  "key_points": ["[fait détaillé 1 avec chiffres si disponible, 15-25 mots]", "[fait détaillé 2, 15-25 mots]", "[fait détaillé 3, 15-25 mots]", "[fait détaillé 4, 15-25 mots]"],
  "why_important": "[3-4 phrases : contexte québécois/canadien, impact concret sur les professionnels, ce que ça change dans leur quotidien, 50-80 mots]",
  "audience": ["[développeurs|entreprises|éducation|grand public]"],
  "seo_title": "[titre reformulé SEO accrocheur en français, max 60 caractères]",
  "meta_description": "[description meta SEO en français, max 155 caractères]",
  "faq_question": "[question précise que les professionnels se posent sur ce sujet, en français]",
  "faq_answer": "[réponse détaillée 2-3 phrases, en français]"
}

Règles STRICTES :
- TOUT en français québécois, accents corrects — JAMAIS de contenu anglais
- Les key_points doivent être des phrases complètes et informatives (pas 5 mots vagues)
- Le hook doit être engageant comme un article de journal, pas un résumé scolaire
- Le why_important doit mentionner l'impact concret au Québec/Canada
- Score 7+ = pertinent pour une plateforme de veille IA/tech
- JSON valide uniquement, aucun texte avant ou après

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
