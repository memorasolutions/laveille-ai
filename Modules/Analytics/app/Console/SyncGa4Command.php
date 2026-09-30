<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Analytics\Console;

use Modules\Analytics\Contracts\AnalyticsGateway;
use Modules\Analytics\Models\Ga4Daily;
use Modules\Analytics\Services\ContentUrlResolver;

class SyncGa4Command extends AbstractSyncCommand
{
    protected $signature = 'analytics:sync-ga4 {--date= : Date AAAA-MM-JJ (défaut : hier, America/Toronto)}';

    protected $description = 'Collecte les métriques GA4 quotidiennes par URL (idempotent, inerte si non configuré)';

    protected function source(): string
    {
        return 'ga4';
    }

    protected function requiredConfig(): array
    {
        return [
            'ANALYTICS_GA4_PROPERTY_ID' => (string) config('analytics.ga4_property_id'),
            'GOOGLE_APPLICATION_CREDENTIALS' => (string) config('analytics.google_credentials'),
        ];
    }

    protected function model(): string
    {
        return Ga4Daily::class;
    }

    protected function fetch(AnalyticsGateway $gateway, string $date): array
    {
        return $gateway->fetchGa4Daily($date);
    }

    protected function aggregate(array $rows, ContentUrlResolver $resolver): array
    {
        // ACTION: fusionner les pagePath qui se normalisent vers la même URL (ex. /a et /a/)
        // SELF: agrégation de sommes, aucune génération
        // RAISON: sans fusion, updateOrCreate écraserait la première ligne par la seconde au lieu de les additionner
        $sum = [];
        foreach ($rows as $row) {
            $raw = trim((string) ($row['url'] ?? ''));
            if ($raw === '') {
                continue;
            }
            $url = $resolver->normalize($raw);
            $sum[$url] ??= ['sessions' => 0, 'active_users' => 0, 'screen_page_views' => 0, 'engaged_sessions' => 0, 'duration' => 0.0];
            $sum[$url]['sessions'] += (int) $row['sessions'];
            $sum[$url]['active_users'] += (int) $row['active_users'];
            $sum[$url]['screen_page_views'] += (int) $row['screen_page_views'];
            $sum[$url]['engaged_sessions'] += (int) $row['engaged_sessions'];
            $sum[$url]['duration'] += (float) $row['user_engagement_duration'];
        }

        $out = [];
        foreach ($sum as $url => $s) {
            $out[$url] = [
                'sessions' => $s['sessions'],
                'active_users' => $s['active_users'],
                'screen_page_views' => $s['screen_page_views'],
                'engaged_sessions' => $s['engaged_sessions'],
                // userEngagementDuration est un TOTAL en secondes : moyenne par session, garde contre la division par 0.
                'avg_engagement_time_seconds' => $s['sessions'] > 0 ? (int) round($s['duration'] / $s['sessions']) : 0,
                // conversions : volontairement à 0, à alimenter quand les key events GA4 seront confirmés.
                'conversions' => 0,
            ];
        }

        return $out;
    }
}
