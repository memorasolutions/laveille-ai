<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Decido\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gate « EN CONSTRUCTION » des pages publiques du module Decido (/decido).
 *
 * Copie ligne à ligne de la logique de Modules\Books\Http\Middleware\BooksUnderConstruction,
 * adaptée à config('decido.under_construction') et à la vue decido::public.under-construction.
 *
 * Tant que config('decido.under_construction') est vrai, un visiteur qui n'est
 * PAS superadmin reçoit une page sobre « bientôt disponible » (503), au lieu
 * du contenu réel. Le superadmin (toi) voit le contenu normal et peut donc
 * valider le module en prod avant le lancement public.
 *
 * Quand on retirera le mode construction (env DECIDO_UNDER_CONSTRUCTION=false),
 * la gate se désactive d'elle-même et les pages deviennent publiques.
 */
class DecidoUnderConstruction
{
    public function handle(Request $request, Closure $next): Response
    {
        if (config('decido.under_construction', true) === true) {
            $user = $request->user();

            $isSuperAdmin = $user && method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin();

            if (! $isSuperAdmin) {
                return response(view('decido::public.under-construction'), 503);
            }
        }

        return $next($request);
    }
}
