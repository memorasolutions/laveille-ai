<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Configuration PWA centralisée
 */

declare(strict_types=1);

return [
    'enabled' => env('PWA_ENABLED', true),
    'name' => env('APP_NAME', 'Laravel'),
    'short_name' => env('PWA_SHORT_NAME', 'App'),
    'description' => env('PWA_DESCRIPTION', 'Application web progressive'),
    'theme_color' => env('PWA_THEME_COLOR', '#6366f1'),
    'background_color' => env('PWA_BACKGROUND_COLOR', '#0f172a'),
    'display' => 'standalone',
    'orientation' => 'any',
    'scope' => '/',
    'start_url' => '/',
    'lang' => env('APP_LOCALE', 'fr'),
    'categories' => ['business', 'productivity'],
    'icons' => [
        [
            'src' => '/icons/icon-192x192.png',
            'sizes' => '192x192',
            'type' => 'image/png',
            'purpose' => 'any maskable',
        ],
        [
            'src' => '/icons/icon-512x512.png',
            'sizes' => '512x512',
            'type' => 'image/png',
            'purpose' => 'any maskable',
        ],
        [
            'src' => '/icons/apple-touch-icon-180x180.png',
            'sizes' => '180x180',
            'type' => 'image/png',
        ],
    ],
];
