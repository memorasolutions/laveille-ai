<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\CustomFields\Providers;

use Modules\Core\Providers\BaseModuleServiceProvider;

class CustomFieldsServiceProvider extends BaseModuleServiceProvider
{
    protected string $name = 'CustomFields';

    protected string $nameLower = 'customfields';

    public function boot(): void
    {
        $this->bootModule();
    }

    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);
    }
}
