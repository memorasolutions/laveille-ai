<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::get('/health', fn () => response()->json(['status' => 'ok']));

Route::middleware(['force-json', 'throttle:api'])
    ->prefix('v1')
    ->group(base_path('routes/api/v1.php'));
