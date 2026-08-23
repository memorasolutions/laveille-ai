<?php

use Illuminate\Database\Migrations\Migration;
use Modules\Dictionary\Models\Term;

/**
 * Complète les synonymes français et anglais du terme « Prompt système » (2026-08-23).
 *
 * AUCUNE NOUVELLE FICHE, et c'est le contrôle anti-doublon qui l'a décidé : le terme
 * `prompt-systeme` EXISTE déjà et porte quatre variantes (« System Prompt », « system-prompts »,
 * « instructions système », « consignes système »). En créer un second aurait divisé le
 * référencement entre deux pages qui se cannibalisent et cassé l'auto-lien, qui doit pouvoir
 * choisir UNE cible. La demande portait sur les synonymes : ils sont posés ici.
 *
 * VÉRIFIÉ AVANT D'ÉCRIRE, pas supposé :
 *  - la casse n'est PAS un problème. Le terme utilise la stratégie par défaut `loose`, et
 *    GlossaryLinkifier ajoute le drapeau `i` au motif dans ce cas. L'escalade vers une casse
 *    stricte ne se déclenche que pour un homographe figurant dans STOP_LIST_FR, ce qu'une
 *    locution de deux mots n'est pas. Constaté en production : sur /glossaire/prompt-injection,
 *    « instructions système » en minuscules est bien auto-lié vers cette fiche.
 *  - les formes plurielles ne sont pas à poser à la main : le linkifier les dérive lui-même.
 *    On n'ajoute donc que les SINGULIERS, et jamais un doublon de casse d'un alias existant,
 *    qui n'apporterait rien et alourdirait la liste des variantes affichée au lecteur.
 *
 * VOLONTAIREMENT EXCLUS, chacun pour une raison précise - un faux synonyme abîme l'auto-lien
 * bien plus qu'un synonyme manquant (défaut mesuré le matin même, où « Google » renvoyait vers
 * la fiche du modèle Gemini) :
 *  - « system » seul : mot anglais courant, il ferait un lien sur chaque occurrence du mot ;
 *  - « instructions » seul : même défaut, en pire, et en français comme en anglais ;
 *  - « developer message » et le paramètre « instructions » d'OpenAI : notions VOISINES, pas
 *    équivalentes. Elles désignent l'instruction de la couche applicative, avec sa propre
 *    hiérarchie d'autorité, et méritent une fiche ou une relation, pas un alias ;
 *  - « pré-prompt » : dans CE projet, le mot désigne un gabarit du constructeur de prompts,
 *    c'est-à-dire un état pré-rempli du wizard. L'aliaser ici enverrait le lecteur au mauvais
 *    endroit sur les pages de l'outil ;
 *  - « méta-prompt » : le glossaire porte déjà `meta-prompting`, qui est une autre notion.
 *
 * IDEMPOTENTE : les alias sont fusionnés sans doublon, la migration peut se rejouer.
 * RÉVERSIBLE : down() retire UNIQUEMENT les alias posés ici, jamais les quatre préexistants.
 */
return new class extends Migration
{
    private const SLUG = 'prompt-systeme';

    /** Français : formes au singulier, le pluriel étant dérivé par le linkifier. */
    private const ALIAS_FR = [
        'instruction système',
        'consigne système',
        'message système',
        'invite système',
    ];

    /** Anglais : appellations officielles des éditeurs, hors termes trop génériques. */
    private const ALIAS_EN = [
        'system message',
        'system instruction',
    ];

    public function up(): void
    {
        $term = Term::where('slug', self::SLUG)->first();

        if (! $term) {
            // Base locale désynchronisée de la production : on ne crée SURTOUT pas la fiche ici
            // (ce n'est pas le rôle de cette migration, et une fiche vide serait pire que rien).
            return;
        }

        $term->aliases = array_values(array_unique(array_merge(
            $term->aliases ?? [],
            self::ALIAS_FR,
            self::ALIAS_EN
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
            self::ALIAS_FR,
            self::ALIAS_EN
        ));

        $term->save();
    }
};
