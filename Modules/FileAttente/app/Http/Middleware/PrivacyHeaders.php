<?php

declare(strict_types=1);

namespace Modules\FileAttente\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Vie privée (Loi 25) : aucune fuite de jeton par Referer, aucune indexation, aucun cache.
 *
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 */
class PrivacyHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $response->headers->set('Referrer-Policy', 'no-referrer');
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow, noarchive');
        $response->headers->set('Cache-Control', 'no-store, private');

        return $response;
    }
}
