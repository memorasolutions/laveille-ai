<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

return [
    'name' => 'CloudflareCache',
    'enabled' => env('CLOUDFLARE_CACHE_ENABLED', true),
    'api_token' => env('CLOUDFLARE_API_TOKEN'),
    'zone_id' => env('CLOUDFLARE_ZONE_ID'),
    'timeout' => (int) env('CLOUDFLARE_TIMEOUT', 5),
    'always_purge_urls' => [],
    // Format: Model::class => ['routes' => [['name' => 'route.name', 'param_field' => 'slug_field']]]
    // 'param_field' = propriété du modèle utilisée comme paramètre de route (optionnel pour index routes)
    // Pour désactiver la purge d'un modèle : retirer sa clé ou commenter.
    'models_to_watch' => [
        \Modules\Blog\Models\Article::class => [
            'routes' => [
                ['name' => 'blog.show', 'param_field' => 'slug'],
                ['name' => 'blog.index'],
            ],
        ],
    ],
];
