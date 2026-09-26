<?php

declare(strict_types=1);

namespace Modules\Signature\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Modules\Signature\Models\Signature;
use Modules\Signature\Services\SignatureTemplateRegistry;
use Modules\Signature\Services\SignatureWireframeRenderer;

class SignatureAssistantController extends Controller
{
    /**
     * Assistant public - éditeur VIDE (section 2.1). Reprendre/modifier une signature existante
     * passe par SignatureManageController (lien secret) ou UserSignatureController (« Mes
     * signatures »), jamais par cette route.
     */
    public function create(): View
    {
        return view('signature::public.assistant', [
            'templates' => Signature::templates(),
            // LOT 2 : le registre COMPLET, sérialisé pour l'aperçu JS (window.SIGNATURE_TEMPLATES,
            // voir Modules/Signature/resources/views/public/partials/editor.blade.php).
            'templateDefinitions' => SignatureTemplateRegistry::definitions(),
            // Schémas de disposition (wireframes) des 14 gabarits - source unique du sélecteur
            // d'étape 1 ET de la galerie modale, voir SignatureWireframeRenderer.
            'templateWireframes' => SignatureWireframeRenderer::map(),
            'fontFamilies' => \Modules\Signature\Services\SignatureContentValidator::FONT_FAMILIES,
            'socialPlatforms' => \Modules\Signature\Services\SignatureContentValidator::SOCIAL_PLATFORMS,
            // LOT 3 (2026-09-25).
            'portraitShapes' => \Modules\Signature\Services\SignatureContentValidator::PORTRAIT_SHAPES,
            'fontScales' => \Modules\Signature\Services\SignatureContentValidator::FONT_SCALES,
            'initialContent' => null,
            'initialTemplate' => Signature::templates()[0],
            'formAction' => route('signature.draft.store'),
            'formMethod' => 'POST',
            'signature' => null,
        ]);
    }
}
