<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

return [
    'name' => 'Ads',

    // ACTION: rendre la pub des pages d'outils repliable + mémorisée (cookie 7 jours, ouverte par défaut).
    // RAISON: proposition du fondateur 2026-10-08 (visible mais réductible, à la manière du bloc « En bref »).
    // Mettre ADS_TOOL_COLLAPSIBLE=false pour revenir au rendu simple non repliable : zéro casse, désactivable.
    'tool_collapsible' => (bool) env('ADS_TOOL_COLLAPSIBLE', true),

    // ACTION: unité AdSense in-feed de /actualites, répétée dans la grille des cartes.
    // RAISON: demande fondateur 2026-10-10. Première unité à la carte `first_position`, puis toutes les
    // `interval` cartes (3, 9, 15... par défaut). Ne jamais enchaîner deux annonces. interval = 0 : aucune
    // insertion (désactivé). Piloté par ADS_INFEED_FIRST_POSITION et ADS_INFEED_INTERVAL.
    'infeed' => [
        'first_position' => (int) env('ADS_INFEED_FIRST_POSITION', 3),
        'interval' => (int) env('ADS_INFEED_INTERVAL', 6),
    ],
];
