<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Api\Providers;

use Modules\Core\Providers\BaseModuleServiceProvider;

class ApiServiceProvider extends BaseModuleServiceProvider
{
    protected string $name = 'Api';

    protected string $nameLower = 'api';

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
    }
}
