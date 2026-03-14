<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Auth\Http\Middleware;

use Closure;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureOnboardingCompleted
{
    public function handle(Request $request, Closure $next): Response
    {
        try {
            if (Auth::check()
                && Auth::user()->onboarding_completed_at === null
                && ! $request->routeIs('onboarding.*')
                && ! $request->routeIs('logout')
            ) {
                return redirect()->route('onboarding.index');
            }
        } catch (QueryException) {
            // Table may not exist during testing
        }

        return $next($request);
    }
}
