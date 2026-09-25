<?php

declare(strict_types=1);

namespace Modules\Signature\Http\Controllers\Concerns;

use Illuminate\Http\Exceptions\HttpResponseException;
use Modules\Signature\Models\Signature;

/**
 * Résolution DRY d'une signature par jeton d'administration en clair - patron exact de
 * Modules\Decido\Models\Poll::verifyAdminToken() (hash_equals, jamais de journalisation du jeton
 * en clair). Une réponse GÉNÉRIQUE (404) est rendue pour un jeton invalide ET pour une signature
 * introuvable - jamais de distinction qui laisserait deviner par tâtonnement qu'un jeton est
 * presque correct (section 10 du plan, limites de débit).
 */
trait ResolvesSignatureByToken
{
    protected function findByToken(string $plainToken): Signature
    {
        // hash_equals exige de comparer contre CHAQUE candidat plutôt que de retrouver la ligne
        // par une requête WHERE admin_token_hash = hash(...) : la comparaison en temps constant
        // n'a de sens que si elle s'exécute réellement, une requête indexée sur le hash serait de
        // toute façon en temps quasi-constant côté SQL - on garde donc la comparaison applicative
        // par cohérence avec le patron Poll, sur un ensemble borné par l'index unique.
        $hash = hash('sha256', $plainToken);

        $signature = Signature::where('admin_token_hash', $hash)->first();

        if (! $signature) {
            throw new HttpResponseException(response()->view('signature::public.jeton-invalide', [], 404));
        }

        return $signature;
    }
}
