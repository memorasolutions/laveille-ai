<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Tenancy\Providers;

use Illuminate\Routing\Router;
use Modules\Core\Providers\BaseModuleServiceProvider;

class TenancyServiceProvider extends BaseModuleServiceProvider
{
    protected string $name = 'Tenancy';

    protected string $nameLower = 'tenancy';

    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        $this->bootModule();

        /** @var Router $router */
        $router = $this->app->make(Router::class);
        $router->aliasMiddleware('identify.tenant', \Modules\Tenancy\Http\Middleware\IdentifyTenant::class);
        $router->aliasMiddleware('tenant.access', \Modules\Tenancy\Http\Middleware\EnsureTenantAccess::class);
        $router->aliasMiddleware('tenant.domain', \Modules\Tenancy\Http\Middleware\TenantDomainResolver::class);
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);

        $this->app->singleton(\Modules\Tenancy\Services\TenantService::class);
    }
}
