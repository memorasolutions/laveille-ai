<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Trait de confort ajouté à App\Models\User : source unique de vérité pour
 * répondre à « cet utilisateur a-t-il un profil d'auteur ? ». Même esprit que
 * Modules\Team\Traits\HasTeams et Modules\Academy\Traits\HasSubscriptionTier
 * déjà utilisés sur User - remplace les requêtes Eloquent recopiées dans les
 * routes et les vues (routes/web.php du module Authors, author-name-link,
 * author-settings) par un point d'appel unique.
 */

declare(strict_types=1);

namespace Modules\Authors\Traits;

use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\Authors\Models\AuthorProfile;

trait HasAuthorProfile
{
    /**
     * Un seul profil d'auteur par utilisateur (contrainte de la table
     * author_profiles). Relation mise en cache par Eloquent après le premier
     * accès : un appel répété à isAuthor() dans le même rendu de page ne
     * déclenche jamais une deuxième requête.
     */
    public function authorProfile(): HasOne
    {
        return $this->hasOne(AuthorProfile::class);
    }

    public function isAuthor(): bool
    {
        return $this->authorProfile !== null;
    }
}
