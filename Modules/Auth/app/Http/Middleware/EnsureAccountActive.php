<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Garde UNIQUE : un compte désactivé ou verrouillé ne peut plus rien faire, quelle que soit
 * la voie par laquelle il s'est authentifié (mot de passe, code par courriel, Google/OAuth,
 * passkey, jeton Sanctum) et même si sa session était déjà ouverte avant la désactivation.
 */

declare(strict_types=1);

namespace Modules\Auth\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountActive
{
    public function handle(Request $request, Closure $next): Response
    {
        // ACTION: résoudre l'utilisateur par session ET par jeton Sanctum
        // MCP: SELF (< 5 lignes) | RAISON: ce garde est dans le groupe "api" AVANT auth:sanctum,
        // donc le garde par défaut ("web") ne voit pas encore l'utilisateur d'un jeton Bearer.
        $user = $request->user() ?? $request->user('sanctum');

        if ($user === null || ($user->is_active && ! $user->isLocked())) {
            return $next($request);
        }

        // Message public identique pour désactivé et verrouillé : on ne révèle pas l'état du compte.
        $message = 'Votre compte est désactivé. Communiquez avec nous.';

        if ($request->expectsJson() || $request->is('api/*')) {
            // Un jeton révoqué ne servira plus, même une fois le compte réactivé par erreur.
            $user->currentAccessToken()?->delete();

            return response()->json(['success' => false, 'message' => 'Compte désactivé.'], 403);
        }

        // ACTION: fermer la session web
        // MCP: SELF | RAISON: pas de boucle, car une fois déconnecté, $request->user() est null
        // à la requête suivante et la page de connexion passe sans retomber dans ce garde.
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', $message);
    }
}
