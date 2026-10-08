<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Shop\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Boutique réservée au fondateur (super-admin) tant que SHOP_FOUNDER_ONLY=true.
 * Tout autre visiteur reçoit un 404 : la boutique paraît inexistante (non lancée).
 * Drapeau absent ou false : aucun effet. Ne jamais appliquer aux webhooks (signatures vérifiées).
 */
class FounderOnly
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('shop.founder_only', false)) {
            return $next($request);
        }

        $user = $request->user();
        if ($user && method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
            return $next($request);
        }

        abort(Response::HTTP_NOT_FOUND);
    }
}
