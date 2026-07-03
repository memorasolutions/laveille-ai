<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Sso\Services;

use App\Models\User;
use Illuminate\Support\Str;

/**
 * Traduit entre le schéma SCIM Core User (RFC 7643 §4.1) et le modèle
 * Eloquent App\Models\User existant du projet. AUCUNE nouvelle colonne :
 * userName/emails[0].value -> email, name.formatted/displayName -> name,
 * active -> is_active (colonne déjà existante sur User).
 */
class ScimUserMapper
{
    /**
     * Représentation SCIM d'un utilisateur Laravel (réponse GET/POST/PUT).
     */
    public function toScim(User $user): array
    {
        return [
            'schemas' => ['urn:ietf:params:scim:schemas:core:2.0:User'],
            'id' => (string) $user->getKey(),
            'externalId' => (string) $user->getKey(),
            'userName' => $user->email,
            'name' => [
                'formatted' => $user->name,
            ],
            'displayName' => $user->name,
            'emails' => [
                ['value' => $user->email, 'primary' => true, 'type' => 'work'],
            ],
            'active' => (bool) ($user->is_active ?? true),
            'meta' => [
                'resourceType' => 'User',
                'created' => optional($user->created_at)->toAtomString(),
                'lastModified' => optional($user->updated_at)->toAtomString(),
                'location' => route('scim.users.show', $user->getKey()),
            ],
        ];
    }

    /**
     * Extrait les champs Laravel pertinents d'un payload SCIM entrant
     * (POST/PUT). Ne renvoie QUE les clés présentes dans le payload — le
     * contrôleur décide comment les fusionner (création vs mise à jour).
     */
    public function fromScim(array $payload): array
    {
        $attributes = [];

        $email = $payload['emails'][0]['value'] ?? ($payload['userName'] ?? null);
        if ($email !== null) {
            $attributes['email'] = $email;
        }

        $name = $payload['name']['formatted']
            ?? $payload['displayName']
            ?? null;
        if ($name !== null) {
            $attributes['name'] = $name;
        }

        if (array_key_exists('active', $payload)) {
            $attributes['is_active'] = (bool) $payload['active'];
        }

        return $attributes;
    }

    /** Mot de passe aléatoire fort — l'utilisateur SSO ne se connecte jamais par mot de passe local. */
    public function randomPassword(): string
    {
        return Str::password(32);
    }
}
