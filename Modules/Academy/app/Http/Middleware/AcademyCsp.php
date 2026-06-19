<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Academy\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * CSP scopée aux routes Academy uniquement.
 *
 * - frame-ancestors : empêche ce site d'être embarqué ailleurs (clickjacking)
 * - frame-src       : autorise l'iframe vidéo ScreenPal (et Stripe déjà existant)
 *
 * Ne modifie PAS la CSP globale (Modules/Core/ContentSecurityPolicy).
 */
class AcademyCsp
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $videoHost = config('academy.video_embed_host', 'screenpal.com');
        $siteHost  = config('academy.site_host', 'laveille.ai');

        // frame-ancestors : seul notre domaine peut inclure les pages Academy dans un iframe
        // frame-src : on autorise ScreenPal + Stripe (présent dans la CSP globale)
        $policy = implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline'",
            "style-src 'self' 'unsafe-inline'",
            "img-src 'self' data: https:",
            "font-src 'self'",
            "connect-src 'self'",
            "frame-src 'self' https://{$videoHost} https://*.{$videoHost} https://js.stripe.com https://hooks.stripe.com",
            "frame-ancestors 'self' https://{$siteHost} https://*.{$siteHost}",
            "base-uri 'self'",
            "form-action 'self'",
        ]);

        $response->headers->set('Content-Security-Policy', $policy);

        return $response;
    }
}
