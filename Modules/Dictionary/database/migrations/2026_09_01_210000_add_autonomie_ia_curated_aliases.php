<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Modules\Dictionary\Models\Term;

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Rattrapage du correctif ALIAS_NEVER_AUTO (GlossaryLinkifier.php, même date) qui bloque
 * désormais « autonomie »/« autonomies » comme alias dérivé de la fiche « Autonomie (IA) » -
 * MESURÉ : 81 des 137 pages de production qui portaient un lien vers /glossaire/autonomie-ia
 * étaient au mauvais sens (60 batterie/véhicule, 21 humain/géopolitique), 49 correctes, 7
 * ambiguës. Bloquer le mot seul rend la fiche NON liée plutôt que MAL liée sur ces 81 pages -
 * mais sans rattrapage, elle redeviendrait aussi injoignable sur les pages correctes qui
 * n'emploient QUE ce mot seul (le nom PRINCIPAL "Autonomie (IA)" écrit en entier reste, lui,
 * toujours trouvable, mais c'est rare dans une actualité).
 *
 * Ce rattrapage pose 8 alias CURÉS (colonne `aliases`, ORIGIN_CURATED_ALIAS) - des expressions
 * qui ne peuvent pas apparaître dans un contexte de batterie ou de véhicule. Chacune vérifiée
 * SANS collision contre le contenu réel du site (les 606 fiches actualités publiées qui
 * mentionnent "autonomi"/"autonom", scannées via le même mécanisme réel GlossaryLinkifier::
 * linkify(), pas un comptage en base) avant d'être retenue :
 *   - "IA autonome" / "IA autonomes" : 36 occurrences réelles, toutes au sens IA, zéro
 *     collision batterie/véhicule. La plus productive des 8.
 *   - "autonomie de l'IA" / "autonomie décisionnelle" : 0 occurrence actuelle (aucune fiche ne
 *     les emploie encore mot pour mot) mais reprennent la définition même du terme ("capacité à
 *     prendre des décisions... sans intervention humaine constante") - ajoutées pour l'avenir,
 *     sans risque puisqu'absentes de tout contexte batterie/véhicule mesuré.
 *   - "autonomie des agents" (1 occurrence réelle), "autonomie de l'agent" (1), "autonomie des
 *     modèles" (1), "autonomie des machines" (1) : chacune vérifiée individuellement.
 *
 * ÉCARTÉS après vérification (le point (3) du mandat : "certaines pourraient elles-mêmes
 * collisionner") : "agent autonome" et son pluriel "agents autonomes", suggérés en premier
 * réflexe, sont déjà en production le nom PRINCIPAL de la fiche dédiée "Agent autonome" (slug
 * agent-autonome) ET un alias curé de "Agent IA" (slug agent-ia, alias "agent autonome"). Les
 * ajouter ICI n'aurait rien capté de plus (le nom principal d'une autre fiche l'emporte toujours
 * sur un simple alias, correctif #199 du 2026-07-xx) et aurait semé une confusion éditoriale sur
 * quel terme les revendique légitimement.
 *
 * Idempotent (fusionne plutôt qu'écrase - ne perd aucun alias curé par ailleurs) et portable
 * (skip silencieux si le terme est absent, ex. environnement local qui n'a pas cette fiche créée
 * hors dépôt git en production - cf. mémoire projet, même motif que
 * 2026_08_31_093000_relocate_mistral_family_aliases.php).
 */
return new class extends Migration
{
    private const SLUG = 'autonomie-ia';

    private const ALIASES_AJOUTES = [
        "IA autonome",
        "IA autonomes",
        "autonomie de l'IA",
        'autonomie décisionnelle',
        'autonomie des agents',
        "autonomie de l'agent",
        'autonomie des modèles',
        'autonomie des machines',
    ];

    public function up(): void
    {
        if (! class_exists(Term::class)) {
            return;
        }

        $terme = Term::where('slug->fr_CA', self::SLUG)->first();

        if (! $terme) {
            echo '[glossaire] alias curés autonomie-ia - terme absent, skip ('.self::SLUG.")\n";

            return;
        }

        $aliasesActuels = is_array($terme->aliases) ? $terme->aliases : [];
        $terme->aliases = array_values(array_unique([...$aliasesActuels, ...self::ALIASES_AJOUTES]));
        $terme->save();

        echo '[glossaire] alias curés ajoutés à '.self::SLUG.' : '.implode(', ', self::ALIASES_AJOUTES)."\n";
    }

    public function down(): void
    {
        if (! class_exists(Term::class)) {
            return;
        }

        $terme = Term::where('slug->fr_CA', self::SLUG)->first();

        if (! $terme) {
            return;
        }

        $aliasesActuels = is_array($terme->aliases) ? $terme->aliases : [];
        $terme->aliases = array_values(array_diff($aliasesActuels, self::ALIASES_AJOUTES));
        $terme->save();
    }
};
