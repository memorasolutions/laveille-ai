<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Modules\Dictionary\Models\Term;

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Correctif du defaut de FRONTIERE signale par le fondateur le 2026-09-13, a partir d une phrase
 * reelle du site : « de 0,4 % (GPT-5.6 Terra) a 98,8 % (GPT-4o mini) ». Son constat : « GPT-4o »
 * est reconnu, mais le texte dit « GPT-4o mini ».
 *
 * LE DEFAUT : un nom de modele COMPOSE se fait couper par un alias plus court quand la forme
 * longue n est declaree nulle part. Le lien souligne « GPT-4o », laisse « mini » en texte nu, et
 * envoie le lecteur vers une fiche qui parle d un AUTRE modele. C est un defaut de FRONTIERE et non
 * de cible : le lien pointe vers une page qui existe, ce qui le rend invisible a tout controle qui
 * ne verifie que la destination.
 *
 * MESURE, pas supposition. Sur 8 articles publies contenant des noms composes, DEUX liens coupes :
 *   « GPT-4o » + « mini »        -> /glossaire/chatgpt
 *   « Gemini Omni » + « Flash »  -> /annuaire/gemini-omni
 * Et la contre-preuve du mecanisme dans la meme mesure : « Gemini Flash » n est JAMAIS coupe,
 * parce que cette forme composee EST declaree en alias sur gemini-google. Le moteur trie deja les
 * candidats par LONGUEUR DECROISSANTE (GlossaryLinkifier ligne 852) : la forme longue gagne des
 * lors qu elle existe. Le defaut ne vient donc pas du moteur, il vient du DICTIONNAIRE incomplet.
 *
 * DEUX ORACLES CONSULTES, et ils convergent (les trois autres du protocole n ont pas ete
 * sollicites : l enjeu est technique, borne et reversible - je le signale plutot que de le taire).
 *   DeepSeek (via Hermes) : retient l option curative, declarer les formes composees, en jugeant
 *     l ampleur faible. J oppose a son raisonnement que mon echantillon de 8 articles etait BIAISE,
 *     puisque je les avais choisis parce qu ils contenaient des noms composes.
 *   Perplexity : nomme la doctrine etablie du domaine, « leftmost-longest match + dictionnaire
 *     canonique complet », et precise que les gardes de suffixe ne sont que des EXCEPTIONS, jamais
 *     le mecanisme principal. Le moteur du site fait deja la premiere moitie ; cette migration
 *     comble la seconde.
 *
 * CE QUE CETTE MIGRATION FAIT : elle ajoute a la fiche chatgpt les formes composees reellement
 * presentes dans le corpus publie, mesurees une par une :
 *   « GPT-4o mini » (1 actualite), « GPT-4o-mini » (1), « GPT-4.1 mini » (1).
 * Elle ajoute aussi « GPT-4.1 », qui manquait et ou « GPT-4 » aurait mordu de la meme facon - un
 * quatrieme trou trouve en verifiant le premier.
 * AUCUN ajout speculatif : « GPT-4 Turbo », « GPT-5 mini » et « GPT-5 nano » ont ete cherches et
 * comptes a ZERO dans le corpus, ils ne sont donc pas declares. Un alias qui ne correspond a rien
 * est du bruit, pas une precaution.
 *
 * POURQUOI PAS UNE GARDE GENERIQUE SUR LES SUFFIXES : elle bloquerait le lien au lieu de le poser
 * correctement, et elle traiterait de la meme facon deux cas qui ne le sont pas. « Claude Pro »
 * designe un NIVEAU D OFFRE et lier « Claude » y est juste ; « GPT-4o mini » designe un MODELE
 * DISTINCT et lier « GPT-4o » y est trompeur. La liste TOOL_SUFFIX_SAFE_MODIFIERS du linkifier
 * melange aujourd hui les deux familles - constat laisse au ticket, pas corrige ici.
 *
 * Migration idempotente : un alias deja present n est pas duplique. down() retire exactement les
 * alias ajoutes, sans toucher aux autres.
 */
return new class extends Migration
{
    private const SLUG = 'chatgpt';

    private const ALIAS_AJOUTES = [
        'GPT-4o mini',
        'GPT-4o-mini',
        'GPT-4.1 mini',
        'GPT-4.1',
    ];

    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql' || ! class_exists(Term::class)) {
            return;
        }

        $terme = Term::where('slug->fr_CA', self::SLUG)->first();

        if (! $terme) {
            echo "[glossaire] terme chatgpt absent, ajout d alias ignore\n";

            return;
        }

        $alias = $terme->aliases ?? [];
        $ajoutes = 0;

        foreach (self::ALIAS_AJOUTES as $nouveau) {
            if (! in_array($nouveau, $alias, true)) {
                $alias[] = $nouveau;
                $ajoutes++;
            }
        }

        if ($ajoutes > 0) {
            $terme->aliases = $alias;
            $terme->save();
        }

        echo "[glossaire] chatgpt : {$ajoutes} alias compose(s) ajoute(s)\n";
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

        $terme->aliases = array_values(array_diff($terme->aliases ?? [], self::ALIAS_AJOUTES));
        $terme->save();
    }
};
