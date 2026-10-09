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

        $claims = [
            'sub' => (string) $user->getKey(),
            'email' => $user->email,
            'email_verified' => $user->email_verified_at !== null,
            'name' => $user->name,
        ];

        return response()->json(array_intersect_key($claims, array_flip(config('idp.claims', []))));
    }
}
