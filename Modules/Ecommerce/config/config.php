<?php

declare(strict_types=1);

return [
    'name' => 'Ecommerce',

    'currency' => 'CAD',
    'currency_symbol' => '$',
    'tax_rate' => 14.975,

    'shipping' => [
        'default_method' => 'flat_rate',
        'flat_rate' => 9.99,
        'free_threshold' => 75.00,
        'per_kg_rate' => 2.50,
    ],

    'stock' => [
        'low_threshold' => 5,
        'track_inventory' => true,
    ],

    'checkout' => [
        'guest_checkout' => false,
        'stripe_enabled' => true,
    ],

    'invoices' => [
        'prefix' => 'INV-',
        'company_name' => '',
        'company_address' => '',
    ],
];
