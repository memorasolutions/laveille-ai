<?php

declare(strict_types=1);

return [
    'name' => 'Directory',
    'ingest_token' => env('DIRECTORY_INGEST_TOKEN'),
    'youtube_api_key' => env('YOUTUBE_API_KEY'),
    'youtube_api_keys' => array_values(array_filter(array_map('trim', explode(',', (string) env('YOUTUBE_API_KEYS', ''))))),
    'openrouter_api_key' => env('OPENROUTER_API_KEY'),

    // Product Hunt API v2 (GraphQL) — token développeur gratuit
    'producthunt_token' => env('PRODUCTHUNT_TOKEN'),

    // Flux RSS/Atom pour découverte automatique d'outils IA
    // Seuls les flux qui listent des PRODUITS/OUTILS (pas des articles de nouvelles)
    'discovery_feeds' => [
        'producthunt' => 'https://www.producthunt.com/feed',
        'hackernews_show' => 'https://hnrss.org/show',
    ],
];
