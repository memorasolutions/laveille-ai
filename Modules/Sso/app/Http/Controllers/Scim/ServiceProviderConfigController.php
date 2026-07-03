<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Sso\Http\Controllers\Scim;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

/**
 * GET /scim/v2/ServiceProviderConfig — RFC 7644 §5. Capacités STATIQUES
 * annoncées aux IdP (Okta/Azure AD lisent ce document à la configuration du
 * connecteur SCIM pour savoir quelles opérations tenter).
 */
class ServiceProviderConfigController extends Controller
{
    public function __invoke(): JsonResponse
    {
        abort_if(! config('sso.enabled'), 404);

        return response()->json([
            'schemas' => ['urn:ietf:params:scim:schemas:core:2.0:ServiceProviderConfig'],
            'documentationUri' => config('app.url').'/sso/saml/metadata',
            'patch' => ['supported' => true],
            'bulk' => ['supported' => false, 'maxOperations' => 0, 'maxPayloadSize' => 0],
            'filter' => ['supported' => true, 'maxResults' => config('sso.scim.max_page_size', 100)],
            'changePassword' => ['supported' => false],
            'sort' => ['supported' => false],
            'etag' => ['supported' => false],
            'authenticationSchemes' => [
                [
                    'type' => 'oauthbearertoken',
                    'name' => 'Bearer Token',
                    'description' => 'Jeton Bearer statique émis par organisation.',
                    'specUri' => 'https://datatracker.ietf.org/doc/html/rfc6750',
                    'primary' => true,
                ],
            ],
            'meta' => ['resourceType' => 'ServiceProviderConfig'],
        ], 200, ['Content-Type' => 'application/scim+json']);
    }
}
