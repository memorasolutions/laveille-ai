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
use Modules\Tools\Models\Tool;
use Symfony\Component\HttpFoundation\Response;

/**
 * Étend le gate "en construction/révision" de PublicToolController::show() aux routes
 * SATELLITES d'un outil (bibliothèque, API de préférences/prompts) - trouvaille de la passe
 * adversariale du 2026-07-26 : le gate ne protégeait que /outils/{slug}, laissant /user/prompts
 * et l'API prompts/tool-preferences accessibles à tout utilisateur connecté pendant la révision.
 *
 * Slug explicite en paramètre (ex. 'constructeur-prompts') pour les routes dédiées à un seul
 * outil ; sans paramètre, lit le segment {tool} de la route (utilisé par la route générique
 * /api/tool-preferences/{tool}, partagée entre plusieurs outils).
 *
 * Round 21 (2026-07-27) : réutilise Tool::isAccessibleTo() (extrait round 12) au lieu de
 * dupliquer sa propre copie de la logique is_under_construction/isSuperAdmin.
 */
class EnsureToolNotUnderConstruction
{
    public function handle(Request $request, Closure $next, ?string $slug = null): Response
    {
        $slug = $slug ?? $request->route('tool');

        if (! $slug || Tool::isAccessibleTo($slug, $request->user())) {
            return $next($request);
        }

        // 2e passe adversariale (2026-07-26) : forcer JSON sur /api/* même sans en-tête Accept
        // explicite (curl brut, scripts sans headers) - expectsJson() seul laissait passer du
        // HTML 200 pour ces clients, alors que la vraie action protégée ne s'exécutait jamais.
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json(['message' => __('Cet outil est temporairement indisponible.')], 403);
        }

        $tool = Tool::where('slug', $slug)->first();

        return response(view('tools::public.under-construction', compact('tool')));
    }
}
