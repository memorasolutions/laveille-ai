<?php

declare(strict_types=1);

return [
    'name' => 'Shop',
    'enabled' => env('SHOP_ENABLED', true),

    'gelato' => [
        'api_key' => env('GELATO_API_KEY'),
        'api_url' => env('GELATO_API_URL', 'https://api.gelato.com'),
    ],

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
];
