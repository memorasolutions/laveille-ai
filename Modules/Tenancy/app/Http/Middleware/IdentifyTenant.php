<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Tenancy\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Tenancy\Models\Tenant;
use Modules\Tenancy\Services\TenantService;
use Symfony\Component\HttpFoundation\Response;

class IdentifyTenant
{
    public function __construct(
        protected TenantService $tenantService
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $tenant = $this->resolveTenant($request);

        if ($tenant && $tenant->is_active) {
            $this->tenantService->switchTo($tenant);

            if ($request->hasSession()) {
                $request->session()->put('tenant_id', $tenant->id);
            }

            $request->attributes->set('tenant', $tenant);
        }

        return $next($request);
    }

    private function resolveTenant(Request $request): ?Tenant
    {
        // 1. Subdomain
        $host = $request->getHost();
        $parts = explode('.', $host);
        if (count($parts) > 2) {
            $subdomain = $parts[0];
            $tenant = Tenant::where('slug', $subdomain)->first();
            if ($tenant) {
                return $tenant;
            }
        }

        // 2. Header X-Tenant-ID
        $headerName = config('tenancy.identification.header', 'X-Tenant-ID');
        $headerValue = $request->header($headerName);
        if ($headerValue) {
            $tenant = Tenant::where('slug', $headerValue)->first();
            if ($tenant) {
                return $tenant;
            }
        }

        // 3. Session
        if ($request->hasSession()) {
            $tenantId = $request->session()->get('tenant_id');
            if ($tenantId) {
                $tenant = Tenant::find($tenantId);
                if ($tenant) {
                    return $tenant;
                }
            }
        }

        // 4. User's default tenant
        $user = Auth::user();
        if ($user) {
            return Tenant::where('owner_id', $user->id)->first();
        }

        return null;
    }
}
