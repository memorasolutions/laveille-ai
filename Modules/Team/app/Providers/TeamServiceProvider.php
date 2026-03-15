<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Team\Providers;

use Modules\Core\Providers\BaseModuleServiceProvider;

class TeamServiceProvider extends BaseModuleServiceProvider
{
    protected string $name = 'Team';

    protected string $nameLower = 'team';

    public function boot(): void
    {
        $this->bootModule();
    }

    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);
    }
}
