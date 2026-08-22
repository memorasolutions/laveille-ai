<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Modules\Dictionary\Models\Category;
use Modules\Dictionary\Models\Term;

/**
 * Ajout du terme "APEX-Agents" au glossaire (2026-08-21).
 *
 * Contenu déjà rédigé et vérifié par recherche croisée en amont (sources Mercor : article de
 * recherche arXiv 2601.14242 v3 + billet officiel) - repris tel quel, aucune formulation modifiée.
 *
 * Catégorie "intelligence-artificielle" retenue : aucune catégorie dédiée à l'évaluation de
 * modèles n'existe parmi les 6 catégories du glossaire (intelligence-artificielle,
 * concepts-fondamentaux, acronymes-et-sigles, securite-et-ethique, outils-et-techniques,
 * donnees-et-traitement). "intelligence-artificielle" regroupe déjà les concepts/techniques liés
 * aux agents et à leur évaluation (ex. A2A (Agent-to-Agent), LLM-as-a-judge) - la plus proche du
 * sujet (banc d'essai qui évalue des agents d'IA).
 *
 * match_strategy "case_sensitive" : "APEX-Agents"/"ApexBench" sont des noms propres à casse
 * distinctive ; "apex" est aussi un mot anglais courant (sommet) - la casse stricte évite les faux
 * positifs, comme pour les autres noms propres/acronymes du glossaire (mitre-attck, sudo, tcc,
 * laravel-herd, cf. migration 2026_07_26_110000_fix_invalid_exact_match_strategy).
 *
 * broader_slugs contient "agent-ia", qui n'existe pas encore comme terme au glossaire au moment de
 * cette migration - laissé tel quel (valeur fournie dans le contenu vérifié, non modifiée) ; le lien
 * restera simplement inactif tant que ce terme parent n'est pas créé.
 *
 * Données dans database/data/glossaire-batch-2026-08-21-apex-agents.json.
 * Anti-doublon : skip si le slug existe déjà. RÉVERSIBLE : down() supprime le terme par slug.
 *
 * Correction 2026-08-21 (après coup) : les images (apex-agents.jpg / .webp, 1200x669) ont été
 * produites APRÈS l'écriture initiale de cette migration, avec "has_image": false dans le JSON -
 * le terme est donc entré sans hero_image. Une fois les fichiers déposés dans
 * public/images/glossaire/, le flag a été corrigé à "has_image": true dans le fichier de données
 * (le code de up() ci-dessous gérait déjà correctement ce flag, aucun changement de logique requis)
 * puis la migration a été rejouée en local (rollback + migrate) pour renseigner hero_image.
 */
return new class extends Migration
{
    private function terms(): array
    {
        $path = __DIR__.'/../data/glossaire-batch-2026-08-21-apex-agents.json';
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
