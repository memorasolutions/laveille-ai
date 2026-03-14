<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Menu\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Modules\Menu\Models\Menu;
use Modules\Menu\Models\MenuItem;
use Modules\Menu\Policies\MenuPolicy;
use Modules\Menu\Services\MenuService;
use Nwidart\Modules\Traits\PathNamespace;

class MenuServiceProvider extends ServiceProvider
{
    use PathNamespace;

    protected string $name = 'Menu';

    protected string $nameLower = 'menu';

    public function boot(): void
    {
        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(module_path($this->name, 'database/migrations'));

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

    private function registerConfig(): void
    {
        $path = module_path($this->name, 'config/config.php');
        $this->mergeConfigFrom($path, "modules.{$this->nameLower}");
    }

    private function registerViews(): void
    {
        $sourcePath = module_path($this->name, 'resources/views');
        $this->loadViewsFrom($sourcePath, $this->nameLower);
    }
}
