<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\AI\Providers;

use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Modules\AI\Livewire\AiArticleGenerator;
use Modules\AI\Livewire\ChatBot;
use Modules\AI\Observers\ArticleSeoObserver;
use Modules\AI\Observers\CommentModerationObserver;
use Modules\AI\Services\AiService;
use Modules\Blog\Models\Article;
use Modules\Blog\Models\Comment;
use Nwidart\Modules\Traits\PathNamespace;

class AiServiceProvider extends ServiceProvider
{
    use PathNamespace;

    protected string $name = 'AI';

    protected string $nameLower = 'ai';

    public function boot(): void
    {
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(module_path($this->name, 'database/migrations'));

        Livewire::component('ai-chatbot', ChatBot::class);
        Livewire::component('ai-article-generator', AiArticleGenerator::class);

        Comment::observe(CommentModerationObserver::class);
        Article::observe(ArticleSeoObserver::class);
    }

    public function register(): void
    {
        $this->app->singleton(AiService::class);
    }

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

    protected function registerConfig(): void
    {
        $this->publishes([
            module_path($this->name, 'config/config.php') => config_path($this->nameLower.'.php'),
        ], 'config');

        $this->mergeConfigFrom(module_path($this->name, 'config/config.php'), $this->nameLower);
    }

    public function registerViews(): void
    {
        $viewPath = resource_path('views/modules/'.$this->nameLower);
        $sourcePath = module_path($this->name, 'resources/views');

        $this->publishes([$sourcePath => $viewPath], ['views', $this->nameLower.'-module-views']);
        $this->loadViewsFrom(array_merge($this->getPublishableViewPaths(), [$sourcePath]), $this->nameLower);
    }

    public function provides(): array
    {
        return [AiService::class];
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
