<?php

declare(strict_types=1);

// Configuration du package Statut — MEMORA solutions

return [
    /*
    |--------------------------------------------------------------------------
    | Activation de la page de statut
    |--------------------------------------------------------------------------
    |
    | Détermine si la page de statut est activée. Lorsque false, les routes
    | et les publications du package ne sont pas chargées.
    |
    */
    'enabled' => env('STATUT_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Fournisseur de surveillance
    |--------------------------------------------------------------------------
    |
    | Le fournisseur à utiliser. Valeur actuelle supportée : "robotalp".
    | D'autres fournisseurs pourront être ajoutés sans modifier la page.
    |
    */
    'provider' => env('STATUT_PROVIDER', 'robotalp'),

    /*
    |--------------------------------------------------------------------------
    | Chemin de la route
    |--------------------------------------------------------------------------
    |
    | Le préfixe URI pour accéder à la page de statut publique.
    |
    */
    'route_path' => env('STATUT_ROUTE_PATH', '/statut'),

    /*
    |--------------------------------------------------------------------------
    | Middleware de la route
    |--------------------------------------------------------------------------
    |
    | Liste des middleware à appliquer à la route de statut.
    |
    */
    'route_middleware' => ['web'],

    /*
    |--------------------------------------------------------------------------
    | Layout de l'application hôte
    |--------------------------------------------------------------------------
    |
    | Le layout Blade que la page de statut étendra. Permet une intégration
    | naturelle dans le design du site hôte.
    |
    */
    'layout' => env('STATUT_LAYOUT', 'layouts.app'),

    /*
    |--------------------------------------------------------------------------
    | Section du layout
    |--------------------------------------------------------------------------
    |
    | Nom de la section du layout dans laquelle le contenu sera injecté.
    |
    */
    'section' => env('STATUT_SECTION', 'content'),

    /*
    |--------------------------------------------------------------------------
    | Durée de cache (en secondes)
    |--------------------------------------------------------------------------
    |
    | Durée de mise en cache des données du fournisseur de surveillance.
    | Réduit la charge sur l'API distante.
    |
    */
    'cache_ttl_seconds' => env('STATUT_CACHE_TTL', 60),

    /*
    |--------------------------------------------------------------------------
    | Langue par défaut
    |--------------------------------------------------------------------------
    |
    | Langue utilisée si l'application hôte n'en impose pas une autre.
    |
    */
    'locale_default' => 'fr',

    /*
    |--------------------------------------------------------------------------
    | Marque (branding)
    |--------------------------------------------------------------------------
    |
    | Informations de branding affichées sur la page de statut.
    |
    */
    'brand' => [
        'name' => env('STATUT_BRAND_NAME', 'MEMORA solutions'),
        'url'  => env('STATUT_BRAND_URL', 'https://memora.solutions'),
        'logo' => env('STATUT_BRAND_LOGO'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Moniteurs à masquer
    |--------------------------------------------------------------------------
    |
    | Liste d'identifiants de moniteurs à exclure de l'affichage public.
    | Définir via une chaîne CSV : STATUT_HIDDEN_MONITORS="123,456".
    |
    */
    'monitors' => [
        'hidden' => array_values(array_filter(array_map('trim', explode(',', (string) env('STATUT_HIDDEN_MONITORS', ''))))),
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuration des fournisseurs
    |--------------------------------------------------------------------------
    |
    | Paramètres spécifiques à chaque fournisseur de surveillance.
    |
    */
    'providers' => [
        'robotalp' => [
            'base_url'     => env('STATUT_ROBOTALP_BASE_URL', 'https://api.robotalp.com'),
            'api_key'      => env('STATUT_ROBOTALP_API_KEY'),
            'workspace_id' => env('STATUT_ROBOTALP_WORKSPACE_ID'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Fenêtres de disponibilité
    |--------------------------------------------------------------------------
    |
    | Périodes d'uptime à afficher sur la page (ex : 24h, 7j, 30j, 90j).
    |
    */
    'uptime_windows' => ['24h', '7d', '30d', '90d'],
];
