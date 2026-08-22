<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 */

namespace Modules\Core\Support;

use Illuminate\Http\Request;

/**
 * Source de vérité UNIQUE du pot de miel anti-robot du site.
 *
 * Avant ce bloc, la même logique était réécrite à la main dans quatre contrôleurs
 * (infolettre, contact, demande de retrait, opt-in auteur) sous trois noms de champ
 * différents. Toute vérification passe désormais par ici, et le composant Blade
 * `<x-core::honeypot />` rend le champ à partir des mêmes attributs.
 */
final class Honeypot
{
    /**
     * Nom canonique du champ leurre. Tout nouveau formulaire utilise celui-ci.
     *
     * Choisi volontairement obscur : les gestionnaires de mots de passe remplissent
     * parfois un champ nommé « website » ou « url », ce qui produirait un faux positif
     * sur un visiteur bien réel. « hp_url » ne ressemble à aucun champ métier connu.
     */
    public const FIELD = 'hp_url';

    /**
     * Anciens noms encore acceptés EN LECTURE seulement.
     *
     * Des pages déjà servies et mises en cache chez des visiteurs émettent encore
     * « website » : les ignorer reviendrait à désactiver leur protection le temps que
     * leur cache expire. On ne les rend plus, on les lit encore.
     *
     * N'y JAMAIS ajouter « website_url » : c'est un vrai champ métier du module
     * Acronyms (site web officiel), l'y mettre casserait ce module.
     *
     * @var array<int, string>
     */
    public const LEGACY_FIELDS = ['website'];

    /**
     * Tous les noms à vérifier : le canonique d'abord, puis les anciens.
     *
     * @return array<int, string>
     */
    public static function fields(): array
    {
        return array_merge([self::FIELD], self::LEGACY_FIELDS);
    }

    /**
     * Vrai si l'un des champs leurres est rempli, donc si la requête vient très
     * probablement d'un robot. Un être humain ne voit jamais ce champ et ne peut pas
     * l'atteindre au clavier.
     */
    public static function isBot(Request $request): bool
    {
        foreach (self::fields() as $field) {
            if ($request->filled($field)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Attributs HTML du champ leurre.
     *
     * `aria-hidden` et `tabindex="-1"` le retirent entièrement du parcours des
     * technologies d'assistance et du clavier : un piège ne doit jamais pouvoir
     * attraper une personne qui navigue au lecteur d'écran.
     *
     * @return array<string, string>
     */
    public static function honeypotAttributes(): array
    {
        return [
            'type' => 'text',
            'name' => self::FIELD,
            'value' => '',
            'tabindex' => '-1',
            'autocomplete' => 'off',
            'aria-hidden' => 'true',
        ];
    }

    /**
     * Les mêmes attributs, rendus en chaîne HTML échappée, pour que le composant
     * Blade n'ait pas à les réassembler à la main et ne puisse donc pas diverger.
     */
    public static function attributesString(): string
    {
        $parts = [];

        foreach (self::honeypotAttributes() as $name => $value) {
            $parts[] = e($name).'="'.e($value).'"';
        }

        return implode(' ', $parts);
    }
}
