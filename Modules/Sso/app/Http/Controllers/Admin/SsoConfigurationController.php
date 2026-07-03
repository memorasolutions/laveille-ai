<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Sso\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Sso\Models\SsoConfiguration;
use Modules\Sso\Services\ScimTokenService;

/**
 * CRUD admin des configurations SSO par organisation + émission de jetons
 * SCIM. Gaté can('sso.manage') — pattern IDENTIQUE aux routes admin des
 * autres modules (voir Modules\Academy : can('academy.manage')).
 *
 * Volontairement une API JSON minimale (pas de vues Blade) : ce module livre
 * la CAPACITÉ technique SSO/SCIM ; l'intégration dans l'admin visuel
 * existant (Modules\Backoffice) est un futur ticket UI, hors scope ici.
 */
class SsoConfigurationController extends Controller
{
    public function __construct(private readonly ScimTokenService $tokens)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorizeManage($request);

        return response()->json([
            'data' => SsoConfiguration::query()->latest()->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizeManage($request);

        $validated = $request->validate([
            'organization_slug' => ['required', 'string', 'max:100', 'unique:sso_configurations,organization_slug'],
            'name' => ['required', 'string', 'max:150'],
            'idp_entity_id' => ['required', 'string', 'max:255'],
            'idp_sso_url' => ['required', 'url', 'max:255'],
            'idp_x509_cert' => ['required', 'string'],
            'attribute_mapping' => ['sometimes', 'array'],
        ]);

        $configuration = SsoConfiguration::create($validated);

        return response()->json(['data' => $configuration], 201);
    }

    public function issueScimToken(Request $request, SsoConfiguration $ssoConfiguration): JsonResponse
    {
        $this->authorizeManage($request);

        $name = (string) $request->input('name', 'Jeton SCIM');
        $issued = $this->tokens->issue($ssoConfiguration, $name);

        // Le jeton EN CLAIR n'est retourné qu'ICI, une seule fois.
        return response()->json([
            'token' => $issued['token'],
            'data' => $issued['model'],
        ], 201);
    }

    public function revokeScimToken(Request $request, SsoConfiguration $ssoConfiguration, int $tokenId): JsonResponse
    {
        $this->authorizeManage($request);

        $token = $ssoConfiguration->scimTokens()->findOrFail($tokenId);
        $this->tokens->revoke($token);

        return response()->json(['data' => $token->fresh()]);
    }

    private function authorizeManage(Request $request): void
    {
        abort_if(! config('sso.enabled'), 404);
        abort_if(! $request->user()?->can('sso.manage'), 403);
    }
}
