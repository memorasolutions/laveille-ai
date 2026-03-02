<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Core\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

abstract class BaseRouteServiceProvider extends ServiceProvider
{
    /** Module name used to resolve route files via module_path(). */
    protected string $name = '';

    /** Whether to load routes/web.php. */
    protected bool $mapWeb = true;

    /** Whether to load routes/api.php. */
    protected bool $mapApi = false;

    /** Prefix applied to API routes (default: 'api'). */
    protected string $apiPrefix = 'api';

    /** Name prefix applied to API routes (default: 'api'). */
    protected string $apiNamePrefix = 'api';

    /** Middleware applied to web routes. */
    protected array $webMiddleware = ['web'];

    /** Middleware applied to API routes. */
    protected array $apiMiddleware = ['api'];

    public function boot(): void
    {
        parent::boot();
    }

    public function map(): void
    {
        if ($this->mapWeb) {
            $this->mapWebRoutes();
        }

        if ($this->mapApi) {
            $this->mapApiRoutes();
        }
    }

    protected function mapWebRoutes(): void
    {
        Route::middleware($this->webMiddleware)
            ->group(module_path($this->name, 'routes/web.php'));
    }

    protected function mapApiRoutes(): void
    {
        $route = Route::middleware($this->apiMiddleware)
            ->prefix($this->apiPrefix);

        if ($this->apiNamePrefix) {
            $route = $route->name($this->apiNamePrefix.'.');
        }

        $route->group(module_path($this->name, 'routes/api.php'));
    }
}
