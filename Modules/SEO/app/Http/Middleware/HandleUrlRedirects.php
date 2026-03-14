<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\SEO\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Modules\SEO\Models\UrlRedirect;
use Symfony\Component\HttpFoundation\Response;

class HandleUrlRedirects
{
    public function handle(Request $request, Closure $next): Response
    {
        $path = '/'.ltrim($request->path(), '/');

        $redirect = Cache::remember(
            "url_redirect:{$path}",
            3600,
            fn () => UrlRedirect::findRedirect($path),
        );

        if ($redirect) {
            $redirect->recordHit();
            Cache::forget("url_redirect:{$path}");

            return redirect($redirect->to_url, $redirect->status_code);
        }

        return $next($request);
    }
}
