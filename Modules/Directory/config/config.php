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

    /*
     * Pricing audit (multi-source consensus) — drivers configurables.
     *
     * screenshot_enabled : active le PlaywrightScreenshotSource (Browsershot + Chromium).
     *   PRÉ-REQUIS prod : Node.js + Chromium installés et BROWSERSHOT_NODE_PATH défini.
     *   En cPanel partagé sans Node, laisser à false : audit fonctionne quand même via
     *   BrowserFetch (poids 2) + PpSearch direct API (poids 1) + LLM (poids 1) → consensus 4/4.
     *   Activer via DIRECTORY_PRICING_AUDIT_SCREENSHOT=true dans .env quand Node prod prêt.
     */
    'pricing_audit' => [
        'screenshot_enabled' => (bool) env('DIRECTORY_PRICING_AUDIT_SCREENSHOT', false),
    ],
];
