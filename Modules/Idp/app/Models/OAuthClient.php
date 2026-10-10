<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 */

declare(strict_types=1);

namespace Modules\Idp\Models;

use Illuminate\Contracts\Auth\Authenticatable;
use Laravel\Passport\Client as PassportClient;

/**
 * Client OAuth2 de l'IdP laveille.
 *
 * Passport 13 ne saute JAMAIS l'écran de consentement par défaut (Client::skipsAuthorization
 * renvoie false) et AUCUNE vue d'autorisation n'est définie dans ce projet. Sans ce modèle, le
 * flux /oauth/authorize casserait au moment du consentement.
 *
 * Cet IdP ne sert qu'un client FIRST-PARTY de la même organisation (le Moodle formations.laveille.ai,
 * sans propriétaire). Pour un tel client, le consentement explicite n'apporte rien - l'utilisateur
 * se connecte délibérément à l'académie, et l'information de transparence vit dans la politique de
 * confidentialité. On saute donc l'écran, EXACTEMENT comme une application maison.
 *
 * Garde de prudence en DEUX conditions (durcissement après revue adversariale 2026-10-10) :
 * le client doit être first-party (sans propriétaire) ET son NOM doit figurer dans la liste de
 * confiance `idp.trusted_client_names`. « Sans propriétaire » seul ne suffit PAS : un client créé
 * par artisan sans `--user` est sans propriétaire sans être pour autant de confiance. Un éventuel
 * client tiers, ou un client interne non listé, reverrait l'écran de consentement - ce qui exigerait
 * alors de définir Passport::authorizationView (non défini aujourd'hui, car un seul client est prévu).
 */
class OAuthClient extends PassportClient
{
    /**
     * @param  \Laravel\Passport\Scope[]  $scopes
     */
    public function skipsAuthorization(Authenticatable $user, array $scopes): bool
    {
        return $this->firstParty()
            && in_array((string) $this->name, (array) config('idp.trusted_client_names', []), true);
    }
}
