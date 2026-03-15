<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Media\Providers;

use Modules\Core\Providers\BaseModuleServiceProvider;
use Modules\Media\Services\MediaService;

class MediaServiceProvider extends BaseModuleServiceProvider
{
    protected string $name = 'Media';

    protected string $nameLower = 'media';

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

        $this->app->singleton(MediaService::class);
    }
}
