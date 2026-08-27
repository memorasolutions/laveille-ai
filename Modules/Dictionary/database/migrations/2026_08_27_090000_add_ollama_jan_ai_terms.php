<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Modules\Dictionary\Models\Category;
use Modules\Dictionary\Models\Term;

/**
 * Ajout de deux termes voisins au glossaire (2026-08-27) : Ollama et Jan (jan.ai). Traités
 * ensemble car ils servent le même besoin (exécuter des LLM en local) sans être la même chose :
 * Ollama est d'abord un moteur en ligne de commande avec une bibliothèque de modèles ; Jan est
 * une application de bureau avec interface graphique. Utiles à trois actualités publiées la
 * veille au soir (compression/VRAM sur Qwen3.8-Flash-Next, exécution locale, v0.33 d'Ollama) que
 * le linkifier rendra cliquables une fois ces fiches en place.
 *
 * ANTI-DOUBLON (relevé complet du sitemap prod, 510 slugs glossaire) : aucune correspondance pour
 * "ollama", "jan" (motifs "^jan|-jan-|-jan$|jan-ai|janai"), "llm-local", "local", "lm-studio",
 * "llama-cpp". Notions voisines déjà présentes : "poids-ouverts" et "open-source" (licences),
 * "quantification-modele"/"quantization", "gpu", "edge-computing", "inference", "qwen-alibaba" -
 * aucune n'est le même concept qu'Ollama ou Jan (outils d'exécution, pas des notions de licence
 * ou de matériel), donc deux fiches nouvelles, reliées à "poids-ouverts" en broader_slugs (ces
 * outils existent pour faire tourner des modèles publiés en poids ouverts - relation thématique
 * du même type que Docker/Socket, cf. migration 2026_07_26_120000).
 *
 * ALIAS "Jan" SEUL ÉCARTÉ D'OFFICE (prénom courant, abréviation anglaise de janvier, mot
 * néerlandais). Test décisif exécuté sur le linkifier RÉEL (transaction DB locale, rollback
 * immédiat, aucune trace) :
 *   - name="Jan (jan.ai)" + alias "Jan" (case_sensitive) → "Jan Kowalski a publié..." SE LIE
 *     à tort (faux positif confirmé).
 *   - name="Jan (jan.ai)" SANS alias "Jan" → SE LIE QUAND MÊME : extractQualifierAliases() du
 *     GlossaryLinkifier dérive automatiquement la base "Jan" de tout nom au format "X (Y)",
 *     indépendamment du tableau `aliases` posé à la main. Toute forme parenthésée du nom est
 *     donc dangereuse, pas seulement l'alias explicite.
 *   - name="Jan.ai" (sans parenthèses) + alias "Jan AI", match_strategy=case_sensitive → routine
 *     de 8 phrases pièges ("Jan Kowalski a publié une étude.", "En jan. 2026...", "Le 15 jan...",
 *     "Jan Smith travaille chez Google.", "En janvier 2026...") : AUCUN faux lien. Les mentions
 *     légitimes ("Jan.ai", "jan.ai" minuscule, "Jan AI") se lient correctement (la variante
 *     minuscule "jan.ai" est un alias morphologique auto-dérivé par extractMorphologicalAliases(),
 *     lui aussi en casse stricte sur sa propre forme). Configuration retenue.
 *
 * ALIAS ÉCARTÉS (Jan) : "Jan" (motif détaillé ci-dessus). "Menlo Research" n'est pas un alias
 * du produit (c'est l'éditeur, pas un synonyme - même principe que Z.ai/GLM, migration
 * 2026_08_26_350000 : un fabricant ne se confond pas avec ce qu'il publie).
 * ALIAS ÉCARTÉS (Ollama) : aucun alias retenu ni écarté - "Ollama" est un nom déposé sans
 * variante d'écriture connue ; testé en isolation (match_strategy=loose) contre "lama"/"drame"
 * (aucune collision, frontière de mot respectée).
 *
 * RECHERCHE ET SOURCES (2 sources indépendantes par fait clé, chaque URL ouverte et vérifiée
 * HTTP 200 avant citation - pp_search absent de cette session, aucun repli utilisé car les faits
 * étaient vérifiables directement via les sources primaires ci-dessous) :
 *   - Ollama : dépôt GitHub officiel (ollama/ollama, licence MIT confirmée par l'API GitHub,
 *     README lu en entier - port 11434, commande `ollama run`, bibliothèque ollama.com/library
 *     contenant bien Llama/Qwen/Gemma/DeepSeek, vérifié par fetch direct) + TechCrunch (Julie
 *     Bort, 9 juillet 2026, lu en entier : 65 M$ US en série B menée par Theory Ventures, 88 M$
 *     levés au total, fondateur/PDG Jeff Morgan, lancement 2023, 8,9 millions de développeurs
 *     mensuels, 85 % du Fortune 500, 14 employés - repris et confirmé identique dans l'actualité
 *     laveille.ai du 2026-07-09 qui cite ce même article).
 *   - Jan : dépôt GitHub officiel (janhq/jan, README lu en entier - port 1337, MCP, exigences
 *     système 8/16/32 Go de RAM, disponible Microsoft Store/Flathub) + fichier LICENSE du dépôt
 *     (copyright Menlo Research 2025, licence Apache 2.0) + PR #5042 (fusionnée le 2025-05-20,
 *     diff lu : passage de AGPLv3 à Apache 2.0) + site officiel jan.ai (fetch direct : 6,3
 *     millions de téléchargements affichés).
 *
 * Angle retenu : DÉFINITIONNEL/COMPARATIF (ce que chaque outil fait exactement, pas un
 * classement) - les deux évoluent trop vite pour un angle "meilleur outil", et la logique AEO
 * veut une réponse citable stable : "en quoi Ollama diffère de Jan".
 *
 * Typographie OQLF appliquée (espace insécable U+00A0 réelle avant ':' et autour de « » ;
 * aucune espace avant ;!?) - contrôle grep -nP '(?<! ) :|\s+[;!?]' passé (aucune correspondance)
 * sur le fichier de données.
 *
 * Images : public/images/glossaire/{ollama,jan-ai}.{webp,jpg} déposées AVANT cette migration
 * (1200x669, compressées, repli mcp__stock-photos__ car /nanobanana hors d'atteinte dans cette
 * session - aucun logo réel, aucune personne).
 *
 * Données dans database/data/glossaire-batch-2026-08-27-ollama-jan.json.
 * Anti-doublon : skip si le slug existe déjà. RÉVERSIBLE : down() supprime uniquement ces deux
 * termes et retire "ollama"/"jan-ai" de narrower_slugs sur "poids-ouverts" sans toucher au reste
 * du tableau. IDEMPOTENTE : rejouable sans effet si déjà appliquée.
 */
return new class extends Migration
{
    private function terms(): array
    {
        $path = __DIR__.'/../data/glossaire-batch-2026-08-27-ollama-jan.json';
        if (! is_file($path)) {
            return [];
        }

        return json_decode(file_get_contents($path), true) ?: [];
    }

    private function resolveCategoryId(string $slug): ?int
    {
        return Category::where('slug->fr_CA', $slug)->value('id')
            ?? Category::where('slug->fr', $slug)->value('id');
    }

    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }
        if (! class_exists(Term::class) || ! class_exists(Category::class)) {
            echo "[glossaire] modèle Term/Category absent - ignoré\n";

            return;
        }

        $fallbackCatId = $this->resolveCategoryId('outils-et-techniques');
        $allTerms = $this->terms();

        foreach ($allTerms as $i => $t) {
            if (Term::where('slug->fr_CA', $t['slug'])->exists()) {
                echo "[glossaire] slug déjà présent, skip : {$t['slug']}\n";

                continue;
            }

            $catId = $this->resolveCategoryId($t['cat_slug']) ?? $fallbackCatId;

            $term = new Term();
            foreach (['name', 'slug', 'definition', 'analogy', 'example', 'did_you_know', 'one_sentence_answer'] as $tf) {
                $term->setTranslations($tf, ['fr_CA' => $t[$tf], 'fr' => $t[$tf]]);
            }
            $term->faq = $t['faq'];
            $term->sources = $t['sources'];
            $term->aliases = $t['aliases'] ?? [];
            $term->broader_slugs = $t['broader_slugs'] ?? [];
            $term->narrower_slugs = $t['narrower_slugs'] ?? [];
            $term->difficulty = $t['difficulty'];
            $term->icon = $t['icon'];
            $term->type = $t['type'];
            $term->acronym_full = $t['acronym_full'] ?? null;
            $term->dictionary_category_id = $catId;
            $term->hero_image = ! empty($t['has_image']) ? 'images/glossaire/'.$t['slug'].'.webp' : null;
            $term->reference_url = $t['reference_url'] ?? null;
            $term->reference_label = $t['reference_label'] ?? null;
            $term->is_published = true;
            $term->match_strategy = $t['match_strategy'] ?? 'loose';
            $term->sort_order = 900 + $i;
            $term->save();

            echo "[glossaire] inséré : {$t['slug']} (id={$term->id})\n";
        }

        // Relation broader/narrower avec "poids-ouverts" (pattern Docker/Socket, migration
        // 2026_07_26_120000) : Ollama et Jan sont des outils qui font tourner des modèles publiés
        // en poids ouverts - relation thématique, pas une hiérarchie de licence.
        $poidsOuverts = Term::where('slug->fr_CA', 'poids-ouverts')->first();
        if ($poidsOuverts) {
            $narrower = is_array($poidsOuverts->narrower_slugs) ? $poidsOuverts->narrower_slugs : [];
            $changed = false;
            foreach (['ollama', 'jan-ai'] as $childSlug) {
                if (! in_array($childSlug, $narrower, true)) {
                    $narrower[] = $childSlug;
                    $changed = true;
                }
            }
            if ($changed) {
                $poidsOuverts->narrower_slugs = array_values($narrower);
                $poidsOuverts->save();
                echo "[glossaire] poids-ouverts.narrower_slugs += ollama, jan-ai\n";
            } else {
                echo "[glossaire] poids-ouverts.narrower_slugs contient déjà ollama/jan-ai, skip\n";
            }
        } else {
            echo "[glossaire] terme poids-ouverts introuvable, skip relation\n";
        }
    }

    public function down(): void
    {
        if (! class_exists(Term::class)) {
            return;
        }

        $poidsOuverts = Term::where('slug->fr_CA', 'poids-ouverts')->first();
        if ($poidsOuverts) {
            $narrower = is_array($poidsOuverts->narrower_slugs) ? $poidsOuverts->narrower_slugs : [];
            $narrower = array_values(array_diff($narrower, ['ollama', 'jan-ai']));
            $poidsOuverts->narrower_slugs = $narrower;
            $poidsOuverts->save();
        }

        foreach ($this->terms() as $t) {
            Term::where('slug->fr_CA', $t['slug'])->delete();
        }
    }
};
