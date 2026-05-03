<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Tools\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * S80 #48 + hotfix #49 : restreint l'outil mots-croisés aux admins (permission view_admin_panel)
 * pendant la phase de tests intensifs. Visiteurs anonymes ou users non-admin voient une page
 * sobre 'En construction' (HTTP 403). Cohérent avec EnsureIsAdmin Memora.
 */
class EnsureCrosswordTester
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->can('view_admin_panel')) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'Outil en construction. Accès réservé pendant la phase de tests.',
            ], 403);
        }

        return response()->view('tools::public.tools.mots-croises-construction', [], 403);
    }
}
