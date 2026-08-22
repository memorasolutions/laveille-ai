<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Modules\Dictionary\Models\Category;
use Modules\Dictionary\Models\Term;

/**
 * Ajout du terme "Agents' Last Exam" au glossaire (2026-08-21).
 *
 * Contenu déjà rédigé et vérifié par recherche croisée en amont (sources Berkeley RDI : article de
 * recherche arXiv 2606.05405 + site officiel du banc d'essai + dépôt public des tâches) - repris tel
 * quel, aucune formulation modifiée.
 *
 * Catégorie "intelligence-artificielle" retenue : même choix que pour le terme voisin "APEX-Agents"
 * (cf. migration 2026_08_21_210000_add_apex_agents_term) - aucune catégorie dédiée à l'évaluation de
 * modèles n'existe parmi les 6 catégories du glossaire, et "intelligence-artificielle" regroupe déjà
 * les concepts/techniques liés aux agents et à leur évaluation.
 *
 * match_strategy "case_sensitive" : l'alias "ALE" est court (3 lettres) et "ale" est un mot anglais
 * courant (style de bière), aussi employé tel quel en français (ex. microbrasseries québécoises,
 * « une ale », « pale ale »). GlossaryLinkifier n'escalade automatiquement la stratégie 'loose' que
 * si le terme figure dans STOP_LIST_FR (mots/verbes français courants, cf.
 * Modules/Core/app/Services/GlossaryLinkifier.php) - "ale" n'y figure pas, donc rien n'aurait empêché
 * un lien automatique sur chaque occurrence du mot anglais/emprunté "ale" en minuscule. La casse
 * stricte ("ALE" en majuscules, jamais "ale"/"Ale") écarte ce risque de faux positifs, comme pour
 * "APEX-Agents"/"ApexBench" (même raisonnement, cf. migration 2026_08_21_210000 et
 * 2026_07_26_110000_fix_invalid_exact_match_strategy pour les valeurs valides de match_strategy).
 *
 * Anti-doublon vérifié avant écriture (recherche locale, 2026-08-21) : aucun terme "agents-last-exam"
 * / nom contenant "Last Exam" / alias "ALE" n'existait en base ni dans les migrations du module.
 *
 * broader_slugs contient "agent-ia", qui n'existe pas encore comme terme au glossaire au moment de
 * cette migration - laissé tel quel (valeur fournie dans le contenu vérifié, non modifiée) ; le lien
 * restera simplement inactif tant que ce terme parent n'est pas créé.
 *
 * Images (agents-last-exam.jpg / .webp, 1200x669) déposées dans public/images/glossaire/ en amont de
 * cette migration - has_image=true dès l'écriture initiale (contrairement au correctif nécessaire
 * sur APEX-Agents, dont les images sont arrivées après coup).
 *
 * Données dans database/data/glossaire-batch-2026-08-21-agents-last-exam.json.
 * Anti-doublon : skip si le slug existe déjà. RÉVERSIBLE : down() supprime le terme par slug.
 */
return new class extends Migration
{
    private function terms(): array
    {
        $path = __DIR__.'/../data/glossaire-batch-2026-08-21-agents-last-exam.json';
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
