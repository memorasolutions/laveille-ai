<?php

declare(strict_types=1);

namespace Modules\Signature\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Modules\Signature\Models\Signature;
use Modules\Signature\Services\SignatureContentValidator;

class SignatureDraftController extends Controller
{
    /**
     * Création d'une signature - visiteur anonyme (lien secret retourné UNE SEULE fois, section
     * 7.2) ou membre connecté (rattachée directement à user_id, visible dans « Mes signatures »
     * sans lien secret à retenir, section 7.1). Le jeton est TOUJOURS généré, même pour un membre
     * connecté (patron Poll uniforme, permet une éventuelle consultation via /gerer/{token} en
     * plus de « Mes signatures » - jamais l'inverse : perdre le lien anonyme reste irrécupérable
     * SAUF via un compte déjà rattaché).
     */
    public function store(Request $request): JsonResponse
    {
        $validated = SignatureContentValidator::validated($request->all());

        $plainToken = Str::random(40);

        $signature = new Signature();
        $signature->user_id = $request->user()?->id;
        $signature->template = $validated['template'];
        $signature->content = $validated['content'];
        $signature->reminder_email = $validated['reminder_email'] ?? null;
        $signature->status = Signature::STATUS_ACTIVE;
        $signature->last_owner_activity_at = now();
        $signature->admin_token_hash = hash('sha256', $plainToken);
        $signature->save();

        return response()->json([
            'id' => $signature->id,
            // Jeton en clair renvoyé UNE SEULE FOIS - jamais journalisé, jamais reconstruit après
            // coup (section 7.2 : « perte du lien = irrécupérable par conception »).
            'admin_token' => $plainToken,
            'manage_url' => route('signature.manage', ['token' => $plainToken]),
        ], 201);
    }
}
