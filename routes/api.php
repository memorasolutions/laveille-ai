<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    $checks = ['status' => 'ok'];

    try {
        \Illuminate\Support\Facades\DB::connection()->getPdo();
        $checks['database'] = 'ok';
    } catch (\Throwable) {
        $checks['database'] = 'error';
        $checks['status'] = 'degraded';
    }

    try {
        \Illuminate\Support\Facades\Cache::store()->put('health-check', true, 10);
        $checks['cache'] = 'ok';
    } catch (\Throwable) {
        $checks['cache'] = 'error';
    }

    $code = $checks['status'] === 'ok' ? 200 : 503;

    return response()->json($checks, $code);
});

Route::middleware(['force-json', 'throttle:api'])
    ->prefix('v1')
    ->group(base_path('routes/api/v1.php'));

// Privacy & consent API moved to Modules/Privacy
