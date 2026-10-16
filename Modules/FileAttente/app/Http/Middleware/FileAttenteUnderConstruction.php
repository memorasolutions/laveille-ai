<?php

declare(strict_types=1);

namespace Modules\FileAttente\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Garde « EN CONSTRUCTION » de toutes les pages du module (même patron que Decido).
 *
 * Tant que config('file-attente.under_construction') est vrai, tout visiteur qui n'est PAS
 * super-administrateur reçoit une page sobre « Bientôt disponible » (503), y compris sur les
 * liens de scan /f/{jeton}. Le super-administrateur voit le vrai module et peut le valider en
 * production avant le lancement. Variable d'environnement : FILEATTENTE_UNDER_CONSTRUCTION=false.
 *
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 */
class FileAttenteUnderConstruction
{
    public function handle(Request $request, Closure $next): Response
    {
        if (config('file-attente.under_construction', true) === true) {
            $user = $request->user();
            $isSuperAdmin = $user && method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin();

            if (! $isSuperAdmin) {
                return response(view('fileattente::under-construction'), 503);
            }
        }

        return $next($request);
    }
}
