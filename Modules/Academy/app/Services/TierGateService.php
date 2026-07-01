<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Gating PAR UTILISATEUR selon le palier d'abonnement Academy courant
 * (freemium/pro/organisation — LMS 2026), distinct des drapeaux GLOBAUX
 * `academy.*_enabled` déjà en place (ceux-ci restent la porte d'entrée : un
 * palier ne peut jamais réactiver une fonctionnalité dont le drapeau global
 * est désactivé, il ne fait que restreindre davantage).
 *
 * Principe fail-safe (jamais bloquant par accident, jamais de 500) :
 *   - drapeau `academy.subscription_tiers_enabled` = false (défaut) → hasFeature()
 *     retourne TOUJOURS true (comportement actuel inchangé, zéro gating ajouté).
 *   - drapeau ON mais palier introuvable/mal configuré → hasFeature() retourne
 *     false PROPREMENT (refus, jamais d'exception qui remonte).
 *   - toute erreur DB (table absente, etc.) est journalisée et absorbée.
 */

declare(strict_types=1);

namespace Modules\Academy\Services;

use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Academy\Models\SubscriptionTier;
use Modules\Academy\Models\UserSubscriptionTier;

final class TierGateService
{
    /** Drapeau maître — voir config('academy.subscription_tiers_enabled'), défaut false. */
    public function isEnabled(): bool
    {
        return (bool) config('academy.subscription_tiers_enabled', false);
    }

    /**
     * Résout le palier COURANT de l'utilisateur : son assignation active la plus
     * récente, sinon le palier marqué `is_default`. Retourne null si rien n'est
     * résolvable (aucune assignation ET aucun palier par défaut configuré) —
     * jamais d'exception.
     */
    public function currentTierFor(?Authenticatable $user): ?SubscriptionTier
    {
        try {
            if ($user !== null) {
                $assignment = UserSubscriptionTier::query()
                    ->active()
                    ->where('user_id', $user->getAuthIdentifier())
                    ->latest('starts_at')
                    ->first();

                /** @var SubscriptionTier|null $assignedTier */
                $assignedTier = $assignment?->tier;

                if ($assignedTier !== null && $assignedTier->is_active) {
                    return $assignedTier;
                }
            }

            // Repli : palier par défaut actif (ex. Freemium).
            /** @var SubscriptionTier|null $default */
            $default = SubscriptionTier::query()
                ->active()
                ->where('is_default', true)
                ->orderBy('sort_order')
                ->first();

            return $default;
        } catch (\Throwable $e) {
            Log::warning('[TierGateService] résolution du palier échouée', ['exception' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Vrai si l'utilisateur a accès à la fonctionnalité Academy $featureKey selon
     * son palier courant. Drapeau OFF (défaut) = toujours true (aucune restriction
     * ajoutée). Drapeau ON et palier introuvable = false (refus propre).
     */
    public function hasFeature(?Authenticatable $user, string $featureKey): bool
    {
        if (! $this->isEnabled()) {
            return true;
        }

        $tier = $this->currentTierFor($user);

        if ($tier === null) {
            return false;
        }

        return $tier->hasFeature($featureKey);
    }

    /** Nombre de sièges du palier courant (paliers organisation), null si non applicable. */
    public function maxSeatsFor(?Authenticatable $user): ?int
    {
        return $this->currentTierFor($user)?->max_seats;
    }

    /**
     * Assigne (manuellement, ADMIN) un palier à un utilisateur : désactive toute
     * assignation active existante puis crée la nouvelle, en transaction. Ne
     * déclenche AUCUN appel Stripe (assignation purement locale).
     */
    public function assignTier(User $user, SubscriptionTier $tier): UserSubscriptionTier
    {
        return DB::transaction(function () use ($user, $tier): UserSubscriptionTier {
            UserSubscriptionTier::query()
                ->where('user_id', $user->id)
                ->where('is_active', true)
                ->update([
                    'is_active' => false,
                    'ends_at'   => now(),
                ]);

            return UserSubscriptionTier::create([
                'user_id'              => $user->id,
                'subscription_tier_id' => $tier->id,
                'is_active'            => true,
                'starts_at'            => now(),
            ]);
        });
    }
}
