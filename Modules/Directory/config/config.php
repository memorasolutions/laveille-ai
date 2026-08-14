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

    /*
     * S90 #43 — Alertes catégorie hebdomadaires.
     *
     * Phase 2 livrée (UI + DB + routes). Phase 3 (cron + email Brevo) NON livrée.
     * Tant que Phase 3 n'est pas prête, l'UI reste cachée. Les routes/controller/DB
     * sont en place mais dormantes (pas de chemin vers elles depuis l'UI publique).
     *
     * Pour réactiver quand Phase 3 sera prête : DIRECTORY_CATEGORY_ALERTS_ENABLED=true
     */
    'category_alerts' => [
        'enabled' => (bool) env('DIRECTORY_CATEGORY_ALERTS_ENABLED', false),
    ],

    /*
     * #232 (v1.17.2) — Classement contributeurs annuaire.
     *
     * Désactivé tant qu'un seul contributor (user solo) — le classement est vide
     * d'intérêt. Réactivation 1-flag dès que ≥3 contributeurs réguliers : passer
     * DIRECTORY_LEADERBOARD_ENABLED=true (.env prod), ou retirer la clé pour
     * retomber sur le défaut false.
     *
     * Effet quand false :
     *   - Route /annuaire/classement → redirect 302 /annuaire + flash info
     *   - Liens menu desktop + offcanvas mobile + footer cachés (pas même <li>)
     *   - Pas de référence dans sitemap (jamais référencée à ce jour)
     */
    'leaderboard' => [
        'enabled' => (bool) env('DIRECTORY_LEADERBOARD_ENABLED', false),
    ],

    /*
     * tools:reenrich-stale — régénération mensuelle de la description des fiches périmées
     * (>3 mois sans mise à jour), via recherche sonar-pro puis rédaction qwen3-max.
     *
     * Désactivé par défaut (2026-08-14) : sur la fiche SceneNote, la commande a écrit
     * « aucune version officielle de cet outil ne dispose d'un site web dédié » alors que
     * l'adresse du produit figurait DÉJÀ dans la fiche au moment de la régénération — le
     * modèle avait la référence sous les yeux et a quand même affirmé l'absence. Le
     * catalogue compte ~2355 fiches ; sans ce verrou, la même faussete peut être reproduite
     * sur n'importe quel produit nommé, à chaque exécution mensuelle. Un réglage qui protège
     * contre un défaut connu doit avoir la protection comme valeur par défaut (même principe
     * que directory.category_alerts / directory.leaderboard ci-dessus, et que
     * news.seo_prune.enabled) : si DIRECTORY_REENRICH_STALE_ENABLED est absent d'un
     * environnement, le traitement reste éteint plutôt que de reprendre silencieusement sa
     * génération fautive. Réactivation seulement après correctif du prompt (interdiction
     * d'affirmer une absence, injection des données déjà connues) et porte de qualité
     * (EnrichmentQualityGate) : DIRECTORY_REENRICH_STALE_ENABLED=true.
     */
    'reenrich_stale' => [
        'enabled' => (bool) env('DIRECTORY_REENRICH_STALE_ENABLED', false),

        // Porte de qualité (EnrichmentQualityGate) avant persistance d'une fiche régénérée.
        // Sous-interrupteurs distincts pour pouvoir désactiver un seul contrôle en cas de faux
        // positif mesuré, sans rouvrir l'ensemble de la porte.
        'quality_gate_enabled' => (bool) env('DIRECTORY_REENRICH_STALE_QUALITY_GATE_ENABLED', true),
        'entity_check_enabled' => (bool) env('DIRECTORY_REENRICH_STALE_ENTITY_CHECK_ENABLED', true),
    ],
];
