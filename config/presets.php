<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Module Presets
    |--------------------------------------------------------------------------
    |
    | Each preset defines which modules to disable and .env overrides to apply.
    | Foundation modules (Core, Auth, Backoffice, Settings, RolesPermissions,
    | Notifications, Logging, Health, Media, Editor, Privacy, Storage, Backup)
    | cannot be disabled regardless of preset configuration.
    |
    | Usage: php artisan core:prune saas
    |        php artisan core:prune --interactive
    |
    */

    'saas' => [
        'description' => 'SaaS complet avec abonnements Stripe, multi-tenant, equipes',
        'modules_disabled' => [
            'ABTest',
            'Blog',
            'Booking',
            'Ecommerce',
            'Export',
            'Faq',
            'FormBuilder',
            'Import',
            'Newsletter',
            'Roadmap',
            'ShortUrl',
            'Testimonials',
            'Widget',
            'CustomFields',
        ],
        'env_overrides' => [
            'CACHE_STORE' => 'redis',
            'SESSION_DRIVER' => 'redis',
            'QUEUE_CONNECTION' => 'redis',
        ],
    ],

    'blog' => [
        'description' => 'Plateforme de contenu et publishing avec SEO et newsletter',
        'modules_disabled' => [
            'ABTest',
            'Booking',
            'Ecommerce',
            'Export',
            'FormBuilder',
            'Import',
            'Roadmap',
            'SaaS',
            'ShortUrl',
            'Team',
            'Tenancy',
            'Widget',
            'CustomFields',
        ],
        'env_overrides' => [
            'CACHE_STORE' => 'redis',
            'QUEUE_CONNECTION' => 'redis',
        ],
    ],

    'minimal' => [
        'description' => 'Installation minimale securisee, fonctionnalites reduites',
        'modules_disabled' => [
            'ABTest',
            'AI',
            'Api',
            'Blog',
            'Booking',
            'Ecommerce',
            'Export',
            'Faq',
            'FormBuilder',
            'Import',
            'Menu',
            'Newsletter',
            'Pages',
            'Roadmap',
            'SaaS',
            'Search',
            'ShortUrl',
            'Team',
            'Tenancy',
            'Testimonials',
            'Translation',
            'Webhooks',
            'Widget',
            'CustomFields',
        ],
        'env_overrides' => [
            'CACHE_STORE' => 'file',
            'SESSION_DRIVER' => 'database',
            'QUEUE_CONNECTION' => 'database',
        ],
    ],

];
