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
 * GET /scim/v2/Schemas — RFC 7643 §7. Schéma Core User exposé (schéma Group
 * NON exposé : Groups SCIM hors scope de cette version, voir sso.scim.groups_enabled).
 */
class SchemasController extends Controller
{
    public function __invoke(): JsonResponse
    {
        abort_if(! config('sso.enabled'), 404);

        return response()->json([
            'schemas' => ['urn:ietf:params:scim:api:messages:2.0:ListResponse'],
            'totalResults' => 1,
            'Resources' => [
                [
                    'id' => 'urn:ietf:params:scim:schemas:core:2.0:User',
                    'name' => 'User',
                    'description' => 'Utilisateur Laravel (App\\Models\\User) provisionné via SCIM.',
                    'attributes' => [
                        ['name' => 'userName', 'type' => 'string', 'multiValued' => false, 'required' => true, 'caseExact' => false, 'mutability' => 'readWrite', 'uniqueness' => 'server'],
                        ['name' => 'name', 'type' => 'complex', 'multiValued' => false, 'required' => false, 'subAttributes' => [
                            ['name' => 'formatted', 'type' => 'string'],
                        ]],
                        ['name' => 'displayName', 'type' => 'string', 'multiValued' => false, 'required' => false],
                        ['name' => 'emails', 'type' => 'complex', 'multiValued' => true, 'required' => false, 'subAttributes' => [
                            ['name' => 'value', 'type' => 'string'],
                            ['name' => 'primary', 'type' => 'boolean'],
                            ['name' => 'type', 'type' => 'string'],
                        ]],
                        ['name' => 'active', 'type' => 'boolean', 'multiValued' => false, 'required' => false, 'mutability' => 'readWrite'],
                    ],
                    'meta' => ['resourceType' => 'Schema', 'location' => route('scim.schemas')],
                ],
            ],
        ], 200, ['Content-Type' => 'application/scim+json']);
    }
}
