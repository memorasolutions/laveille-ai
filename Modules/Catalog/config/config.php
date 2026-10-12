<?php

// Author: MEMORA solutions, https://memora.solutions ; info@memora.ca

declare(strict_types=1);

return [
    'name' => 'Catalog',
    'moodle_ws_url' => env('MOODLE_WS_URL', 'https://laveille.ai/formations/webservice/rest/server.php'),
    'moodle_ws_token' => env('MOODLE_WS_TOKEN'),
    'moodle_base_url' => env('MOODLE_BASE_URL', 'https://laveille.ai/formations'),
    'cache_ttl' => (int) env('CATALOG_CACHE_TTL', 1800),
    'http_timeout' => (int) env('CATALOG_HTTP_TIMEOUT', 15),
];
