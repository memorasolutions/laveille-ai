<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Modules\Dictionary\Models\Category;
use Modules\Dictionary\Models\Term;

/**
 * Ajout du terme "ImgEdit-Bench" au glossaire (2026-08-21).
 *
 * Contenu déjà rédigé et vérifié par recherche croisée en amont (sources ImgEdit : article de
 * recherche arXiv 2505.20275 + dépôt officiel GitHub PKU-YuanGroup/ImgEdit) - repris tel quel,
 * aucune formulation modifiée.
 *
 * Catégorie "intelligence-artificielle" retenue : même choix que pour les deux termes voisins
 * "Agents' Last Exam" et "APEX-Agents" (cf. migrations 2026_08_21_220000 et 2026_08_21_210000) -
 * aucune catégorie dédiée à l'évaluation de modèles n'existe parmi les 6 catégories du glossaire,
 * et "intelligence-artificielle" regroupe déjà les concepts/techniques liés à l'évaluation de
 * modèles d'IA.
 *
 * match_strategy "loose" (défaut) : contrairement au cas "ALE" (alias de "Agents' Last Exam",
 * migration 2026_08_21_220000), où "ale" est un mot anglais courant aussi employé en français
 * (style de bière), l'alias "ImgEdit" est une chaîne distinctive - un mot-valise inventé par les
 * auteurs, absent de STOP_LIST_FR (cf. Modules/Core/app/Services/GlossaryLinkifier.php) et sans
 * usage courant en anglais ou en français. Aucun risque de faux positif à laisser GlossaryLinkifier
 * matcher "imgedit"/"ImgEdit"/"IMGEDIT" indépendamment de la casse : la stricte casse n'apporterait
 * ici aucune protection supplémentaire, seulement des occurrences manquées dans le texte courant.
 *
 * Anti-doublon vérifié avant écriture (recherche locale via tinker, 2026-08-21) : aucun terme au
 * slug "imgedit-bench", aucun nom contenant "ImgEdit", aucun alias contenant "ImgEdit" n'existait
 * en base ni dans les migrations/données du module.
 *
 * Images (imgedit-bench.jpg / .webp, 1200x669) déjà déposées dans public/images/glossaire/ au
 * moment de l'écriture de cette migration - has_image=true dès le départ (contrairement au
 * correctif nécessaire sur APEX-Agents, dont les images sont arrivées après coup).
 *
 * Données dans database/data/glossaire-batch-2026-08-21-imgedit-bench.json.
 * Anti-doublon : skip si le slug existe déjà. RÉVERSIBLE : down() supprime le terme par slug.
 */
return new class extends Migration
{
    private function terms(): array
    {
        $path = __DIR__.'/../data/glossaire-batch-2026-08-21-imgedit-bench.json';
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
