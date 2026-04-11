<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\ShortUrl\Providers;

use Modules\Core\Providers\BaseModuleServiceProvider;

class ShortUrlServiceProvider extends BaseModuleServiceProvider
{
    protected string $name = 'ShortUrl';

    protected string $nameLower = 'shorturl';

    public function boot(): void
    {
        $this->bootModule();

        \Modules\ShortUrl\Models\ShortUrl::observe(\Modules\ShortUrl\Observers\ShortUrlVisitObserver::class);

        $this->commands([
            \Modules\ShortUrl\Console\CleanupExpiredCommand::class,
        ]);
    }

    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);
    }
}
