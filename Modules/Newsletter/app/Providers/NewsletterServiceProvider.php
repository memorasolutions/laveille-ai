<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Newsletter\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Modules\Newsletter\Listeners\WorkflowTriggerListener;

class NewsletterServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        $this->commands([
            \Modules\Newsletter\Console\DigestCommand::class,
            \Modules\Newsletter\Console\ProcessWorkflowsCommand::class,
        ]);

        Event::listen(Registered::class, [WorkflowTriggerListener::class, 'handleRegistered']);
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
        $sourcePath = __DIR__.'/../../resources/views';
        $theme = config('backoffice.theme', 'backend');
        $themePath = __DIR__.'/../../resources/views/themes/'.$theme;
        $paths = is_dir($themePath) ? [$themePath, $sourcePath] : [$sourcePath];
        $this->loadViewsFrom($paths, 'newsletter');
        $this->loadRoutesFrom(__DIR__.'/../../routes/web.php');
    }
}
