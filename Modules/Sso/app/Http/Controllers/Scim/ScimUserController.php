<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Sso\Http\Controllers\Scim;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Modules\Sso\Models\SsoConfiguration;
use Modules\Sso\Services\ScimAuditService;
use Modules\Sso\Services\ScimPatchApplier;
use Modules\Sso\Services\ScimUserMapper;

/**
 * Endpoints SCIM 2.0 Users (RFC 7643 Core Schema / RFC 7644 Protocol).
 *
 * ISOLATION MULTI-TENANT (anti-IDOR) : chaque action ne peut lire/modifier
 * QUE des utilisateurs provisionnés par l'organisation du jeton Bearer
 * courant. Ceci est garanti par la table pivot organisation<->utilisateur
 * (voir migration sso_provisioned_users) — un jeton d'org A ne peut jamais
 * cibler un utilisateur provisionné par org B (404, pas 403, pour ne pas
 * confirmer l'existence de la ressource — pattern SCIM standard).
 */
class ScimUserController extends Controller
{
    public function __construct(
        private readonly ScimUserMapper $mapper,
        private readonly ScimPatchApplier $patchApplier,
        private readonly ScimAuditService $audit,
    ) {
    }

    /** GET /scim/v2/Users */
    public function index(Request $request): JsonResponse
    {
        $configuration = $this->currentOrganization($request);

        $startIndex = max(1, (int) $request->query('startIndex', 1));
        $count = min(
            (int) config('sso.scim.max_page_size', 100),
            max(1, (int) $request->query('count', config('sso.scim.default_page_size', 20)))
        );

        $query = $this->provisionedUsersQuery($configuration);

        $filter = (string) $request->query('filter', '');
        if ($filter !== '') {
            $this->applyFilter($query, $filter);
        }

        $total = (clone $query)->count();

        $users = $query
            ->orderBy('users.id')
            ->skip($startIndex - 1)
            ->take($count)
            ->get();

        return response()->json([
            'schemas' => ['urn:ietf:params:scim:api:messages:2.0:ListResponse'],
            'totalResults' => $total,
            'startIndex' => $startIndex,
            'itemsPerPage' => $users->count(),
            'Resources' => $users->map(fn (User $user) => $this->mapper->toScim($user))->all(),
        ], 200, ['Content-Type' => 'application/scim+json']);
    }

    /** GET /scim/v2/Users/{id} */
    public function show(Request $request, string $id): JsonResponse
    {
        $configuration = $this->currentOrganization($request);
        $user = $this->findProvisionedOrFail($configuration, $id);

        return $this->scimResponse($this->mapper->toScim($user));
    }

    /** POST /scim/v2/Users */
    public function store(Request $request): JsonResponse
    {
        $configuration = $this->currentOrganization($request);
        $payload = (array) $request->json()->all();

        $attributes = $this->mapper->fromScim($payload);

        if (empty($attributes['email'])) {
            return $this->scimError('userName ou emails[0].value requis.', 400);
        }

        $existing = User::where('email', $attributes['email'])->first();

        if ($existing) {
            // L'utilisateur existe déjà (créé par un autre canal ou déjà
            // provisionné par cette organisation) — on le rattache/actualise
            // plutôt que de créer un doublon (conforme SCIM : idempotence).
            $existing->fill($attributes);
            $existing->save();
            $this->attachProvisioning($configuration, $existing);
            $this->audit->record($configuration, $existing, 'updated', ['via' => 'scim.store.existing']);

            return $this->scimResponse($this->mapper->toScim($existing), 200);
        }

        $user = new User();
        $user->fill($attributes);
        $user->password = Hash::make($this->mapper->randomPassword());
        $user->is_active = $attributes['is_active'] ?? true;
        $user->save();

        $this->attachProvisioning($configuration, $user);
        $this->audit->record($configuration, $user, 'created');

        return $this->scimResponse($this->mapper->toScim($user), 201);
    }

    /** PUT /scim/v2/Users/{id} — remplacement complet */
    public function update(Request $request, string $id): JsonResponse
    {
        $configuration = $this->currentOrganization($request);
        $user = $this->findProvisionedOrFail($configuration, $id);

        $payload = (array) $request->json()->all();
        $attributes = $this->mapper->fromScim($payload);

        $user->fill($attributes);
        $user->save();

        $this->audit->record($configuration, $user, 'updated', ['via' => 'scim.put']);

        return $this->scimResponse($this->mapper->toScim($user));
    }

    /** PATCH /scim/v2/Users/{id} — mise à jour incrémentale (RFC 7644 §3.5.2) */
    public function patch(Request $request, string $id): JsonResponse
    {
        $configuration = $this->currentOrganization($request);
        $user = $this->findProvisionedOrFail($configuration, $id);

        $payload = (array) $request->json()->all();
        $operations = (array) ($payload['Operations'] ?? []);

        $attributes = $this->patchApplier->apply($operations);

        $wasActive = (bool) ($user->is_active ?? true);

        if ($attributes !== []) {
            $user->fill($attributes);
            $user->save();
        }

        $nowActive = (bool) ($user->is_active ?? true);

        if ($wasActive && ! $nowActive) {
            $this->audit->record($configuration, $user, 'deactivated', ['via' => 'scim.patch']);
        } elseif (! $wasActive && $nowActive) {
            $this->audit->record($configuration, $user, 'reactivated', ['via' => 'scim.patch']);
        } else {
            $this->audit->record($configuration, $user, 'updated', ['via' => 'scim.patch']);
        }

        return $this->scimResponse($this->mapper->toScim($user));
    }

    /**
     * DELETE /scim/v2/Users/{id} — DÉSACTIVATION, JAMAIS de suppression
     * physique de données (spec explicite : is_active=false, détache le
     * provisioning de CETTE organisation, ne touche pas au compte lui-même
     * si l'utilisateur est aussi rattaché à une autre organisation).
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $configuration = $this->currentOrganization($request);
        $user = $this->findProvisionedOrFail($configuration, $id);

        $user->is_active = false;
        $user->save();

        $this->detachProvisioning($configuration, $user);
        $this->audit->record($configuration, $user, 'deactivated', ['via' => 'scim.delete']);

        return response()->json(null, 204);
    }

    private function currentOrganization(Request $request): SsoConfiguration
    {
        /** @var SsoConfiguration $configuration */
        $configuration = $request->attributes->get('sso_configuration');

        abort_if(! $configuration, 401);

        return $configuration;
    }

    private function provisionedUsersQuery(SsoConfiguration $configuration)
    {
        return User::query()
            ->join('sso_provisioned_users', 'sso_provisioned_users.user_id', '=', 'users.id')
            ->where('sso_provisioned_users.sso_configuration_id', $configuration->getKey())
            ->select('users.*');
    }

    private function findProvisionedOrFail(SsoConfiguration $configuration, string $id): User
    {
        $user = $this->provisionedUsersQuery($configuration)->where('users.id', $id)->first();

        abort_if(! $user, 404);

        return $user;
    }

    private function attachProvisioning(SsoConfiguration $configuration, User $user): void
    {
        DB::table('sso_provisioned_users')->updateOrInsert(
            ['sso_configuration_id' => $configuration->getKey(), 'user_id' => $user->getKey()],
            ['created_at' => now(), 'updated_at' => now()]
        );
    }

    private function detachProvisioning(SsoConfiguration $configuration, User $user): void
    {
        DB::table('sso_provisioned_users')
            ->where('sso_configuration_id', $configuration->getKey())
            ->where('user_id', $user->getKey())
            ->delete();
    }

    /**
     * Filtrage BASIQUE : supporte uniquement `attr eq "valeur"` sur userName
     * ou emails.value (cas d'usage quasi exclusif des IdP réels pour vérifier
     * l'existence d'un compte avant création). Toute autre syntaxe de filtre
     * SCIM (and/or/not, pr, co, sw...) est IGNORÉE proprement (pas d'erreur
     * 500) — retourne la liste non filtrée plutôt que de mal interpréter.
     */
    private function applyFilter($query, string $filter): void
    {
        if (preg_match('/^\s*(userName|emails(?:\.value)?)\s+eq\s+"([^"]*)"\s*$/i', $filter, $matches) === 1) {
            $query->where('users.email', $matches[2]);
        }
    }

    private function scimResponse(array $body, int $status = 200): JsonResponse
    {
        return response()->json($body, $status, ['Content-Type' => 'application/scim+json']);
    }

    private function scimError(string $detail, int $status): JsonResponse
    {
        return response()->json([
            'schemas' => ['urn:ietf:params:scim:api:messages:2.0:Error'],
            'detail' => $detail,
            'status' => (string) $status,
        ], $status, ['Content-Type' => 'application/scim+json']);
    }
}
