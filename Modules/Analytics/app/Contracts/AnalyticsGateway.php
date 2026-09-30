<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Analytics\Contracts;

/**
 * Source de données GA4 + Search Console. Contrat remplaçable : les commandes de collecte
 * dépendent de cette interface, jamais du client Google, ce qui permet un faux en test.
 */
interface AnalyticsGateway
{
    /**
     * @return array<int, array{url: string, sessions: int, active_users: int, screen_page_views: int, engaged_sessions: int, user_engagement_duration: float}>
     */
    public function fetchGa4Daily(string $date): array;

    /**
     * @return array<int, array{url: string, clicks: int, impressions: int, ctr: float, position: float}>
     */
    public function fetchGscDaily(string $date): array;
}
