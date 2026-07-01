<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Trait de confort ajouté à App\Models\User : délègue TOUJOURS à
 * Services\TierGateService (source unique de vérité, aucune logique dupliquée
 * ici). Même esprit que Modules\Team\Traits\HasTeams déjà utilisé sur User.
 */

declare(strict_types=1);

namespace Modules\Academy\Traits;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Academy\Models\SubscriptionTier;
use Modules\Academy\Models\UserSubscriptionTier;
use Modules\Academy\Services\TierGateService;

trait HasSubscriptionTier
{
    public function subscriptionTierAssignments(): HasMany
    {
        return $this->hasMany(UserSubscriptionTier::class, 'user_id');
    }

    public function currentSubscriptionTier(): ?SubscriptionTier
    {
        return app(TierGateService::class)->currentTierFor($this);
    }

    public function hasSubscriptionFeature(string $featureKey): bool
    {
        return app(TierGateService::class)->hasFeature($this, $featureKey);
    }
}
