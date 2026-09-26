<?php

declare(strict_types=1);

namespace Modules\Signature\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Modules\Signature\Models\Signature;
use Modules\Signature\Services\SignatureContentValidator;
use Modules\Signature\Services\SignaturePurgeService;
use Modules\Signature\Services\SignatureTemplateRegistry;

/**
 * « Mes signatures » - patron direct de Modules\Tools\Http\Controllers\UserPromptController /
 * SavedPrompt (section 7.1 du plan, ramené du lot 2 au lot 1 par décision du fondateur du
 * 2026-09-25).
 */
class UserSignatureController extends Controller
{
    public function index(Request $request): View
    {
        // Une signature purgée (M3.4 : la suppression membre passe désormais par la même
        // quarantaine que la purge automatique, jamais un hard-delete) ne doit plus apparaître
        // dans la liste - la ligne technique survit pour la quarantaine, pas pour l'utilisateur.
        $signatures = Signature::forUser((int) $request->user()->id)
            ->where('status', '!=', Signature::STATUS_PURGED)
            ->orderByDesc('updated_at')
            ->get();

        return view('signature::user.index', compact('signatures'));
    }

    public function edit(Request $request, Signature $signature): View|RedirectResponse
    {
        $this->authorizeOwner($request, $signature);

        // Mineur (spec) : une signature déjà purgée n'a plus de contenu à éditer - bloquer plutôt
        // que d'ouvrir un formulaire vide dont l'enregistrement écrirait sur une coquille purgée.
        if ($signature->isPurged()) {
            return redirect()->route('signature.user.index')->with('status', 'Cette signature a été supprimée.');
        }

        $signature->load('images');

        return view('signature::public.manage', [
            'signature' => $signature,
            'token' => null,
            // B4 : identifiant transmis à l'éditeur pour le chemin membre - ni token (jamais
            // réaffiché après création) ni updateUrl seuls ne suffisent au téléversement d'image,
            // qui doit prouver la propriété autrement (SignatureImageController::resolveSignature()).
            'signatureId' => $signature->id,
            'templates' => Signature::templates(),
            'templateDefinitions' => SignatureTemplateRegistry::definitions(),
            'fontFamilies' => SignatureContentValidator::FONT_FAMILIES,
            'socialPlatforms' => SignatureContentValidator::SOCIAL_PLATFORMS,
            // LOT 3 (2026-09-25).
            'portraitShapes' => SignatureContentValidator::PORTRAIT_SHAPES,
            'fontScales' => SignatureContentValidator::FONT_SCALES,
            'initialContent' => $signature->content,
            'initialTemplate' => $signature->template,
            'initialImages' => $signature->imagesPayload(),
            'formAction' => route('signature.user.update', $signature),
            'formMethod' => 'PATCH',
        ]);
    }

    public function update(Request $request, Signature $signature): JsonResponse
    {
        $this->authorizeOwner($request, $signature);
        $validated = SignatureContentValidator::validated($request->all());

        $signature->template = $validated['template'];
        $signature->content = $validated['content'];
        $signature->save();
        $signature->markOwnerActivity();

        return response()->json(['status' => 'ok']);
    }

    public function extend(Request $request, Signature $signature): JsonResponse
    {
        $this->authorizeOwner($request, $signature);
        $signature->markOwnerActivity();

        return response()->json(['status' => 'ok']);
    }

    /**
     * Suppression EXPLICITE par l'utilisateur (distincte de la purge automatique par inactivité,
     * section 7.1). Correctif M3.4 (DRY strict) : réutilise EXACTEMENT la même logique que la
     * purge automatique (SignaturePurgeService::purge(), partagée) - contenu et images passent
     * d'abord par la quarantaine opérateur (B2) avant que la coquille technique ne soit vidée,
     * JAMAIS un hard-delete immédiat comme avant ce correctif.
     */
    public function destroy(Request $request, Signature $signature, SignaturePurgeService $purgeService): RedirectResponse
    {
        $this->authorizeOwner($request, $signature);

        $disk = (string) config('signature.disk', 'public');
        $purgeService->purge($signature, $disk, 'member-delete');

        return redirect()->route('signature.user.index')->with('status', 'Signature supprimée.');
    }

    private function authorizeOwner(Request $request, Signature $signature): void
    {
        abort_unless($signature->user_id === $request->user()->id, 403);
    }
}
