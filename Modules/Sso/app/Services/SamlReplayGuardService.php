<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Sso\Services;

use Illuminate\Database\UniqueConstraintViolationException;
use Modules\Sso\Models\SamlReplayGuard;
use Modules\Sso\Models\SsoConfiguration;

/**
 * Protection anti-rejeu SAML : un InResponseTo (identifiant de l'AuthnRequest
 * d'origine) ne peut être consommé qu'UNE SEULE FOIS par organisation. La
 * contrainte unique en base (sso_replay_unique) est la garde ULTIME —
 * l'appel concurrent est géré sans race condition (2 requêtes simultanées
 * avec le même InResponseTo : une seule gagne, l'autre lève une violation
 * de contrainte et est traitée comme un rejeu).
 */
class SamlReplayGuardService
{
    /**
     * Tente de consommer un InResponseTo. Retourne true si c'est la première
     * fois (assertion acceptée), false si déjà vu (REJEU — assertion à rejeter).
     */
    public function consume(SsoConfiguration $configuration, string $inResponseTo, ?string $assertionId = null): bool
    {
        try {
            SamlReplayGuard::create([
                'sso_configuration_id' => $configuration->getKey(),
                'in_response_to' => $inResponseTo,
                'assertion_id' => $assertionId,
                'consumed_at' => now(),
            ]);

            return true;
        } catch (UniqueConstraintViolationException) {
            return false;
        }
    }

    public function hasBeenConsumed(SsoConfiguration $configuration, string $inResponseTo): bool
    {
        return SamlReplayGuard::query()
            ->where('sso_configuration_id', $configuration->getKey())
            ->where('in_response_to', $inResponseTo)
            ->exists();
    }

    /** Purge les entrées plus vieilles que sso.saml.inresponseto_ttl_days. */
    public function purgeExpired(): int
    {
        $ttlDays = (int) config('sso.saml.inresponseto_ttl_days', 7);

        return SamlReplayGuard::query()
            ->where('consumed_at', '<', now()->subDays($ttlDays))
            ->delete();
    }
}
