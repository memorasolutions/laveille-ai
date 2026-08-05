<?php

declare(strict_types=1);

namespace Modules\Tools\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Modules\Tools\Models\SavedPrompt;

/**
 * Permalien public de partage d'un prompt (/p/{publicId}) + endpoint de données pour le bouton
 * "Remixer" du constructeur de prompts (Phase 1, plan approuvé 2026-08-05).
 *
 * Pattern répliqué de PublicCrosswordController::play() (Modules/Tools/app/Http/Controllers/
 * PublicCrosswordController.php), déjà éprouvé en production pour /jeumc/{identifier}. Simplifié
 * ici : SavedPrompt n'a pas de custom_slug (uniquement public_id), donc aucune logique de
 * redirection vers une URL canonique.
 */
class PublicPromptController
{
    public function show(string $publicId): View|RedirectResponse
    {
        $prompt = SavedPrompt::where('public_id', $publicId)->first();

        if (! $prompt || ! $prompt->is_public) {
            // La cible /outils/constructeur-prompts passe par la route générique /outils/{slug}
            // (Modules/Tools/routes/web.php), gardée par le middleware `cacheResponse:600`
            // (Spatie ResponseCache) : la réponse HTTP y est mise en cache intégralement, donc
            // ->with('error', ...) (flash de session) ne s'affichera JAMAIS de façon fiable une
            // fois la page mise en cache - le HTML servi date d'AVANT ce flash. Le paramètre de
            // requête ?share_error=notfound contourne le problème : il est lu côté client par le
            // JS du constructeur (constructeur-prompts-core.js, init()), qui s'exécute à CHAQUE
            // chargement de page même si le HTML est un snapshot en cache. Le flash de session
            // est conservé en parallèle (inoffensif, utile si la page n'est pas/plus en cache).
            return redirect('/outils/constructeur-prompts?share_error=notfound')
                ->with('error', "Ce prompt n'existe pas ou n'est plus public.");
        }

        return view('tools::public.tools.prompt-share', [
            'prompt' => $prompt,
            'pageTitle' => $prompt->name.' - Prompt partagé',
            'pageDescription' => 'Découvrez et remixez le prompt « '.$prompt->name.' », créé avec le constructeur de prompts de laveille.ai.',
        ]);
    }

    /**
     * Endpoint PUBLIC (aucune authentification requise) consommé par ?remix={publicId} du
     * constructeur (voir public/assets/tools/constructeur-prompts/constructeur-prompts-core.js).
     * Renvoie la même structure JSON que SavedPromptController::show() (name, prompt_text,
     * params, public_id...), mais le scope de la requête est volontairement DIFFÉRENT : jamais
     * par user_id (SavedPromptController::show() reste réservé au propriétaire), uniquement par
     * public_id + is_public=true. Un prompt resté privé ne peut donc jamais transiter par cette
     * route, quel que soit l'appelant - test IDOR explicite dans PublicPromptControllerTest.
     */
    public function remixData(string $publicId): JsonResponse
    {
        $prompt = SavedPrompt::where('public_id', $publicId)
            ->where('is_public', true)
            ->first();

        if (! $prompt) {
            return response()->json(['message' => 'Prompt introuvable ou non public.'], 404);
        }

        // Réponse volontairement partielle (jamais le modèle complet) : name/prompt_text/params/
        // public_id sont déjà publics via la page /p/{publicId} elle-même, mais id/user_id/
        // timestamps/tags/is_favorite ne le sont pas - exposer l'identifiant interne du
        // propriétaire à un visiteur anonyme n'apporterait rien et le divulguerait inutilement.
        return response()->json([
            'public_id' => $prompt->public_id,
            'name' => $prompt->name,
            'prompt_text' => $prompt->prompt_text,
            'params' => $prompt->params,
        ]);
    }
}
