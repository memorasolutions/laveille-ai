<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

return [
    'name' => 'Api',

    // Source unique d'attribution des réponses publiques (DRY) : réutilisée par les Resources.
    'attribution' => [
        'source' => env('API_ATTRIBUTION_SOURCE', 'La veille (laveille.ai)'),
        'license' => env('API_ATTRIBUTION_LICENSE', 'CC BY 4.0'),
        'license_url' => env('API_ATTRIBUTION_LICENSE_URL', 'https://creativecommons.org/licenses/by/4.0/'),
    ],
];
