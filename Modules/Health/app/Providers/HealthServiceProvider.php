<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Health\Providers;

use Modules\Core\Providers\BaseModuleServiceProvider;

class HealthServiceProvider extends BaseModuleServiceProvider
{
    protected string $name = 'Health';

    protected string $nameLower = 'health';

    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        $this->bootModule();
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);
        $this->app->register(HealthCheckServiceProvider::class);
    }
}
