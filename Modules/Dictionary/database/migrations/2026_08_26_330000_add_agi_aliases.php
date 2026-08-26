<?php

use Illuminate\Database\Migrations\Migration;
use Modules\Dictionary\Models\Term;

/**
 * Complète les variantes du terme « AGI », en français, en anglais et en cinq autres langues
 * (2026-08-26).
 *
 * AUCUNE NOUVELLE FICHE. Le contrôle anti-doublon a montré que `agi` existe déjà et qu'il est
 * COMPLET au standard : définition, analogie, exemple, « le saviez-vous », FAQ, sources, termes
 * liés, balisage DefinedTerm et FAQPage, paire d'images webp + jpg. Le seul champ absent était
 * `aliases`, et cette absence coûtait quelque chose de mesurable.
 *
 * LE DÉFAUT MESURÉ, sur une fiche publiée le jour même
 * (`/actualites/pas-encore-altman-dit-quopenai-aura-une-agi-interne-...`) : le corps contient
 * bien « intelligence artificielle générale », mais l'auto-lien n'attrapait que la sous-chaîne
 * « intelligence artificielle » et envoyait le lecteur vers la fiche GÉNÉRIQUE `/glossaire/ia`.
 * Le sigle « AGI » était lié sept fois vers la bonne page, l'expression française développée
 * zéro fois. Le lecteur qui rencontre le terme en toutes lettres était donc le seul à ne pas
 * recevoir la définition précise.
 *
 * POURQUOI CELA SE CORRIGE TOUT SEUL une fois l'alias posé : `GlossaryLinkifier::loadTerms()`
 * trie les entrées par LONGUEUR DÉCROISSANTE. « intelligence artificielle générale » est plus
 * longue que « intelligence artificielle » et gagne donc l'arbitrage sans qu'aucune règle de
 * priorité n'ait à être écrite.
 *
 * AUCUNE ENTRÉE DANS LE MODULE ACRONYMES, délibérément. `acronymes-education/ia` existe, ce qui
 * pouvait laisser croire qu'AGI y avait sa place. Mais le linkifier ne retient qu'UNE cible par
 * chaîne : un `acronymes-education/agi` entrerait en concurrence directe avec `glossaire/agi`
 * pour le même sigle, et rouvrirait le problème d'ambiguïté que
 * `GlossaryLinkifier::resolveAmbiguousAcronymUrl()` existe précisément pour arbitrer. Une notion
 * bien traitée dans un module n'a pas à être dupliquée dans l'autre.
 *
 * DEUX EXCLUSIONS, motivées plutôt que tues :
 *  - « IAG » est ÉCARTÉ. Le sigle est peu employé en français (« AGI » domine, y compris dans
 *    les textes francophones), et il désigne par ailleurs des sociétés cotées. Poser un alias
 *    rare qui porte un homographe réel, c'est échanger un gain nul contre un risque de faux lien
 *    - le défaut même corrigé le 2026-08-23, quand « Google » renvoyait vers la fiche Gemini.
 *  - « IA générale » est ÉCARTÉ pour une raison interne : la fiche `ia-generale-vs-etroite`
 *    existe et porte exactement cette formulation. L'alias volerait des liens à une page voisine
 *    légitime du site.
 *
 * POURQUOI DES LANGUES ÉTRANGÈRES. Ces formes n'apparaîtront jamais dans le corps français du
 * site : elles ne servent donc pas l'auto-lien, et c'est assumé. Elles alimentent `alternateName`
 * du balisage schema.org, que les moteurs de réponse consultent pour rattacher une requête
 * étrangère à cette page. Elles sont sans risque de faux lien par construction, aucune ne pouvant
 * apparaître dans une phrase française voulant dire autre chose. Une forme canonique par langue,
 * jamais trois : la liste des variantes est affichée au lecteur, et une liste qui enfle cesse
 * d'être lisible.
 *
 * IDEMPOTENTE : les alias sont fusionnés sans doublon, la migration peut se rejouer.
 * RÉVERSIBLE : down() retire UNIQUEMENT les alias posés ici, jamais un préexistant.
 *
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 */
return new class extends Migration
{
    private const SLUG = 'agi';

    /** Français et anglais : formes qui peuvent réellement apparaître dans le corps du site. */
    private const ALIAS_FR_EN = [
        'intelligence artificielle générale',
        'artificial general intelligence',
    ];

    /** Une forme canonique par langue, pour le balisage `alternateName` uniquement. */
    private const ALIAS_AUTRES_LANGUES = [
        'allgemeine künstliche Intelligenz',
        'inteligencia artificial general',
        'intelligenza artificiale generale',
        'inteligência artificial geral',
        'algemene kunstmatige intelligentie',
    ];

    public function up(): void
    {
        // `slug` est TRADUISIBLE (Spatie) : la colonne contient un JSON, et `where('slug', ...)`
        // compare ce JSON entier à une chaîne simple - donc ne correspond JAMAIS.
        $term = Term::where('slug->fr_CA', self::SLUG)->first()
            ?? Term::where('slug->fr', self::SLUG)->first();

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
        $term = Term::where('slug->fr_CA', self::SLUG)->first()
            ?? Term::where('slug->fr', self::SLUG)->first();

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
