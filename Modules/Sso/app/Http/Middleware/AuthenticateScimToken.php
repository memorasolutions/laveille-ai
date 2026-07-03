<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Sso\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Modules\Sso\Services\ScimTokenService;

/**
 * Authentifie chaque requête /scim/v2/* par jeton Bearer statique
 * PAR ORGANISATION. Résout la sso_configurations correspondante et
 * l'injecte dans la requête (request()->attributes->get('sso_configuration'))
 * — c'est CETTE valeur qui garantit l'isolation multi-tenant (un jeton d'org
 * A ne peut jamais résoudre les données d'org B, voir contrôleurs SCIM).
 *
 * Répond conformément au format d'erreur SCIM (RFC 7644 §3.12) : JSON avec
 * schemas urn:ietf:params:scim:api:messages:2.0:Error.
 */
class AuthenticateScimToken
{
    public function __construct(private readonly ScimTokenService $tokens)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        abort_if(! config('sso.enabled'), 404);

        $header = (string) $request->header('Authorization', '');

        if (! str_starts_with($header, 'Bearer ')) {
            return $this->scimError('Jeton Bearer SCIM manquant ou mal formé.', 401);
        }

        $plainTextToken = trim(substr($header, 7));
        $token = $this->tokens->resolve($plainTextToken);

        if (! $token) {
            return $this->scimError('Jeton SCIM invalide, révoqué, ou organisation inactive.', 401);
        }

        $request->attributes->set('sso_configuration', $token->ssoConfiguration);
        $request->attributes->set('scim_token', $token);

        return $next($request);
    }

    private function scimError(string $detail, int $status): Response
    {
        return response()->json([
            'schemas' => ['urn:ietf:params:scim:api:messages:2.0:Error'],
            'detail' => $detail,
            'status' => (string) $status,
        ], $status, ['Content-Type' => 'application/scim+json']);
    }
}
