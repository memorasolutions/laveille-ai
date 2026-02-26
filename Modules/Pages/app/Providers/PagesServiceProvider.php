<?php

declare(strict_types=1);

namespace Modules\Pages\Providers;

use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Modules\Pages\Livewire\StaticPagesTable;
use Nwidart\Modules\Traits\PathNamespace;

class PagesServiceProvider extends ServiceProvider
{
    use PathNamespace;

    protected string $name = 'Pages';

    protected string $nameLower = 'pages';

    public function boot(): void
    {
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(module_path($this->name, 'database/migrations'));
        Livewire::component('static-pages-table', StaticPagesTable::class);
    }

    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);
    }

    public function registerTranslations(): void
    {
        $this->loadTranslationsFrom(module_path($this->name, 'lang'), $this->nameLower);
    }

    protected function registerConfig(): void
    {
        $configPath = module_path($this->name, 'config');
        if (is_dir($configPath)) {
            $this->mergeConfigFrom($configPath.'/config.php', $this->nameLower);
        }
    }

    public function registerViews(): void
    {
        $viewPath = resource_path('views/modules/'.$this->nameLower);
        $sourcePath = module_path($this->name, 'resources/views');
        $this->publishes([$sourcePath => $viewPath], ['views', $this->nameLower.'-module-views']);
        $this->loadViewsFrom(array_merge(
            is_dir($viewPath) ? [$viewPath] : [],
            [$sourcePath]
        ), $this->nameLower);
    }
}
