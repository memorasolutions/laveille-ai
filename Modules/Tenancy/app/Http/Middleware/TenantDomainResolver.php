<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Tenancy\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Tenancy\Models\Tenant;
use Modules\Tenancy\Services\TenantService;
use Symfony\Component\HttpFoundation\Response;

class TenantDomainResolver
{
    public function __construct(
        protected TenantService $tenantService
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        // Skip if tenant already resolved
        if ($this->tenantService->getCurrent()) {
            return $next($request);
        }

        $host = $request->getHost();

        // 1. Exact domain match
        $tenant = Tenant::where('domain', $host)->first();

        // 2. Subdomain fallback
        if (! $tenant) {
            $parts = explode('.', $host);
            if (count($parts) > 2) {
                $tenant = Tenant::where('slug', $parts[0])->first();
            }
        }

        // 3. Apply tenant context
        if ($tenant && $tenant->is_active) {
            $this->tenantService->switchTo($tenant);
            $request->attributes->set('tenant', $tenant);

            if ($request->hasSession()) {
                $request->session()->put('tenant_id', $tenant->id);
            }
        }

        return $next($request);
    }
}
