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
 * S80 #48 : restreint l'outil mots-croisés au tester unique (Stéphane) pendant la phase de tests intensifs.
 * Visiteurs anonymes ou autres users authentifiés voient une page sobre 'En construction' (HTTP 403).
 */
class EnsureCrosswordTester
{
    private const TESTER_EMAIL = 'chatgptpro@gomemora.com';

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->email === self::TESTER_EMAIL) {
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
