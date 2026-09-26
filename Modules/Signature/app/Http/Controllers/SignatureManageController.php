<?php

declare(strict_types=1);

namespace Modules\Signature\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Modules\Signature\Http\Controllers\Concerns\ResolvesSignatureByToken;
use Modules\Signature\Models\Signature;
use Modules\Signature\Services\SignatureContentValidator;
use Modules\Signature\Services\SignatureTemplateRegistry;
use Modules\Signature\Services\SignatureWireframeRenderer;

class SignatureManageController extends Controller
{
    use ResolvesSignatureByToken;

    public function showByToken(string $token)
    {
        $signature = $this->findByToken($token);

        if ($signature->isPurged()) {
            return response()->view('signature::public.expiree', compact('signature'), 200);
        }

        $signature->load('images');

        // Referrer-Policy: no-referrer (section 7.2) - le jeton en clair vit dans l'URL de cette
        // page ; un en-tête HTTP réel protège aussi les ressources tierces, contrairement à la
        // seule balise <meta name="referrer"> (défense en profondeur, pas l'une OU l'autre).
        return response()
            ->view('signature::public.manage', [
                'signature' => $signature,
                'token' => $token,
                'templates' => Signature::templates(),
                'templateDefinitions' => SignatureTemplateRegistry::definitions(),
                'templateWireframes' => SignatureWireframeRenderer::map(),
                'fontFamilies' => SignatureContentValidator::FONT_FAMILIES,
                'socialPlatforms' => SignatureContentValidator::SOCIAL_PLATFORMS,
                // LOT 3 (2026-09-25).
                'portraitShapes' => SignatureContentValidator::PORTRAIT_SHAPES,
                'fontScales' => SignatureContentValidator::FONT_SCALES,
                'initialContent' => $signature->content,
                'initialTemplate' => $signature->template,
                'initialImages' => $signature->imagesPayload(),
                'formAction' => route('signature.manage.update', ['token' => $token]),
                'formMethod' => 'PATCH',
            ])
            ->header('Referrer-Policy', 'no-referrer');
    }

    public function updateByToken(Request $request, string $token): JsonResponse
    {
        $signature = $this->findByToken($token);
        $validated = SignatureContentValidator::validated($request->all());

        $signature->template = $validated['template'];
        $signature->content = $validated['content'];
        if (array_key_exists('reminder_email', $validated)) {
            $signature->reminder_email = $validated['reminder_email'];
        }
        $signature->save();
        $signature->markOwnerActivity();

        return response()->json(['status' => 'ok']);
    }

    public function extendByToken(string $token): JsonResponse
    {
        $signature = $this->findByToken($token);
        $signature->markOwnerActivity();

        return response()->json(['status' => 'ok', 'last_owner_activity_at' => $signature->last_owner_activity_at]);
    }

    /**
     * Un jeton haché ne peut jamais être réaffiché en clair après sa création - seul un détenteur
     * du jeton VALIDE peut en émettre un nouveau, qui invalide immédiatement l'ancien. Le
     * remplacement est IRRÉVERSIBLE côté client (M2.2) : l'éditeur affiche une modale de
     * confirmation avant d'appeler cette route, puisque l'ancien lien cesse de fonctionner
     * immédiatement après.
     */
    public function rotateByToken(string $token): JsonResponse
    {
        $signature = $this->findByToken($token);
        $newToken = Str::random(40);
        $signature->setAdminToken($newToken);
        $signature->markOwnerActivity();

        return response()->json([
            'admin_token' => $newToken,
            'manage_url' => route('signature.manage', ['token' => $newToken]),
        ]);
    }

    /**
     * Conversion en compte (section 7.2) - exige le jeton VALIDE en plus de la connexion. Refuse
     * silencieusement (403) si la signature appartient déjà à un AUTRE compte, pour ne jamais
     * permettre à un jeton compromis de voler une signature déjà rattachée.
     */
    public function attachByToken(Request $request, string $token): JsonResponse
    {
        $signature = $this->findByToken($token);
        $userId = $request->user()->id;

        if ($signature->user_id !== null && $signature->user_id !== $userId) {
            return response()->json(['message' => 'Cette signature est déjà rattachée à un autre compte.'], 403);
        }

        $signature->user_id = $userId;
        $signature->save();
        $signature->markOwnerActivity();

        return response()->json(['status' => 'ok']);
    }
}
