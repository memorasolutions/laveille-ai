<?php

declare(strict_types=1);

return [
    'name' => 'Shop',
    'enabled' => env('SHOP_ENABLED', true),
    'maintenance' => env('SHOP_MAINTENANCE', false),

    'gelato' => [
        'api_key' => env('GELATO_API_KEY'),
        'api_url' => env('GELATO_API_URL', 'https://api.gelato.com'),
        'store_id' => env('GELATO_STORE_ID'),
        'webhook_secret' => env('GELATO_WEBHOOK_SECRET'),
    ],

    'gelato_webhook_secret' => env('GELATO_WEBHOOK_SECRET'),

    'stripe' => [
        'secret_key' => env('STRIPE_SECRET_KEY'),
        'publishable_key' => env('STRIPE_PUBLISHABLE_KEY'),
        'webhook_secret' => env('STRIPE_SHOP_WEBHOOK_SECRET'),
    ],

    'currency' => env('SHOP_CURRENCY', 'CAD'),

    'tax' => [
        'tps' => (float) env('SHOP_TAX_TPS', 5.0),
        'tvq' => (float) env('SHOP_TAX_TVQ', 9.975),
    ],

    'shipping_countries' => env('SHOP_SHIPPING_COUNTRIES')
        ? array_values(array_filter(array_map('trim', explode(',', (string) env('SHOP_SHIPPING_COUNTRIES')))))
        : ['CA'],

    'cart' => [
        'expiry_hours' => (int) env('SHOP_CART_EXPIRY_HOURS', 72),
    ],

    'routes' => [
        'prefix' => env('SHOP_ROUTES_PREFIX', 'boutique'),
        'admin_prefix' => env('SHOP_ROUTES_ADMIN_PREFIX', 'admin/shop'),
    ],

    'pagination' => (int) env('SHOP_PAGINATION', 12),

    'pricing' => [
        'usd_cad_rate' => (float) env('SHOP_USD_CAD_RATE', 1.40),
        'estimated_shipping_usd' => (float) env('SHOP_ESTIMATED_SHIPPING_USD', 11.00),
        'shipping_by_category' => [
            'hoodies' => 12.00,
            't-shirts' => 8.00,
            'mugs' => 6.00,
            'tote-bags' => 7.00,
            'water-bottles' => 7.00,
            'posters' => 5.00,
        ],
        'margins' => [
            'hoodies' => 0.30,
            't-shirts' => 0.30,
            'mugs' => 0.35,
            'tote-bags' => 0.30,
            'water-bottles' => 0.30,
            'posters' => 0.25,
            'default' => 0.30,
        ],
    ],
];
