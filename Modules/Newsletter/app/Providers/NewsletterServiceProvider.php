<?php

declare(strict_types=1);

namespace Modules\Newsletter\Providers;

use Illuminate\Support\ServiceProvider;

class NewsletterServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'newsletter');
        $this->loadRoutesFrom(__DIR__.'/../../routes/web.php');
    }
}
