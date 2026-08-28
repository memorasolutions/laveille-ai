<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Sso\Http\Controllers\Scim;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

/**
 * Groups SCIM (RFC 7643 §4.2 / RFC 7644) — HORS SCOPE de cette V1 (voir
 * rapport de livraison). Répond 501 Not Implemented plutôt que de simuler
 * un comportement partiel/trompeur. Activable en gardant
 * sso.scim.groups_enabled=false tant qu'aucune implémentation réelle
 * n'existe (le drapeau documente l'intention, pas un raccourci de code).
 */
class ScimGroupController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return $this->notImplemented();
    }

    public function store(Request $request): JsonResponse
    {
        return $this->notImplemented();
    }

    public function show(Request $request, string $id): JsonResponse
    {
        return $this->notImplemented();
    }

    private function notImplemented(): JsonResponse
    {
        return response()->json([
            'schemas' => ['urn:ietf:params:scim:api:messages:2.0:Error'],
            'detail' => 'Groups SCIM non implémenté dans cette version (scope réduit – voir documentation).',
            'status' => '501',
        ], 501, ['Content-Type' => 'application/scim+json']);
    }
}
