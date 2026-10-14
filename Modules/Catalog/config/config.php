<?php

// Author: MEMORA solutions, https://memora.solutions ; info@memora.ca

declare(strict_types=1);

return [
    'name' => 'Catalog',
    'moodle_ws_url' => env('MOODLE_WS_URL', 'https://laveille.ai/formations/webservice/rest/server.php'),
    'moodle_ws_token' => env('MOODLE_WS_TOKEN'),
    'moodle_base_url' => env('MOODLE_BASE_URL', 'https://laveille.ai/formations'),
    'cache_ttl' => (int) env('CATALOG_CACHE_TTL', 1800),
    'http_timeout' => (int) env('CATALOG_HTTP_TIMEOUT', 15),

    // Catégories Moodle exclues de la vitrine publique /cours, désignées par leur idnumber (liste
    // séparée par des virgules dans le .env). Sert aux cours PRIVÉS (ex. une formation réservée à
    // des inscrits) : la catégorie reste visible=1 côté Moodle - pour que les inscrits gardent
    // l'accès, une catégorie masquée forçant visible=0 le leur retirerait - mais ses cours
    // n'apparaissent jamais dans le catalogue public. Défaut « prive » : il suffit de créer une
    // catégorie « Formations privées » avec l'idnumber « prive » pour l'exclure.
    'excluded_category_idnumbers' => array_values(array_filter(array_map(
        fn ($v) => strtolower(trim((string) $v)),
        explode(',', (string) env('CATALOG_EXCLUDED_CATEGORY_IDNUMBERS', 'prive'))
    ), fn ($v) => $v !== '')),

    // Vitrine en noindex tant que les cours sont des démos (pré-lancement). Basculer à false
    // dans le .env de production (CATALOG_NOINDEX=false) quand de vrais cours remplaceront les
    // démos : /cours redeviendra alors indexable et un atout de référencement.
    'noindex' => filter_var(env('CATALOG_NOINDEX', true), FILTER_VALIDATE_BOOLEAN),

    // Formulaire public « demander un cours personnalisé » (vitrine /cours). Activable/désactivable
    // sans casse : à false, la route /cours/demande n'est pas déclarée (404) et aucun lien ne s'affiche.
    'request_enabled' => filter_var(env('CATALOG_REQUEST_ENABLED', true), FILTER_VALIDATE_BOOLEAN),
];
