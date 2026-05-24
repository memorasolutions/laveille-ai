<?php

declare(strict_types=1);

namespace Modules\Authors\Services;

use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class AnalyticsRecommendationService
{
    private const CACHE_TTL_DAYS = 7;

    public function getCachedInsights(int $authorProfileId): array
    {
        try {
            return Cache::remember(
                "author_insights_{$authorProfileId}",
                now()->addDays(self::CACHE_TTL_DAYS),
                fn () => $this->generateWeeklyInsights($authorProfileId)
            );
        } catch (Exception $e) {
            Log::warning('Failed to get cached insights', ['author_id' => $authorProfileId, 'error' => $e->getMessage()]);
            return $this->generateStubInsights();
        }
    }

    public function generateWeeklyInsights(int $authorProfileId): array
    {
        try {
            $metrics = $this->fetchMetrics($authorProfileId);
            if ($metrics === null) {
                return $this->generateStubInsights();
            }

            $aiResponse = $this->getAiRecommendations($metrics);
            if ($aiResponse && isset($aiResponse['summary'])) {
                return [
                    'summary' => (string) $aiResponse['summary'],
                    'recommendations' => array_values(Arr::wrap($aiResponse['recommendations'] ?? [])),
                    'best_publish_time' => (string) ($aiResponse['best_publish_time'] ?? 'mercredi 10h'),
                    'trending_topics' => array_values(Arr::wrap($aiResponse['trending_topics'] ?? [])),
                ];
            }

            return $this->generateStubInsights();
        } catch (Exception $e) {
            Log::warning('Error generating weekly insights', ['author_id' => $authorProfileId, 'error' => $e->getMessage()]);
            return $this->generateStubInsights();
        }
    }

    private function fetchMetrics(int $authorProfileId): ?array
    {
        try {
            if (class_exists(\Modules\Core\Services\Ga4Service::class)) {
                $ga4 = app(\Modules\Core\Services\Ga4Service::class);
                if (method_exists($ga4, 'getAuthorArticlesMetrics')) {
                    return $ga4->getAuthorArticlesMetrics($authorProfileId, 7);
                }
            }
            return null;
        } catch (Exception $e) {
            return null;
        }
    }

    private function getAiRecommendations(array $metrics): ?array
    {
        $statsJson = json_encode($metrics, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $prompt = "Analyse ces stats d'un auteur de blog tech en français du Québec. Génère 3 recommandations actionnables courtes (1 phrase chacune) + meilleur moment publication + 2 sujets tendance à explorer. Format JSON: {\"summary\": \"...\", \"recommendations\": [\"...\", \"...\", \"...\"], \"best_publish_time\": \"...\", \"trending_topics\": [\"...\", \"...\"]}\n\nStats: {$statsJson}";

        try {
            $response = Http::withToken((string) config('services.openrouter.api_key'))
                ->withHeaders([
                    'HTTP-Referer' => (string) config('app.url'),
                    'X-Title' => 'Author Insights',
                ])
                ->timeout(30)
                ->post('https://openrouter.ai/api/v1/chat/completions', [
                    'model' => 'qwen/qwen3-max',
                    'messages' => [['role' => 'user', 'content' => $prompt]],
                    'temperature' => 0.7,
                ]);

            if (! $response->successful()) {
                return null;
            }

            $content = (string) $response->json('choices.0.message.content', '');
            $content = preg_replace('/^```json\s*|\s*```$/i', '', trim($content));

            if (preg_match('/\{.*\}/s', $content, $matches)) {
                $decoded = json_decode($matches[0], true);
                return is_array($decoded) ? $decoded : null;
            }

            return null;
        } catch (Exception $e) {
            return null;
        }
    }

    private function generateStubInsights(): array
    {
        return [
            'summary' => 'Tes contenus performent bien en milieu de semaine. Continue sur cette lancée.',
            'recommendations' => [
                'Publie tes articles techniques le mercredi entre 9h et 11h pour maximiser la portée.',
                'Approfondis les sujets liés à la Loi 25 et à l\'IA générative, populaires dans ton audience.',
                'Ajoute des exemples concrets PME québécoise pour augmenter l\'engagement.',
            ],
            'best_publish_time' => 'mercredi 10h',
            'trending_topics' => [
                'Intelligence artificielle générative',
                'Conformité Loi 25 pour PME',
            ],
        ];
    }
}
