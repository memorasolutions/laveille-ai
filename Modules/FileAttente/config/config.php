<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 */

declare(strict_types=1);

return [
    'name' => 'FileAttente',

    // Cadences d'interrogation de l'endpoint d'état versionné (secondes).
    'cadences' => [
        'affichage' => 2,
        'animateur' => 3,
        'participant' => 15,
    ],

    // Quotas PAR TABLEAU (jamais par IP : une classe partage une IP).
    'max_participants' => 60,
    'max_name_len' => 40,
    'boards_per_hour' => 20,

    // Plafond de vie d'un tableau depuis sa création, sans prolongation par consultation.
    'purge_hours' => 24,

    // Carte de l'outil sur /outils (injectée à l'exécution par le ServiceProvider, jamais seedée en base).
    'show_in_tools' => true,

    // Garde « en construction » : 503 pour tous sauf le super-administrateur (FILEATTENTE_UNDER_CONSTRUCTION=false pour lancer).
    'under_construction' => env('FILEATTENTE_UNDER_CONSTRUCTION', true),

    // Entête de marque (jamais de nom de site codé en dur dans les vues).
    'brand' => [
        'name' => env('FILEATTENTE_BRAND_NAME'),          // défaut : config('app.name')
        'logo' => env('FILEATTENTE_BRAND_LOGO', '/images/logo-eye.svg'),
        'logo_light' => env('FILEATTENTE_BRAND_LOGO_LIGHT', '/images/logo-eye-white.svg'), // variante pour fond sombre
        'url' => env('FILEATTENTE_BRAND_URL'),            // défaut : config('app.url')
    ],
];
