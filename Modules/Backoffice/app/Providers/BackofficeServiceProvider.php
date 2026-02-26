<?php

declare(strict_types=1);

namespace Modules\Backoffice\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Modules\Backoffice\Livewire\ActivityLogsTable;
use Modules\Backoffice\Livewire\ArticlesTable;
use Modules\Backoffice\Livewire\CampaignsTable;
use Modules\Backoffice\Livewire\CategoriesTable;
use Modules\Backoffice\Livewire\CommentsTable;
use Modules\Backoffice\Livewire\FeatureFlagsTable;
use Modules\Backoffice\Livewire\GlobalSearch;
use Modules\Backoffice\Livewire\LookerStudioStats;
use Modules\Backoffice\Livewire\MediaTable;
use Modules\Backoffice\Livewire\MetaTagsTable;
use Modules\Backoffice\Livewire\NotificationBell;
use Modules\Backoffice\Livewire\PlansTable;
use Modules\Backoffice\Livewire\RolesTable;
use Modules\Backoffice\Livewire\SettingsManager;
use Modules\Backoffice\Livewire\SettingsTable;
use Modules\Backoffice\Livewire\ShortcodesTable;
use Modules\Backoffice\Livewire\SubscribersTable;
use Modules\Backoffice\Livewire\TranslationsManager;
use Modules\Backoffice\Livewire\UsersTable;
use Modules\Backoffice\Livewire\WebhooksManager;
use Nwidart\Modules\Traits\PathNamespace;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class BackofficeServiceProvider extends ServiceProvider
{
    use PathNamespace;

    protected string $name = 'Backoffice';

    protected string $nameLower = 'backoffice';

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
        $this->registerLivewireComponents();
        $this->registerBrandingComposer();
        $this->loadMigrationsFrom(module_path($this->name, 'database/migrations'));
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
        // $this->commands([]);
    }

    /**
     * Register command Schedules.
     */
    protected function registerCommandSchedules(): void
    {
        // $this->app->booted(function () {
        //     $schedule = $this->app->make(Schedule::class);
        //     $schedule->command('inspire')->hourly();
        // });
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

        config([$key => array_replace_recursive($existing, $module_config)]);
    }

    /**
     * Register views.
     */
    public function registerViews(): void
    {
        $viewPath = resource_path('views/modules/'.$this->nameLower);
        $sourcePath = module_path($this->name, 'resources/views');

        $this->publishes([$sourcePath => $viewPath], ['views', $this->nameLower.'-module-views']);

        $theme = config('backoffice.theme', 'wowdash');
        $themePath = module_path($this->name, 'resources/views/themes/'.$theme);

        $paths = $this->getPublishableViewPaths();

        if (is_dir($themePath)) {
            array_unshift($paths, $themePath);
        }

        $paths[] = $sourcePath;

        $this->loadViewsFrom($paths, $this->nameLower);

        Blade::componentNamespace(config('modules.namespace').'\\'.$this->name.'\\View\\Components', $this->nameLower);
    }

    protected function registerLivewireComponents(): void
    {
        Livewire::component('backoffice-activity-logs-table', ActivityLogsTable::class);
        Livewire::component('backoffice-articles-table', ArticlesTable::class);
        Livewire::component('backoffice-categories-table', CategoriesTable::class);
        Livewire::component('backoffice-campaigns-table', CampaignsTable::class);
        Livewire::component('backoffice-webhooks-manager', WebhooksManager::class);
        Livewire::component('backoffice-subscribers-table', SubscribersTable::class);
        Livewire::component('backoffice-comments-table', CommentsTable::class);
        Livewire::component('backoffice-users-table', UsersTable::class);
        Livewire::component('backoffice-roles-table', RolesTable::class);
        Livewire::component('backoffice-settings-table', SettingsTable::class);
        Livewire::component('backoffice-settings-manager', SettingsManager::class);
        Livewire::component('backoffice-global-search', GlobalSearch::class);
        Livewire::component('backoffice-notification-bell', NotificationBell::class);
        Livewire::component('backoffice-plans-table', PlansTable::class);
        Livewire::component('backoffice-feature-flags-table', FeatureFlagsTable::class);
        Livewire::component('backoffice-meta-tags-table', MetaTagsTable::class);
        Livewire::component('backoffice-media-table', MediaTable::class);
        Livewire::component('shortcodes-table', ShortcodesTable::class);
        Livewire::component('backoffice-translations-manager', TranslationsManager::class);
        Livewire::component('backoffice-looker-studio-stats', LookerStudioStats::class);
    }

    protected function registerBrandingComposer(): void
    {
        View::composer('backoffice::*', BrandingViewComposer::class);
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
