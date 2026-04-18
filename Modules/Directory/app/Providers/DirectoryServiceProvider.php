<?php

declare(strict_types=1);

namespace Modules\Directory\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Nwidart\Modules\Traits\PathNamespace;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class DirectoryServiceProvider extends ServiceProvider
{
    use PathNamespace;

    protected string $name = 'Directory';

    protected string $nameLower = 'directory';

    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        $this->registerCommands();
        $this->registerCommandSchedules();
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();

        if (class_exists(\Modules\Core\Services\ModeratableRegistry::class)) {
            \Modules\Core\Services\ModeratableRegistry::register('discussions', \Modules\Directory\Models\ToolDiscussion::class);
            \Modules\Core\Services\ModeratableRegistry::register('resources', \Modules\Directory\Models\ToolResource::class);
            \Modules\Core\Services\ModeratableRegistry::register('suggestions', \Modules\Directory\Models\ToolSuggestion::class);
        }
        $this->loadMigrationsFrom(module_path($this->name, 'database/migrations'));

        \Modules\Directory\Models\Tool::observe(\Modules\Directory\Observers\ToolObserver::class);

        // Middleware streak tracking (ajouté au groupe web)
        $this->app['router']->pushMiddlewareToGroup('web', \Modules\Directory\Http\Middleware\TrackActivity::class);
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->app->register(EventServiceProvider::class);
        $this->app->register(RouteServiceProvider::class);
    }

    /**
     * Register commands in the format of Command::class
     */
    protected function registerCommands(): void
    {
        $this->commands([
            \Modules\Directory\Console\DirectoryEnrichToolsCommand::class,
            \Modules\Directory\Console\CaptureScreenshotsCommand::class,
            \Modules\Directory\Console\CheckLinksCommand::class,
            \Modules\Directory\Console\EnrichTutorialsCommand::class,
            \Modules\Directory\Console\EnrichTutorialsSonarCommand::class,
            \Modules\Directory\Console\EnrichPendingCommand::class,
            \Modules\Directory\Console\EnrichMetadataCommand::class,
            \Modules\Directory\Console\DispatchEnrichmentCommand::class,
            \Modules\Directory\Console\SummarizePendingCommand::class,
            \Modules\Directory\Console\GenerateAlternativesCommand::class,
            \Modules\Directory\Console\DiscoverNewToolsCommand::class,
            \Modules\Directory\Console\ReenrichStaleCommand::class,
            \Modules\Directory\Console\RefreshPricingCommand::class,
            \Modules\Directory\Console\EnrichFormationsCommand::class,
            \Modules\Directory\Console\FixHnSlugsCommand::class,
            \Modules\Directory\Console\ImportYoutubeResourcesCommand::class,
        ]);
    }

    /**
     * Register command Schedules.
     */
    protected function registerCommandSchedules(): void
    {
        $this->app->booted(function () {
            $schedule = $this->app->make(\Illuminate\Console\Scheduling\Schedule::class);
            $schedule->command('tools:enrich-pending --batch=3')->dailyAt('05:15');
            $schedule->command('tools:dispatch-enrichment --type=pending --limit=5')->everyFifteenMinutes()->withoutOverlapping();
            $schedule->command('tools:dispatch-enrichment --type=metadata --limit=5')->everyFifteenMinutes()->withoutOverlapping();
            $schedule->command('queue:work database --queue=screenshots --once --max-time=280 --timeout=270 --tries=1 --stop-when-empty')->everyThreeMinutes()->withoutOverlapping()->runInBackground();
            $schedule->command('tools:enrich-tutorials --batch=5')->dailyAt('05:00');
            $schedule->command('resources:summarize-pending --batch=10')->dailyAt('05:30');
            $schedule->command('tools:discover-new')->dailyAt('04:00');
            $schedule->command('tools:reenrich-stale --batch=2 --months=3')->monthlyOn(1, '06:00');
            $schedule->command('tools:refresh-pricing --batch=5')->quarterly();
            $schedule->command('tools:enrich-formations --batch=5')->weeklyOn(0, '07:00');
        });
    }

    /**
     * Register translations.
     */
    public function registerTranslations(): void
    {
        $langPath = resource_path('lang/modules/'.$this->nameLower);

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, $this->nameLower);
            $this->loadJsonTranslationsFrom($langPath);
        } else {
            $this->loadTranslationsFrom(module_path($this->name, 'lang'), $this->nameLower);
            $this->loadJsonTranslationsFrom(module_path($this->name, 'lang'));
        }
    }

    /**
     * Register config.
     */
    protected function registerConfig(): void
    {
        $configPath = module_path($this->name, config('modules.paths.generator.config.path'));

        if (is_dir($configPath)) {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($configPath));

            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getExtension() === 'php') {
                    $config = str_replace($configPath.DIRECTORY_SEPARATOR, '', $file->getPathname());
                    $config_key = str_replace([DIRECTORY_SEPARATOR, '.php'], ['.', ''], $config);
                    $segments = explode('.', $this->nameLower.'.'.$config_key);

                    // Remove duplicated adjacent segments
                    $normalized = [];
                    foreach ($segments as $segment) {
                        if (end($normalized) !== $segment) {
                            $normalized[] = $segment;
                        }
                    }

                    $key = ($config === 'config.php') ? $this->nameLower : implode('.', $normalized);

                    $this->publishes([$file->getPathname() => config_path($config)], 'config');
                    $this->merge_config_from($file->getPathname(), $key);
                }
            }
        }
    }

    /**
     * Merge config from the given path recursively.
     */
    protected function merge_config_from(string $path, string $key): void
    {
        $existing = config($key, []);
        $module_config = require $path;

        config([$key => array_replace_recursive($module_config, $existing)]);
    }

    /**
     * Register views.
     */
    public function registerViews(): void
    {
        $viewPath = resource_path('views/modules/'.$this->nameLower);
        $sourcePath = module_path($this->name, 'resources/views');

        $this->publishes([$sourcePath => $viewPath], ['views', $this->nameLower.'-module-views']);

        $this->loadViewsFrom(array_merge($this->getPublishableViewPaths(), [$sourcePath]), $this->nameLower);

        Blade::componentNamespace(config('modules.namespace').'\\'.$this->name.'\\View\\Components', $this->nameLower);
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [];
    }

    private function getPublishableViewPaths(): array
    {
        $paths = [];
        foreach (config('view.paths') as $path) {
            if (is_dir($path.'/modules/'.$this->nameLower)) {
                $paths[] = $path.'/modules/'.$this->nameLower;
            }
        }

        return $paths;
    }
}
