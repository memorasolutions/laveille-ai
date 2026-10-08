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
];
