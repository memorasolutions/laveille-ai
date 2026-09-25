<?php

declare(strict_types=1);

namespace Modules\Signature\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Modules\Signature\Models\Signature;

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
            'templates' => Signature::TEMPLATES,
            'fontFamilies' => \Modules\Signature\Services\SignatureContentValidator::FONT_FAMILIES,
            'socialPlatforms' => \Modules\Signature\Services\SignatureContentValidator::SOCIAL_PLATFORMS,
            'initialContent' => null,
            'initialTemplate' => Signature::TEMPLATES[0],
            'formAction' => route('signature.draft.store'),
            'formMethod' => 'POST',
            'signature' => null,
        ]);
    }
}
