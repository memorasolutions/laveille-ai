<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Modules\Dictionary\Models\Term;

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Ticket #2076, point 2 - « Mistral Large » (et « Mixtral ») pointaient encore vers le produit
 * après le correctif du mot seul « Mistral » (v1.242.0, migration
 * 2026_08_30_120000_add_mistral_term.php, ALIAS_NEVER_AUTO). Mécanisme DISTINCT de celui déjà
 * corrigé : la v1.242.0 bloquait un alias DÉRIVÉ (extractQualifierAliases()/
 * extractMorphologicalAliases(), voir ALIAS_NEVER_AUTO dans GlossaryLinkifier.php). Ici, « Mistral
 * Large » et « Mixtral » sont des alias CURÉS (saisis à la main dans la colonne `aliases` du terme
 * « Mistral (Le Chat) », hors du dépôt git - confirmé le 2026-08-31 en lisant la ligne "Aussi
 * appelé" rendue publiquement sur /glossaire/mistral-le-chat : « Mistral · Mistral Large · Le Chat
 * Mistral · Mixtral »). ALIAS_NEVER_AUTO ne bloque que la chaîne EXACTE "mistral", jamais "mistral
 * large" ni "mixtral" : ces deux alias curés passent donc le filtre et continuent de lier vers le
 * produit.
 *
 * Mesuré sur production le 2026-08-31 (sitemap.xml + recherche interne /recherche, requêtes
 * reproductibles) : 8 liens fautifs sur 7 pages au total, tous vers /glossaire/mistral-le-chat -
 *   - "Mistral Large" (5 pages : /annuaire/mistral-le-chat ×2, /annuaire/mistral ×1,
 *     /annuaire/void-test-6-frontier-llms-go-silent-on-be-silence-live-proof ×1,
 *     /glossaire/score-elo ×1)
 *   - "Mixtral" (3 pages : /annuaire/built-an-obsidian-plugin-that-rephrases-your-writing-
 *     without-takin-over ×1, /glossaire/mixture-of-experts ×1, /glossaire/mistral [fiche éditeur
 *     elle-même, où "Mixtral" fait pourtant partie de SA propre définition] ×1)
 * Les 8 actualités au slug "mistral" (même liste que la v1.242.0) ont été vérifiées individuellement
 * et n'en portent aucun. Variantes "Mistral Small"/"Mistral Medium"/"Magistral"/"Mistral 7B"
 * vérifiées par recherche interne : aucune n'est un alias curé de ce terme (absentes de la ligne
 * "Aussi appelé"), donc hors mécanisme et hors périmètre de ce correctif.
 *
 * REMÈDE : les deux alias appartiennent, par le contenu même de la fiche "Mistral" créée en
 * v1.242.0 (« sa famille de modèles ouverts - Mistral 7B, Mixtral, Small/Medium/Large, Magistral »),
 * au registre de l'ÉDITEUR, jamais au produit Le Chat. Ils sont donc RELOCALISÉS (retirés du terme
 * produit, ajoutés au terme éditeur) plutôt que simplement supprimés : le lien continue de
 * fonctionner, corrigé vers la bonne destination - complète fidèlement l'intention déjà posée par
 * la v1.242.0, ne l'étend pas au-delà.
 *
 * Idempotent (skip si déjà relocalisé) et portable (skip si l'un des deux termes est absent - ex.
 * environnement local, où seule la fiche "mistral" existe : "mistral-le-chat" a été créée hors
 * dépôt git avant le suivi par migrations, cf. mémoire projet).
 */
return new class extends Migration
{
    private const SLUG_PRODUIT = 'mistral-le-chat';

    private const SLUG_EDITEUR = 'mistral';

    private const ALIAS_A_RELOCALISER = ['Mistral Large', 'Mixtral'];

    public function up(): void
    {
        if (! class_exists(Term::class)) {
            return;
        }

        $produit = Term::where('slug->fr_CA', self::SLUG_PRODUIT)->first();
        $editeur = Term::where('slug->fr_CA', self::SLUG_EDITEUR)->first();

        if (! $produit || ! $editeur) {
            echo '[glossaire] relocalisation mistral - terme(s) absent(s), skip ('
                .($produit ? '' : self::SLUG_PRODUIT.' ').($editeur ? '' : self::SLUG_EDITEUR)
                .")\n";

            return;
        }

        $aliasesProduit = is_array($produit->aliases) ? $produit->aliases : [];
        $aliasesEditeur = is_array($editeur->aliases) ? $editeur->aliases : [];

        $produit->aliases = array_values(array_diff($aliasesProduit, self::ALIAS_A_RELOCALISER));
        $produit->save();

        $editeur->aliases = array_values(array_unique([...$aliasesEditeur, ...self::ALIAS_A_RELOCALISER]));
        $editeur->save();

        echo "[glossaire] alias relocalisés : ".implode(', ', self::ALIAS_A_RELOCALISER)
            .' ('.self::SLUG_PRODUIT.' -> '.self::SLUG_EDITEUR.")\n";
    }

    public function down(): void
    {
        if (! class_exists(Term::class)) {
            return;
        }

        $produit = Term::where('slug->fr_CA', self::SLUG_PRODUIT)->first();
        $editeur = Term::where('slug->fr_CA', self::SLUG_EDITEUR)->first();

        if (! $produit || ! $editeur) {
            return;
        }

        $aliasesProduit = is_array($produit->aliases) ? $produit->aliases : [];
        $aliasesEditeur = is_array($editeur->aliases) ? $editeur->aliases : [];

        $produit->aliases = array_values(array_unique([...$aliasesProduit, ...self::ALIAS_A_RELOCALISER]));
        $produit->save();

        $editeur->aliases = array_values(array_diff($aliasesEditeur, self::ALIAS_A_RELOCALISER));
        $editeur->save();
    }
};
