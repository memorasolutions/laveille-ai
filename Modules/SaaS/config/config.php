<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

return [
    'name' => 'SaaS',

    'stripe' => [
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    ],

    'currency' => env('SAAS_CURRENCY', 'cad'),

    'trial_days' => (int) env('SAAS_TRIAL_DAYS', 14),

    'rate_limits' => [
        'default' => 60,       // Free / non-subscribed users
        'subscribed' => 300,   // Subscribed users (fallback if price not mapped)
        'plans' => [
            // Map stripe_price_id => requests per minute
            // Example: 'price_xxx_pro_monthly' => 300,
            // Example: 'price_xxx_enterprise_monthly' => 1000,
        ],
    ],

    'plans' => [
        'free' => [
            'name' => 'Free',
            'features' => ['1_user', 'basic_support', '1gb_storage'],
        ],
        'pro' => [
            'name' => 'Pro',
            'features' => ['10_users', 'priority_support', '50gb_storage', 'api_access', 'export'],
        ],
        'enterprise' => [
            'name' => 'Enterprise',
            'features' => ['unlimited_users', 'dedicated_support', 'unlimited_storage', 'api_access', 'export', 'webhooks', 'sso'],
        ],
    ],
];
