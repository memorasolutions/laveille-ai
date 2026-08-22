<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Modules\Dictionary\Models\Category;
use Modules\Dictionary\Models\Term;

/**
 * Ajout du terme "WeEdit" au glossaire (2026-08-21).
 *
 * Contenu déjà rédigé et vérifié par recherche croisée en amont (sources WeEdit : article de
 * recherche arXiv 2603.11593 + jeu de données officiel Hugging Face + dépôt officiel GitHub
 * HuiZhang0812/WeEdit) - repris tel quel, aucune formulation modifiée.
 *
 * Catégorie "intelligence-artificielle" retenue : même choix que pour les trois termes voisins
 * "APEX-Agents", "Agents' Last Exam" et "ImgEdit-Bench" (cf. migrations 2026_08_21_210000,
 * 2026_08_21_220000 et 2026_08_21_230000) - aucune catégorie dédiée à l'évaluation de modèles
 * n'existe parmi les 6 catégories du glossaire, et "intelligence-artificielle" regroupe déjà les
 * concepts/techniques liés à l'évaluation de modèles d'IA.
 *
 * match_strategy "loose" (défaut) : les trois termes voisins ont divergé sur ce choix selon un
 * seul critère - l'alias risque-t-il de matcher un mot anglais/français COURANT en minuscule,
 * absent de STOP_LIST_FR (cf. Modules/Core/app/Services/GlossaryLinkifier.php) donc non protégé
 * automatiquement ? "APEX-Agents" (alias "ApexBench") et "ALE" (alias de "Agents' Last Exam") sont
 * passés en case_sensitive car "apex" (sommet) et "ale" (style de bière, aussi employé en français
 * dans les microbrasseries) sont des mots anglais usuels qui seraient sinon systématiquement liés en
 * minuscule dans la prose courante. "ImgEdit-Bench" est resté en loose car "imgedit" est un
 * mot-valise inventé, sans aucun usage courant. "WeEdit" suit le même raisonnement qu'ImgEdit :
 * "weedit" (et ses variantes "WeEdit benchmark"/"WeEdit-Bench") est un mot-valise distinctif inventé
 * par les auteurs, absent de STOP_LIST_FR et sans usage courant en anglais ou en français - à la
 * différence de "apex"/"ale", aucun mot anglais/français ordinaire ne se cache derrière cette chaîne
 * (GlossaryLinkifier compare la chaîne complète de l'alias, jamais des sous-mots séparés : "we" et
 * "edit" pris isolément ne sont jamais évalués). La casse stricte n'apporterait donc ici aucune
 * protection supplémentaire, seulement des occurrences manquées dans le texte courant.
 *
 * Anti-doublon vérifié avant écriture (recherche locale via tinker, 2026-08-21) : aucun terme au
 * slug "weedit", aucun nom contenant "WeEdit", aucun alias contenant "WeEdit" n'existait en base ni
 * dans les migrations/données du module (131 termes existants passés en revue).
 *
 * Images (weedit.jpg / .webp, 1200x669 attendu) déposées EN PARALLÈLE de cette migration dans
 * public/images/glossaire/ - has_image=true dès le départ (contrairement au correctif nécessaire sur
 * APEX-Agents, dont les images sont arrivées après coup). Si les fichiers ne sont pas encore présents
 * au moment où up() s'exécute, hero_image pointera quand même vers le chemin attendu (comportement
 * identique aux trois migrations voisines) - à vérifier séparément, sans bloquer cette migration.
 *
 * Données dans database/data/glossaire-batch-2026-08-21-weedit.json.
 * Anti-doublon : skip si le slug existe déjà. RÉVERSIBLE : down() supprime le terme par slug.
 */
return new class extends Migration
{
    private function terms(): array
    {
        $path = __DIR__.'/../data/glossaire-batch-2026-08-21-weedit.json';
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
            echo "[glossaire] modèle Term/Category absent — ignoré\n";

            return;
        }

        $fallbackCatId = $this->resolveCategoryId('intelligence-artificielle');
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
            $term->is_published = true;
            $term->match_strategy = $t['match_strategy'] ?? 'loose';
            $term->sort_order = 900 + $i;
            $term->save();

            echo "[glossaire] inséré : {$t['slug']} (id={$term->id})\n";
        }
    }

    public function down(): void
    {
        if (! class_exists(Term::class)) {
            return;
        }

        foreach ($this->terms() as $t) {
            Term::where('slug->fr_CA', $t['slug'])->delete();
        }
    }
};
