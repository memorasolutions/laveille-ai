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
use Modules\Team\Models\Team;
use Modules\Tenancy\Services\TenantService;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantAccess
{
    public function __construct(
        protected TenantService $tenantService
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $tenant = $this->tenantService->getCurrent();

        if (! $tenant) {
            abort(403, 'No tenant context');
        }

        $user = Auth::user();

        if (! $user) {
            abort(403);
        }

        // Super admin bypasses tenant check
        if ($user->hasRole('super_admin')) {
            return $next($request);
        }

        // Tenant owner has full access
        if ($user->id === $tenant->owner_id) {
            return $next($request);
        }

        // User belongs to a team within this tenant
        $hasAccessViaTeam = Team::query()
            ->where('tenant_id', $tenant->id)
            ->whereHas('members', fn ($query) => $query->where('users.id', $user->id))
            ->exists();

        if ($hasAccessViaTeam) {
            return $next($request);
        }

        abort(403, 'Access denied to this tenant');
    }
}
