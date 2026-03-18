<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Blog\Providers;

use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Modules\Blog\Models\Article;
use Modules\Blog\Models\Comment;
use Modules\Blog\Observers\ArticleObserver;
use Modules\Blog\Policies\ArticlePolicy;
use Modules\Blog\Policies\CommentPolicy;
use Modules\Core\Providers\BaseModuleServiceProvider;

class BlogServiceProvider extends BaseModuleServiceProvider
{
    protected string $name = 'Blog';

    protected string $nameLower = 'blog';

    public function boot(): void
    {
        $this->bootModule();

        Gate::policy(Article::class, ArticlePolicy::class);
        Gate::policy(Comment::class, CommentPolicy::class);
        Article::observe(ArticleObserver::class);
        Livewire::component('blog-search', \Modules\Blog\Livewire\BlogSearch::class);
        Livewire::component('blog-list', \Modules\Blog\Livewire\BlogList::class);
    }

    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);

        $this->app->singleton(BlogMetricProvider::class);
        $this->app->tag([BlogMetricProvider::class], 'metric_providers');
    }
}
