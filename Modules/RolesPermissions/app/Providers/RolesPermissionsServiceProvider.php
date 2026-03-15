<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\RolesPermissions\Providers;

use Illuminate\Support\Facades\Gate;
use Modules\Core\Providers\BaseModuleServiceProvider;

class RolesPermissionsServiceProvider extends BaseModuleServiceProvider
{
    protected string $name = 'RolesPermissions';

    protected string $nameLower = 'rolespermissions';

    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        $this->registerCommands();
        $this->bootModule();

        // super_admin bypasses all permission/policy checks
        Gate::before(fn ($user) => $user->hasRole('super_admin') ? true : null);
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);
    }

    /**
     * Register commands in the format of Command::class
     */
    protected function registerCommands(): void
    {
        $this->commands([
            \Modules\RolesPermissions\Console\SyncPermissionsCommand::class,
        ]);
    }
}
