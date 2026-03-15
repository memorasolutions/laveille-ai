<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Menu\Providers;

use Illuminate\Support\Facades\Gate;
use Modules\Core\Providers\BaseModuleServiceProvider;
use Modules\Menu\Models\Menu;
use Modules\Menu\Models\MenuItem;
use Modules\Menu\Policies\MenuPolicy;
use Modules\Menu\Services\MenuService;

class MenuServiceProvider extends BaseModuleServiceProvider
{
    protected string $name = 'Menu';

    protected string $nameLower = 'menu';

    public function boot(): void
    {
        $this->bootModule();

        Gate::policy(Menu::class, MenuPolicy::class);

        $clearCache = function () {
            app(MenuService::class)->clearCache();
        };

        Menu::saved($clearCache);
        Menu::deleted($clearCache);
        MenuItem::saved($clearCache);
        MenuItem::deleted($clearCache);
    }

    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);
        $this->app->singleton(MenuService::class);
    }
}
