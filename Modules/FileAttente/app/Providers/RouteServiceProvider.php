<?php

declare(strict_types=1);

namespace Modules\FileAttente\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 */
class RouteServiceProvider extends ServiceProvider
{
    protected string $name = 'FileAttente';

    public function map(): void
    {
        Route::middleware('web')->group(module_path($this->name, 'routes/web.php'));
        Route::middleware('api')->prefix('api')->group(module_path($this->name, 'routes/api.php'));
    }
}
