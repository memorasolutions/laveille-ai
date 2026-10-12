<?php

// Author: MEMORA solutions, https://memora.solutions ; info@memora.ca

declare(strict_types=1);

namespace Modules\Catalog\Providers;

use Modules\Core\Providers\BaseModuleServiceProvider;

class CatalogServiceProvider extends BaseModuleServiceProvider
{
    protected string $name = 'Catalog';

    protected string $nameLower = 'catalog';

    public function boot(): void
    {
        $this->bootModule();
    }

    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);
    }
}
