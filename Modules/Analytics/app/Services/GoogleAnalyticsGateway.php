<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Analytics\Services;

use Google\Client;
use Google\Service\AnalyticsData;
use Google\Service\AnalyticsData\DateRange;
use Google\Service\AnalyticsData\Dimension;
use Google\Service\AnalyticsData\Metric;
use Google\Service\AnalyticsData\RunReportRequest;
use Google\Service\SearchConsole;
use Google\Service\SearchConsole\SearchAnalyticsQueryRequest;
use Modules\Analytics\Contracts\AnalyticsGateway;

/**
 * Implémentation réelle : un seul Google\Client (compte de service), deux services.
 * Le client est créé À LA DEMANDE : instancier la classe ne touche ni fichier ni réseau.
 */
class GoogleAnalyticsGateway implements AnalyticsGateway
{
    private const GA4_PAGE_SIZE = 10000;

    private const GSC_PAGE_SIZE = 25000;

    private ?Client $client = null;

    public function fetchGa4Daily(string $date): array
    {
        // ACTION: rapport GA4 runReport (date + pagePath), paginé par offset
        // SELF: appel d'API, aucune génération de contenu
        // RAISON: NE demande PAS conversions/keyEvents (dépend de la config GA4, ferait échouer
        //         tout le rapport) ; la colonne conversions reste à 0 jusqu'à confirmation des key events.
        $service = new AnalyticsData($this->client());
        $property = (string) config('analytics.ga4_property_id');
        $property = str_starts_with($property, 'properties/') ? $property : 'properties/'.$property;

        $rows = [];
        $offset = 0;

        do {
            $request = new RunReportRequest;
            $request->setDateRanges([new DateRange(['startDate' => $date, 'endDate' => $date])]);
            $request->setDimensions([new Dimension(['name' => 'date']), new Dimension(['name' => 'pagePath'])]);
            $request->setMetrics(array_map(
                fn (string $name) => new Metric(['name' => $name]),
                ['sessions', 'activeUsers', 'screenPageViews', 'engagedSessions', 'userEngagementDuration']
            ));
            $request->setLimit(self::GA4_PAGE_SIZE);
            $request->setOffset($offset);

            $report = $service->properties->runReport($property, $request);
            $page = $report->getRows() ?? [];

            foreach ($page as $row) {
                $dims = $row->getDimensionValues();
                $metrics = $row->getMetricValues();
                $rows[] = [
                    'url' => (string) $dims[1]->getValue(),
                    'sessions' => (int) $metrics[0]->getValue(),
                    'active_users' => (int) $metrics[1]->getValue(),
                    'screen_page_views' => (int) $metrics[2]->getValue(),
                    'engaged_sessions' => (int) $metrics[3]->getValue(),
                    'user_engagement_duration' => (float) $metrics[4]->getValue(),
                ];
            }

            $offset += self::GA4_PAGE_SIZE;
        } while (count($page) === self::GA4_PAGE_SIZE && $offset < (int) $report->getRowCount());

        return $rows;
    }

    public function fetchGscDaily(string $date): array
    {
        // ACTION: requête searchanalytics.query (date + page), dataState final, paginée par startRow
        // SELF: appel d'API, aucune génération de contenu
        // RAISON: le contrat du brief impose dataState 'final' et rowLimit 25000
        $service = new SearchConsole($this->client());
        $site = (string) config('analytics.gsc_site_url');

        $rows = [];
        $startRow = 0;

        do {
            $request = new SearchAnalyticsQueryRequest;
            $request->setStartDate($date);
            $request->setEndDate($date);
            $request->setDimensions(['date', 'page']);
            $request->setDataState('final');
            $request->setRowLimit(self::GSC_PAGE_SIZE);
            $request->setStartRow($startRow);

            $page = $service->searchanalytics->query($site, $request)->getRows() ?? [];

            foreach ($page as $row) {
                $keys = $row->getKeys();
                $rows[] = [
                    'url' => (string) ($keys[1] ?? ''),
                    'clicks' => (int) $row->getClicks(),
                    'impressions' => (int) $row->getImpressions(),
                    'ctr' => (float) $row->getCtr(),
                    'position' => (float) $row->getPosition(),
                ];
            }

            $startRow += self::GSC_PAGE_SIZE;
        } while (count($page) === self::GSC_PAGE_SIZE);

        return $rows;
    }

    private function client(): Client
    {
        if ($this->client === null) {
            // ACTION: une SEULE authentification compte de service pour GA4 ET Search Console
            // SELF: configuration du client, moins de 5 lignes utiles
            // RAISON: décision d'architecture imposée, zéro dépendance ajoutée
            $client = new Client;
            $client->setApplicationName('MEMORA Analytics');
            $client->setAuthConfig((string) config('analytics.google_credentials'));
            $client->setScopes([AnalyticsData::ANALYTICS_READONLY, SearchConsole::WEBMASTERS_READONLY]);
            $this->client = $client;
        }

        return $this->client;
    }
}
