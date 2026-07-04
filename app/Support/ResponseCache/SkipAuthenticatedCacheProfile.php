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

        return parent::shouldCacheRequest($request);
    }
}
