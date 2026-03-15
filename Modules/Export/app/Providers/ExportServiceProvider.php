<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Export\Providers;

use Modules\Core\Providers\BaseModuleServiceProvider;

class ExportServiceProvider extends BaseModuleServiceProvider
{
    protected string $name = 'Export';

    protected string $nameLower = 'export';

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

        $this->app->singleton(\Modules\Export\Services\ExportService::class);
    }
}
