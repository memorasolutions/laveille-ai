<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Webhooks\Providers;

use Modules\Core\Providers\BaseModuleServiceProvider;

class WebhooksServiceProvider extends BaseModuleServiceProvider
{
    protected string $name = 'Webhooks';

    protected string $nameLower = 'webhooks';

    public function boot(): void
    {
        $this->bootModule();
    }

    public function register(): void
    {
        $this->app->register(EventServiceProvider::class);
        $this->app->register(RouteServiceProvider::class);

        $this->app->singleton(\Modules\Webhooks\Services\WebhookService::class);
    }
}
