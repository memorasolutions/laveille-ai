<?php

declare(strict_types=1);

namespace Modules\Decido\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Round 20 (skill /100) : la règle Laravel `distinct` compare des CHAÎNES EXACTES - elle ne
 * détecte jamais deux valeurs qui ne diffèrent que par la casse ("Pizza" / "pizza") ou par des
 * espaces internes multiples ("Pizza 4 fromages" / "Pizza  4 fromages"), pourtant visuellement
 * identiques ou quasi-identiques pour un votant (un navigateur collapse les espaces multiples à
 * l'affichage). Round 11 avait bloqué la duplication EXACTE d'options classiques ; ce round
 * bloque le contournement de cette même règle par simple variation de format, qui recrée
 * exactement le même bug de fond que le round 11 (deux PollOption distinctes créées en base,
 * votes scindés silencieusement entre elles, décompte faussé sans jamais remonter d'erreur) -
 * preuve réelle en conditions HTTP réelles avant ce correctif : soumettre `options: ['Pizza',
 * 'pizza']` créait bien 2 PollOption distinctes ET passait la validation intacte.
 *
 * Appliquée au niveau du champ TABLEAU complet (`options`, pas `options.*`) car la détection de
 * doublon nécessite de comparer chaque élément à TOUS les autres, ce qu'une règle par-élément ne
 * peut pas faire seule.
 */
final class DistinctNormalized implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_array($value)) {
            return;
        }

        $seen = [];

        foreach ($value as $item) {
            if (! is_string($item)) {
                continue;
            }

            $collapsed = preg_replace('/\s+/u', ' ', trim($item));
            $normalized = mb_strtolower($collapsed ?? '');

            if ($normalized === '') {
                continue;
            }

            if (isset($seen[$normalized])) {
                $fail(
                    "Les options « {$seen[$normalized]} » et « {$item} » ne diffèrent que par la casse ou des espaces - ".
                    'elles seraient visuellement identiques pour un votant et scinderaient silencieusement les votes '.
                    "entre elles. Renomme l'une des deux pour la distinguer clairement."
                );

                return;
            }

            $seen[$normalized] = $item;
        }
    }
}
