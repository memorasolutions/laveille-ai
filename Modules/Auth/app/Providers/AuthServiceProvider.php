<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Auth\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Modules\Auth\Listeners\SendWelcomeNotification;
use Modules\Auth\Observers\UserObserver;
use Modules\Auth\Policies\UserPolicy;
use Modules\Core\Events\UserCreated;
use Modules\Core\Providers\BaseModuleServiceProvider;

class AuthServiceProvider extends BaseModuleServiceProvider
{
    protected string $name = 'Auth';

    protected string $nameLower = 'auth';

    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        $this->registerCommands();
        $this->registerCommandSchedules();
        $this->bootModule();

        Gate::policy(User::class, UserPolicy::class);
        User::observe(UserObserver::class);
        Event::listen(UserCreated::class, SendWelcomeNotification::class);

        $sourcePath = module_path($this->name, 'resources/views');
        Blade::anonymousComponentPath($sourcePath.'/layouts', $this->nameLower.'::layouts');
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
            \Modules\Auth\Console\UnlockUserCommand::class,
            \Modules\Auth\Console\BlockSuspiciousIps::class,
        ]);
    }

    /**
     * Register command Schedules.
     */
    protected function registerCommandSchedules(): void
    {
        // Scheduling centralisé dans routes/console.php (Laravel standard)
    }
}
