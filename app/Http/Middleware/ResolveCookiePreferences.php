<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\CookieCategory;
use Closure;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class ResolveCookiePreferences
{
    public function handle(Request $request, Closure $next): Response
    {
        $cookiePreferences = [];

        if ($request->cookie('cookie_consent')) {
            $decoded = json_decode((string) $request->cookie('cookie_consent'), true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $cookiePreferences = $decoded;
            }
        }

        View::share('cookiePreferences', $cookiePreferences);

        try {
            $cookieCategories = CookieCategory::active()->ordered()->get();
        } catch (QueryException) {
            $cookieCategories = collect();
        }

        View::share('cookieCategories', $cookieCategories);

        return $next($request);
    }
}
