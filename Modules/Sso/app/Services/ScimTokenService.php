<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Sso\Services;

use Illuminate\Support\Str;
use Modules\Sso\Models\ScimToken;
use Modules\Sso\Models\SsoConfiguration;

/**
 * Émission et résolution des jetons Bearer SCIM.
 *
 * Même principe que Laravel\Sanctum\PersonalAccessToken : le jeton en clair
 * n'est JAMAIS stocké, seul son hash SHA-256 l'est. Comparaison en temps
 * constant (hash_equals implicite via recherche par hash, pas de comparaison
 * de chaîne côté PHP) pour éviter les attaques par canal auxiliaire (timing).
 */
class ScimTokenService
{
    /**
     * Émet un nouveau jeton pour une organisation. Le jeton EN CLAIR n'est
     * retourné qu'ICI, une seule fois — l'appelant doit le communiquer au
     * client IMMÉDIATEMENT (jamais récupérable ensuite).
     *
     * @return array{token: string, model: ScimToken}
     */
    public function issue(SsoConfiguration $configuration, string $name = 'Jeton SCIM'): array
    {
        $plainTextToken = Str::random(64);

        $model = ScimToken::create([
            'sso_configuration_id' => $configuration->getKey(),
            'name' => $name,
            'token_hash' => hash('sha256', $plainTextToken),
        ]);

        return [
            'token' => $plainTextToken,
            'model' => $model,
        ];
    }

    /**
     * Résout un jeton Bearer en sa configuration SSO d'organisation. Retourne
     * null si le jeton est invalide, révoqué, ou l'organisation inactive —
     * jamais d'exception (l'appelant décide de la réponse HTTP : 401).
     */
    public function resolve(string $plainTextToken): ?ScimToken
    {
        if ($plainTextToken === '') {
            return null;
        }

        $hash = hash('sha256', $plainTextToken);

        /** @var ScimToken|null $token */
        $token = ScimToken::query()
            ->where('token_hash', $hash)
            ->whereNull('revoked_at')
            ->with('ssoConfiguration')
            ->first();

        if (! $token || ! $token->ssoConfiguration || ! $token->ssoConfiguration->is_active) {
            return null;
        }

        $token->forceFill(['last_used_at' => now()])->save();

        return $token;
    }

    public function revoke(ScimToken $token): void
    {
        $token->forceFill(['revoked_at' => now()])->save();
    }
}
