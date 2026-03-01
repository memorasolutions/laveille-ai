<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Widget\Providers;

use Illuminate\Support\ServiceProvider;
use Nwidart\Modules\Traits\PathNamespace;

class WidgetServiceProvider extends ServiceProvider
{
    use PathNamespace;

    protected string $name = 'Widget';

    protected string $nameLower = 'widget';

    public function boot(): void
    {
        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(module_path($this->name, 'database/migrations'));
    }

    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);
        $this->app->register(EventServiceProvider::class);
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
