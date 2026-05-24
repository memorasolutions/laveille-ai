<?php

namespace Modules\Authors\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Nwidart\Modules\Traits\PathNamespace;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class AuthorsServiceProvider extends ServiceProvider
{
    use PathNamespace;

    protected string $name = 'Authors';

    protected string $nameLower = 'authors';

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
        $this->loadMigrationsFrom(module_path($this->name, 'database/migrations'));
        $this->registerLivewireComponents();
        $this->registerObservers();
    }

    protected function registerObservers(): void
    {
        \Modules\Authors\Models\AuthorPost::observe(\Modules\Authors\Observers\AuthorPostObserver::class);

        if (class_exists(\Modules\Authors\Models\AuthorWebmention::class)) {
            \Modules\Authors\Models\AuthorWebmention::observe(\Modules\Authors\Observers\AuthorWebmentionObserver::class);
        }
    }

    protected function registerLivewireComponents(): void
    {
        if (! class_exists(Livewire::class)) {
            return;
        }
        Livewire::component('authors.author-dashboard', \Modules\Authors\Livewire\AuthorDashboard::class);
        Livewire::component('authors.author-settings', \Modules\Authors\Livewire\AuthorSettings::class);
        Livewire::component('authors.image-uploader', \Modules\Authors\Livewire\ImageUploader::class);
        Livewire::component('authors.article-builder', \Modules\Authors\Livewire\ArticleBuilder::class);
        Livewire::component('authors.image-builder', \Modules\Authors\Livewire\ImageBuilder::class);
        Livewire::component('authors.comment-section', \Modules\Authors\Livewire\CommentSection::class);
        Livewire::component('authors.author-activity-log-viewer', \Modules\Authors\Livewire\AuthorActivityLogViewer::class);
        Livewire::component('authors.author-search', \Modules\Authors\Livewire\AuthorSearch::class);
        Livewire::component('authors.affiliate-link-manager', \Modules\Authors\Livewire\AffiliateLinkManager::class);
        Livewire::component('authors.all-authors-viewer', \Modules\Authors\Livewire\AllAuthorsViewer::class);
        Livewire::component('authors.author-recent-notifications', \Modules\Authors\Livewire\AuthorRecentNotifications::class);
        Livewire::component('authors.author-related-posts', \Modules\Authors\Livewire\AuthorRelatedPosts::class);
        Livewire::component('authors.comment-moderation-queue', \Modules\Authors\Livewire\CommentModerationQueue::class);
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->app->register(EventServiceProvider::class);
        $this->app->register(RouteServiceProvider::class);

        // S107 P3 — override Cashier webhook controller to sync AuthorProfile tier
        if (class_exists(\Laravel\Cashier\Http\Controllers\WebhookController::class)) {
            $this->app->bind(
                \Laravel\Cashier\Http\Controllers\WebhookController::class,
                \Modules\Authors\Http\Controllers\StripeWebhookController::class
            );
        }
    }

    /**
     * Register commands in the format of Command::class
     */
    protected function registerCommands(): void
    {
        $this->commands([
            \Modules\Authors\Console\Commands\AuthorsReportCommand::class,
            \Modules\Authors\Console\Commands\AuthorsHealthCommand::class,
            \Modules\Authors\Console\Commands\AuthorsWeeklyDigestCommand::class,
            \Modules\Authors\Console\Commands\AuthorsWebmentionSendCommand::class,
            \Modules\Authors\Console\Commands\SendSubscriberDigestCommand::class,
        ]);
    }

    /**
     * Register command Schedules.
     */
    protected function registerCommandSchedules(): void
    {
        $this->app->booted(function () {
            if (! function_exists('module_enabled') || ! module_enabled('Authors')) {
                return;
            }

            $schedule = $this->app->make(\Illuminate\Console\Scheduling\Schedule::class);

            // S117 — Digest hebdo dimanche 09:00 Québec time
            $schedule->command('authors:digest')
                ->weeklyOn(0, '09:00')
                ->timezone('America/Toronto')
                ->withoutOverlapping()
                ->onOneServer();

            // S117 — Healthcheck quotidien 04:00 Québec time
            $schedule->command('authors:health')
                ->dailyAt('04:00')
                ->timezone('America/Toronto')
                ->withoutOverlapping()
                ->onOneServer();

            // S121 — Digest hebdo aux abonnés dimanche 10:00 Québec time
            $schedule->command('authors:subscriber-digest')
                ->weeklyOn(0, '10:00')
                ->timezone('America/Toronto')
                ->withoutOverlapping()
                ->onOneServer();
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

        $this->loadViewsFrom(array_merge($this->getPublishableViewPaths(), [$sourcePath]), $this->nameLower);

        Blade::componentNamespace(config('modules.namespace').'\\' . $this->name . '\\View\\Components', $this->nameLower);
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
