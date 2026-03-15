<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Widget\Providers;

use Modules\Core\Providers\BaseModuleServiceProvider;

class WidgetServiceProvider extends BaseModuleServiceProvider
{
    protected string $name = 'Widget';

    protected string $nameLower = 'widget';

    public function boot(): void
    {
        $this->bootModule();
    }

    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);
    }
}
