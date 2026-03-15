<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\SEO\Providers;

use Modules\Core\Providers\BaseModuleServiceProvider;

class SEOServiceProvider extends BaseModuleServiceProvider
{
    protected string $name = 'SEO';

    protected string $nameLower = 'seo';

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

        $this->app->singleton(\Modules\SEO\Services\SeoService::class);
    }
}
