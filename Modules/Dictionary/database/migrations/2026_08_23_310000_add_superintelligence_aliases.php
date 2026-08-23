<?php

use Illuminate\Database\Migrations\Migration;
use Modules\Dictionary\Models\Term;

/**
 * Complète les variantes du terme « Superintelligence », en français, en anglais et en cinq
 * autres langues (2026-08-23).
 *
 * AUCUNE NOUVELLE FICHE : le contrôle anti-doublon a montré que `superintelligence` existe déjà
 * et porte quatre variantes (« Artificial Superintelligence », « ASI », « intelligence
 * artificielle supérieure », « super-intelligence »). Le terme voisin `agi` existe aussi, et
 * reste VOLONTAIREMENT séparé : l'intelligence artificielle générale désigne une parité large
 * avec l'humain, la superintelligence un dépassement massif. Deux étapes d'une même échelle ne
 * sont pas deux noms d'une même notion.
 *
 * Aucune relation broader/narrower n'est déclarée ici : entre AGI et superintelligence, aucune
 * des deux n'englobe l'autre au sens du graphe. Poser une relation fausse abîme davantage la
 * navigation que ne le fait son absence.
 *
 * POURQUOI DES LANGUES ÉTRANGÈRES. Ces formes n'apparaîtront jamais dans le corps français du
 * site : elles ne servent donc pas l'auto-lien, et c'est assumé. Elles alimentent le champ
 * `alternateName` du balisage schema.org, que les moteurs de recherche et les moteurs de réponse
 * consultent pour rattacher une requête étrangère à cette page. Elles sont par construction sans
 * risque de faux lien, puisqu'aucune ne peut apparaître dans une phrase française voulant dire
 * autre chose. Une forme canonique par langue, jamais trois : la liste des variantes est affichée
 * au lecteur, et une liste qui enfle cesse d'être lisible.
 *
 * VÉRIFIÉ PAR DEUX ORACLES INDÉPENDANTS avant écriture, et deux exclusions en découlent :
 *  - « IA surhumaine » et « superhuman AI » sont des FAUX SYNONYMES. Une IA peut être surhumaine
 *    aux échecs, au go ou au repliement des protéines sans être une superintelligence : le
 *    dépassement doit être GÉNÉRAL. Un article sur une IA surhumaine dans un seul domaine
 *    recevrait un lien faux, exactement le défaut corrigé le matin même où « Google » renvoyait
 *    vers la fiche du modèle Gemini.
 *  - « super AI » et son miroir français « super-IA » sont écartés pour ambiguïté : dans « a
 *    super AI tool » ou « une super IA », « super » n'est qu'un adjectif d'appréciation.
 *
 * IDEMPOTENTE : les alias sont fusionnés sans doublon, la migration peut se rejouer.
 * RÉVERSIBLE : down() retire UNIQUEMENT les alias posés ici, jamais les quatre préexistants.
 */
return new class extends Migration
{
    private const SLUG = 'superintelligence';

    /** Français et anglais : formes qui peuvent réellement apparaître dans le corps du site. */
    private const ALIAS_FR_EN = [
        'superintelligence artificielle',
        'IA superintelligente',
        'superintelligent AI',
        'AI superintelligence',
    ];

    /** Une forme canonique par langue, pour le balisage `alternateName` uniquement. */
    private const ALIAS_AUTRES_LANGUES = [
        'künstliche Superintelligenz',
        'superinteligencia artificial',
        'superintelligenza artificiale',
        'superinteligência artificial',
        'kunstmatige superintelligentie',
    ];

    public function up(): void
    {
        $term = Term::where('slug', self::SLUG)->first();

        if (! $term) {
            // Base locale désynchronisée de la production : on ne crée SURTOUT pas la fiche ici.
            return;
        }

        $term->aliases = array_values(array_unique(array_merge(
            $term->aliases ?? [],
            self::ALIAS_FR_EN,
            self::ALIAS_AUTRES_LANGUES
        )));

        $term->save();
    }

    public function down(): void
    {
        $term = Term::where('slug', self::SLUG)->first();

        if (! $term) {
            return;
        }

        $term->aliases = array_values(array_diff(
            $term->aliases ?? [],
            self::ALIAS_FR_EN,
            self::ALIAS_AUTRES_LANGUES
        ));

        $term->save();
    }
};
