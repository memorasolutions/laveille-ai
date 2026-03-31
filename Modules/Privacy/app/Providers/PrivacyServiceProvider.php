<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Privacy\Providers;

use Modules\Core\Providers\BaseModuleServiceProvider;

class PrivacyServiceProvider extends BaseModuleServiceProvider
{
    protected string $name = 'Privacy';

    protected string $nameLower = 'privacy';

    public function boot(): void
    {
        $this->bootModule();
        $this->commands([
            \Modules\Privacy\Console\PurgeExpiredDataCommand::class,
        ]);
    }

    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);
    }
}
