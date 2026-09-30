<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Analytics\Console;

use Modules\Analytics\Contracts\AnalyticsGateway;
use Modules\Analytics\Models\GscDaily;
use Modules\Analytics\Services\ContentUrlResolver;

class SyncGscCommand extends AbstractSyncCommand
{
    protected $signature = 'analytics:sync-gsc {--date= : Date AAAA-MM-JJ (défaut : il y a 3 jours, America/Toronto)}';

    protected $description = 'Collecte les métriques Search Console quotidiennes par URL (idempotent, inerte si non configuré)';

    // ACTION: défaut à J-3 pour GSC (au lieu de J-1)
    // SELF: surcharge de 3 lignes
    // RAISON: Search Console finalise ses données environ 2 jours plus tard ; à J-1 le rapport est souvent vide
    protected function defaultDaysAgo(): int
    {
        return 3;
    }

    protected function source(): string
    {
        return 'gsc';
    }

    protected function requiredConfig(): array
    {
        return [
            'ANALYTICS_GSC_SITE_URL' => (string) config('analytics.gsc_site_url'),
            'GOOGLE_APPLICATION_CREDENTIALS' => (string) config('analytics.google_credentials'),
        ];
    }

    protected function model(): string
    {
        return GscDaily::class;
    }

    protected function fetch(AnalyticsGateway $gateway, string $date): array
    {
        return $gateway->fetchGscDaily($date);
    }

    protected function aggregate(array $rows, ContentUrlResolver $resolver): array
    {
        // ACTION: fusionner les URL qui se normalisent à l'identique (paramètres, barre finale)
        // SELF: agrégation de sommes, aucune génération
        // RAISON: clics et impressions s'additionnent ; le CTR se recalcule et la position se pondère par les impressions
        $sum = [];
        foreach ($rows as $row) {
            $raw = trim((string) ($row['url'] ?? ''));
            if ($raw === '') {
                continue;
            }
            $url = $resolver->normalize($raw);
            $sum[$url] ??= ['clicks' => 0, 'impressions' => 0, 'weighted_position' => 0.0];
            $sum[$url]['clicks'] += (int) $row['clicks'];
            $sum[$url]['impressions'] += (int) $row['impressions'];
            $sum[$url]['weighted_position'] += (float) $row['position'] * (int) $row['impressions'];
        }

        $out = [];
        foreach ($sum as $url => $s) {
            $out[$url] = [
                'clicks' => $s['clicks'],
                'impressions' => $s['impressions'],
                'ctr' => $s['impressions'] > 0 ? round($s['clicks'] / $s['impressions'], 4) : 0,
                'position' => $s['impressions'] > 0 ? round($s['weighted_position'] / $s['impressions'], 2) : 0,
            ];
        }

        return $out;
    }
}
