<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Shop\Support;

/**
 * Source unique de vérité : l'interface de la boutique (lien de menu « Boutique »,
 * mini-panier dans l'en-tête, « Mes commandes », etc.) doit-elle être visible pour
 * l'utilisateur courant ?
 *
 * La règle est volontairement ALIGNÉE sur les deux gardes de ROUTE de la boutique, pour
 * qu'un lien ne s'affiche JAMAIS vers une page qui répondrait 404 ou 503 :
 *  - en maintenance (SHOP_MAINTENANCE=true) → ShopMaintenanceMode renvoie 503 → on cache tout ;
 *  - réservée au fondateur (SHOP_FOUNDER_ONLY=true) → FounderOnly renvoie 404 à tout le monde
 *    SAUF le super-admin → seul lui voit l'interface ;
 *  - sinon (boutique publique) → visible de tous.
 *
 * Le contrôle super-admin est délégué à User::isSuperAdmin() (source unique), jamais recopié.
 */
final class ShopAccess
{
    public static function uiVisible(): bool
    {
        if (config('shop.maintenance', false)) {
            return false;
        }

        if (! config('shop.founder_only', false)) {
            return true;
        }

        $user = auth()->user();

        return $user !== null
            && method_exists($user, 'isSuperAdmin')
            && $user->isSuperAdmin();
    }
}
