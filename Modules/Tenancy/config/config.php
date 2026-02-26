<?php

declare(strict_types=1);

return [
    'name' => 'Tenancy',

    'identification' => [
        'method' => env('TENANCY_IDENTIFICATION', 'domain'),
        'header' => env('TENANCY_HEADER', 'X-Tenant-ID'),
    ],

    'defaults' => [
        'timezone' => 'America/Toronto',
        'locale' => 'fr',
    ],
];
