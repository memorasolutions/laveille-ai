<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Backup\Providers;

use Modules\Core\Providers\BaseModuleServiceProvider;

class BackupServiceProvider extends BaseModuleServiceProvider
{
    protected string $name = 'Backup';

    protected string $nameLower = 'backup';

    public function boot(): void
    {
        $this->bootModule();
    }

    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);

        $this->app->singleton(\Modules\Backup\Services\BackupService::class);
    }
}
