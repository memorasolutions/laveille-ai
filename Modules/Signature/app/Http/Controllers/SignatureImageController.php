<?php

declare(strict_types=1);

namespace Modules\Signature\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;
use Modules\Signature\Http\Controllers\Concerns\ResolvesSignatureByToken;
use Modules\Signature\Models\Signature;
use Modules\Signature\Models\SignatureImage;
use Modules\Signature\Services\SignatureImagePipeline;
use RuntimeException;

class SignatureImageController extends Controller
{
    use ResolvesSignatureByToken;

    /**
     * Deux chemins d'autorisation (correctif B4) : `token` (visiteur anonyme ou lien secret) OU
     * `signature_id` + session authentifiée + propriété vérifiée (membre depuis « Mes
     * signatures ») - un membre n'a jamais accès au jeton en clair de sa propre signature (jamais
     * réaffiché après création), donc le chemin par jeton lui était structurellement fermé avant
     * ce correctif.
     */
    public function store(Request $request, SignatureImagePipeline $pipeline): JsonResponse
    {
        $validated = $request->validate([
            'token' => ['nullable', 'string'],
            'signature_id' => ['nullable', 'integer'],
            'role' => ['required', 'string', Rule::in(SignatureImage::ROLES)],
            'display_width' => ['required', 'integer', 'min:24', 'max:1200'],
            'image' => ['required', 'file', 'max:'.((int) config('signature.max_upload_bytes', 8 * 1024 * 1024) / 1024)],
        ]);

        $signature = $this->resolveSignature($request, $validated);

        try {
            $image = $pipeline->process(
                $request->file('image')->getRealPath(),
                $signature,
                $validated['role'],
                (int) $validated['display_width']
            );
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $signature->markOwnerActivity();

        return response()->json($image->toAssetPayload(), 201);
    }

    /** @param  array<string, mixed>  $validated */
    private function resolveSignature(Request $request, array $validated): Signature
    {
        if (! empty($validated['token'])) {
            return $this->findByToken($validated['token']);
        }

        abort_if(empty($validated['signature_id']), 422, 'Jeton ou identifiant de signature requis.');
        abort_unless($request->user(), 401);

        $signature = Signature::find($validated['signature_id']);
        abort_unless($signature && $signature->user_id === $request->user()->id, 403);

        return $signature;
    }
}
