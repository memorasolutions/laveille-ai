<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 */

declare(strict_types=1);

namespace Modules\Idp\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class UserInfoController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user('idp');

        // Même règle que Modules\Auth\Http\Middleware\EnsureAccountActive : actif et non verrouillé.
        if ($user === null || ! $user->is_active || $user->isLocked()) {
            return response()->json(['message' => 'Compte désactivé.'], 403);
        }

        // Prénom/nom séparés pour le mappage de profil côté client (ex. Moodle given_name/family_name).
        // Repli si first_name/last_name sont vides : 1er mot du nom complet = prénom, le reste = nom.
        $given = (string) ($user->first_name ?? '');
        $family = (string) ($user->last_name ?? '');
        if ($given === '' && $family === '') {
            [$given, $family] = array_pad(preg_split('/\s+/', trim((string) $user->name), 2) ?: [''], 2, '');
        }

        $claims = [
            'sub' => (string) $user->getKey(),
            'email' => $user->email,
            'email_verified' => $user->email_verified_at !== null,
            'name' => $user->name,
            'given_name' => $given,
            'family_name' => $family,
        ];

        // Avatar (claim OIDC « picture ») : seulement si une vraie image a été téléversée
        // (URL HTTP récupérable par le client, jamais le data-URI de repli à initiales).
        if ($user->avatar) {
            $claims['picture'] = asset('storage/'.$user->avatar);
        }

        return response()->json(array_intersect_key($claims, array_flip(config('idp.claims', []))));
    }
}
