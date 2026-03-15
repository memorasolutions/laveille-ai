<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Search\Providers;

use Modules\Core\Providers\BaseModuleServiceProvider;

class SearchServiceProvider extends BaseModuleServiceProvider
{
    protected string $name = 'Search';

    protected string $nameLower = 'search';

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

        $this->app->singleton(\Modules\Search\Services\SearchService::class);
    }
}
