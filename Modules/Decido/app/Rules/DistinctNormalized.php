<?php

declare(strict_types=1);

namespace Modules\Decido\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Normalizer;

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
 *
 * Round 21 (skill /100) : le round 20 lui-même avait documenté dans son propre test de contrôle
 * négatif une limite non couverte - la variation de FORME Unicode. Un même caractère accentué
 * peut être encodé en octets strictement différents tout en étant rendu de façon IDENTIQUE par
 * tout navigateur : "é" précomposé (NFC, U+00E9, 1 code point) vs "é" décomposé (NFD, U+0065 +
 * U+0301, 2 code points). `mb_strtolower()` seul ne touche jamais à la composition Unicode - il
 * laisse passer intactes deux chaînes visuellement identiques mais différentes en octets. Preuve
 * réelle en conditions HTTP réelles avant ce fix : POST avec options=["café" en NFC, "café" en
 * NFD] créait bien 2 PollOption distinctes rendues à l'identique par le navigateur, recréant le
 * bug de scission de votes des rounds 11/20 via un vecteur invisible à l'oeil nu. Corrigé en
 * appliquant `Normalizer::normalize(..., Normalizer::FORM_C)` (forme canonique composée) AVANT
 * le collapse d'espaces et la mise en minuscules.
 *
 * LIMITE CONNUE ET ASSUMÉE (hors périmètre) : la normalisation NFC ne résout PAS la confusion
 * entre caractères de SCRIPTS Unicode différents qui se ressemblent visuellement (homoglyphes),
 * ex. la lettre latine "a" (U+0061) et la lettre cyrillique "а" (U+0430) - ce sont deux code
 * points sans aucune relation de canonicité, NFC ne les fait jamais converger. Une détection
 * complète des homoglyphes multi-scripts nécessiterait une table de correspondance substantielle
 * (type Unicode TR39/UTS#39 "skeleton") avec un risque réel de faux positifs sur des libellés
 * légitimes contenant des caractères non-latins - jugé hors périmètre raisonnable pour ce module
 * plutôt que d'introduire un correctif cosmétique fragile.
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

            // Round 21 (skill /100) : normalisation de forme Unicode (NFC) AVANT le collapse
            // d'espaces/minuscules - voir docblock de la classe. extension_loaded('intl') est
            // confirmée sur ce projet ; garde défensive si Normalizer::normalize() échoue malgré
            // tout (retourne false sur une chaîne malformée) - on retombe alors sur la chaîne
            // d'origine plutôt que de planter la validation.
            $formOfC = extension_loaded('intl') ? Normalizer::normalize($item, Normalizer::FORM_C) : false;
            $unicodeNormalized = $formOfC !== false ? $formOfC : $item;

            $collapsed = preg_replace('/\s+/u', ' ', trim($unicodeNormalized));
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
