<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Import\Providers;

use Modules\Core\Providers\BaseModuleServiceProvider;

class ImportServiceProvider extends BaseModuleServiceProvider
{
    protected string $name = 'Import';

    protected string $nameLower = 'import';

    public function boot(): void
    {
        $this->bootModule();
    }

    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);
    }
}
