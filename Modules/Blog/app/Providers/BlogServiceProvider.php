<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Blog\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Modules\Blog\Models\Article;
use Modules\Blog\Models\Comment;
use Modules\Blog\Observers\ArticleObserver;
use Modules\Blog\Policies\ArticlePolicy;
use Modules\Blog\Policies\CommentPolicy;
use Nwidart\Modules\Traits\PathNamespace;

class BlogServiceProvider extends ServiceProvider
{
    use PathNamespace;

    protected string $name = 'Blog';

    protected string $nameLower = 'blog';

    public function boot(): void
    {
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(module_path($this->name, 'database/migrations'));
        Gate::policy(Article::class, ArticlePolicy::class);
        Gate::policy(Comment::class, CommentPolicy::class);
        Article::observe(ArticleObserver::class);
        Livewire::component('blog-search', \Modules\Blog\Livewire\BlogSearch::class);
        Livewire::component('blog-list', \Modules\Blog\Livewire\BlogList::class);
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
        $theme = config('backoffice.theme', 'backend');
        $themePath = module_path($this->name, 'resources/views/themes/'.$theme);
        $paths = is_dir($viewPath) ? [$viewPath] : [];
        if (is_dir($themePath)) {
            array_unshift($paths, $themePath);
        }
        $paths[] = $sourcePath;
        $this->loadViewsFrom($paths, $this->nameLower);
        Blade::componentNamespace('Modules\\Blog\\View\\Components', $this->nameLower);
    }
}
