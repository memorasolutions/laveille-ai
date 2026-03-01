<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HoneypotProtection
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->filled('website_url')) {
            abort(422, 'Validation failed.');
        }

        return $next($request);
    }
}
