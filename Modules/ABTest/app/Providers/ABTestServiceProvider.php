<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\ABTest\Providers;

use Modules\Core\Providers\BaseModuleServiceProvider;

class ABTestServiceProvider extends BaseModuleServiceProvider
{
    protected string $name = 'ABTest';

    protected string $nameLower = 'abtest';

    public function boot(): void
    {
        $this->bootModule();
    }

    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);
    }
}
