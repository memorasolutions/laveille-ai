<?php

declare(strict_types=1);

namespace Modules\Tools\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Modules\Tools\Services\PromptFromDraftService;

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * Brique 2 - « Partir de mon brouillon » (SPEC-BRIQUE2, design docs/specs/
 * 2026-08-20-bibliotheque-pre-prompts-design.md). Endpoint AJAX consommé par
 * constructeur-prompts-core.js (submitDraft()) : transforme un texte collé par l'utilisateur en
 * état de wizard pré-rempli, via le MÊME format `params` que ?edit=/?remix= (_applyWizardParams()).
 * Couche HTTP volontairement mince - toute la logique de transformation/validation vit dans
 * PromptFromDraftService (jamais dupliquée ici).
 *
 * Loi 25 : le texte brut de l'utilisateur n'est JAMAIS journalisé, ni ici ni dans le service - en
 * cas d'échec, seul le motif technique (JSON invalide, réponse vide) est loggé, jamais le contenu.
 */
class PromptFromDraftController
{
    private const SOFT_ERROR_MESSAGE = "Je n'ai pas pu transformer ce texte, réessaie ou pars du wizard.";

    public function transform(Request $request, PromptFromDraftService $service): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'texte' => 'required|string|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => self::SOFT_ERROR_MESSAGE], 422);
        }

        try {
            $params = $service->transform((string) $request->input('texte'));
        } catch (\Throwable $e) {
            // Jamais le texte de l'utilisateur dans ce log (Loi 25) - seulement le motif technique.
            Log::error('PromptFromDraftController : échec de transformation - '.$e->getMessage());
            $params = null;
        }

        if ($params === null) {
            return response()->json(['message' => self::SOFT_ERROR_MESSAGE], 422);
        }

        return response()->json(['params' => $params]);
    }
}
