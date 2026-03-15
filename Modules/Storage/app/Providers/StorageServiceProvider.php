<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Storage\Providers;

use Modules\Core\Providers\BaseModuleServiceProvider;
use Modules\Storage\Services\StorageService;

class StorageServiceProvider extends BaseModuleServiceProvider
{
    protected string $name = 'Storage';

    protected string $nameLower = 'storage';

    public function boot(): void
    {
        $this->bootModule();
    }

    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);

        $this->app->singleton(StorageService::class);
    }
}
