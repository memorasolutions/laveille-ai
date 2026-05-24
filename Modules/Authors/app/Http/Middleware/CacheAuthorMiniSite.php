<?php

declare(strict_types=1);

namespace Modules\Authors\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class CacheAuthorMiniSite
{
    public function handle(Request $request, Closure $next, int $maxAge = 300, int $sMaxAge = 600, int $swr = 120): Response
    {
        if ($request->user() !== null) {
            $response = $next($request);
            if ($response instanceof Response) {
                $response->headers->set('Cache-Control', 'private, max-age=60');
            }

            return $response;
        }

        $response = $next($request);

        if (! $response instanceof Response) {
            return $response;
        }

        if ($response->getStatusCode() !== 200) {
            return $response;
        }

        $content = (string) $response->getContent();
        $etag = '"'.substr(hash('xxh3', $content), 0, 16).'"';
        $response->headers->set('Cache-Control', "public, max-age={$maxAge}, s-maxage={$sMaxAge}, stale-while-revalidate={$swr}");
        $response->headers->set('ETag', $etag);
        $response->headers->set('Vary', 'Accept-Encoding, Accept-Language');

        if ($request->header('If-None-Match') === $etag) {
            return response('', 304, $response->headers->all());
        }

        return $response;
    }
}
