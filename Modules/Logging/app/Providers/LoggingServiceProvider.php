<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Logging\Providers;

use Modules\Core\Providers\BaseModuleServiceProvider;
use Modules\Logging\Services\LogService;

class LoggingServiceProvider extends BaseModuleServiceProvider
{
    protected string $name = 'Logging';

    protected string $nameLower = 'logging';

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

        $this->app->singleton(LogService::class);
    }
}
