<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 */

namespace Modules\Core\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\Core\Support\Honeypot;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bloque les requêtes dont le champ leurre est rempli.
 *
 * Remplace l'ancien app/Http/Middleware/HoneypotProtection.php, qui cherchait un champ
 * nommé « website_url » qu'aucun formulaire du site n'émet : il était donc inerte sur
 * la seule route où il était appliqué.
 */
final class HoneypotProtection
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Honeypot::isBot($request)) {
            // Journal volontairement pauvre : ni adresse IP, ni contenu soumis. Savoir
            // qu'une route a été visée suffit à mesurer l'abus, et n'oblige à conserver
            // aucun renseignement personnel (Loi 25, minimisation).
            Log::channel('daily')->info('honeypot.blocked', [
                'method' => $request->method(),
                'path' => $request->path(),
            ]);

            // Rejet SILENCIEUX, et c'est délibéré : le robot reçoit une réponse de succès,
            // enregistre une fausse réussite et persiste dans une stratégie qui ne produit
            // rien. Lui renvoyer une erreur lui apprendrait qu'il a été repéré.
            //
            // Ce silence n'est acceptable QUE sur un champ leurre rempli, où la certitude
            // est quasi totale. Il ne doit jamais être appliqué à un dépassement de débit
            // ou à un échec de captcha, où un être humain peut légitimement se retrouver.
            if ($request->expectsJson()) {
                return response()->json(['ok' => true], 200);
            }

            return redirect()->back();
        }

        return $next($request);
    }
}
