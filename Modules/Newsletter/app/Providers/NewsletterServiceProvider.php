<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Newsletter\Providers;

use Illuminate\Support\ServiceProvider;

class NewsletterServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
        $sourcePath = __DIR__.'/../../resources/views';
        $theme = config('backoffice.theme', 'backend');
        $themePath = __DIR__.'/../../resources/views/themes/'.$theme;
        $paths = is_dir($themePath) ? [$themePath, $sourcePath] : [$sourcePath];
        $this->loadViewsFrom($paths, 'newsletter');
        $this->loadRoutesFrom(__DIR__.'/../../routes/web.php');
    }
}
