<?php

declare(strict_types=1);

namespace App\Support\ResponseCache;

use Illuminate\Http\Request;
use Spatie\ResponseCache\CacheProfiles\CacheAllSuccessfulGetRequests;

/**
 * Empêche Spatie ResponseCache de mettre en cache les réponses des utilisateurs
 * authentifiés : plusieurs vues publiques affichent du contenu admin-only via
 * @can('view_admin_panel') (barre admin, bouton engrenage, badges...). Sans ce
 * garde-fou, la première visite d'un admin fige cette version dans le cache
 * serveur et la sert ensuite à TOUS les visiteurs suivants (invités inclus).
 */
class SkipAuthenticatedCacheProfile extends CacheAllSuccessfulGetRequests
{
    public function shouldCacheRequest(Request $request): bool
    {
        if ($request->user()) {
            return false;
        }

        // Même garde-fou que ci-dessus, pour le contournement "aperçu" du mode maintenance d'un
        // outil (Modules\Tools\Models\Tool::MAINTENANCE_PREVIEW_*, 2026-09-19) : un visiteur NON
        // connecté (superadmin en aperçu mobile) qui présente le paramètre ou le cookie d'aperçu
        // recevrait sinon la page RÉELLE de l'outil, et cette réponse serait mise en cache par
        // Spatie ResponseCache (cacheResponse:600 sur /outils/{slug}) puis reservie telle quelle à
        // TOUS les visiteurs suivants - contournant entièrement la maintenance publique.
        if (class_exists(\Modules\Tools\Models\Tool::class)) {
            $previewQuery = \Modules\Tools\Models\Tool::MAINTENANCE_PREVIEW_QUERY;
            $previewCookie = \Modules\Tools\Models\Tool::MAINTENANCE_PREVIEW_COOKIE;

            if ($request->query($previewQuery) !== null || $request->cookie($previewCookie) !== null) {
                return false;
            }
        }

        return parent::shouldCacheRequest($request);
    }
}
