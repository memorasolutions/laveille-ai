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
 * Gate « EN CONSTRUCTION » des pages publiques du module Academy.
 *
 * Tant que config('academy.under_construction') est vrai, un visiteur qui
 * n'est PAS superadmin reçoit une page sobre « bientôt disponible » (503),
 * au lieu du contenu réel. Le superadmin (toi) voit le contenu normal et
 * peut donc valider le module en prod avant le lancement public.
 *
 * Quand on retirera le mode construction (env ACADEMY_UNDER_CONSTRUCTION=false),
 * la gate se désactive d'elle-même et les pages deviennent publiques.
 */
class AcademyUnderConstruction
{
    public function handle(Request $request, Closure $next): Response
    {
        if (config('academy.under_construction', true) === true) {
            $user = $request->user();

            // Verrou « en construction » : SEUL le superadmin (toi) accède pour valider en prod.
            // Tout le reste (anonyme ET tout utilisateur connecté, même avec un rôle Académie)
            // reçoit la 503. On rouvrira au public via ACADEMY_UNDER_CONSTRUCTION=false.
            $isSuperAdmin = $user && method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin();

            if (! $isSuperAdmin) {
                // 503 volontaire : la page n'est pas encore disponible publiquement.
                return response(view('academy::public.under-construction'), 503);
            }
        }

        return $next($request);
    }
}
