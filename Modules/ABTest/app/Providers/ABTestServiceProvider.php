<?php

declare(strict_types=1);

namespace Modules\ABTest\Providers;

use Illuminate\Support\ServiceProvider;
use Nwidart\Modules\Traits\PathNamespace;

class ABTestServiceProvider extends ServiceProvider
{
    use PathNamespace;

    protected string $name = 'ABTest';

    protected string $nameLower = 'abtest';

    public function boot(): void
    {
        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(module_path($this->name, 'database/migrations'));
    }

    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);
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
