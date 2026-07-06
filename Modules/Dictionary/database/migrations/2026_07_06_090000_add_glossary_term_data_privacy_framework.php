<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Modules\Dictionary\Models\Category;
use Modules\Dictionary\Models\Term;

/**
 * Ajout d'un terme au glossaire (demande utilisateur, skill /glossaire, 2026-07-06) :
 * Data Privacy Framework (cadre de transfert transatlantique de données UE-É.-U., 3e mécanisme
 * après Safe Harbor et Privacy Shield, tous deux annulés).
 *
 * Contenu vérifié via mcp__perplexity-pro-playwright__pp_search (statut légal mi-2026, dont le
 * fait très récent Trump v. Slaughter/juillet 2026 sur l'indépendance de la FTC). Rédaction des
 * champs déléguée à mcp__hermes__model_invoke (task_type=synthesis), relue et corrigée avant
 * écriture (définition réduite de 232 à 176 mots pour respecter le standard 150-165 mots ;
 * une URL source hallucinée par le modèle remplacée par l'URL réelle observée en recherche).
 *
 * Sans image (has_image=false) : terme juridique/abstrait, décision éditoriale du skill
 * /glossaire (un visuel n'apporterait rien de plus qu'un pictogramme générique).
 *
 * Lien broader vers « rgpd » (le DPF est une décision d'adéquacité au titre de l'article 45 du
 * RGPD) ; complète aussi le lien narrower inverse sur la fiche « rgpd » existante pour le graphe
 * de connaissances bidirectionnel (étape 2 de cette migration, motif repris de la migration
 * 2026_07_05_100000).
 *
 * Anti-doublon : vérifié absent en prod (sitemap.xml consulté avant rédaction, aucun slug
 * data-privacy-framework/dpf/privacy-shield existant). Données dans
 * database/data/glossaire-batch-2026-07-06-data-privacy-framework.json.
 * RÉVERSIBLE : down() supprime le terme par slug et retire 'data-privacy-framework' du
 * narrower_slugs de rgpd (seulement s'il l'avait ajouté, jamais aveuglément).
 */
return new class extends Migration
{
    private function terms(): array
    {
        $path = __DIR__.'/../data/glossaire-batch-2026-07-06-data-privacy-framework.json';
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
            // Cette migration insère des données avec des FK vers dictionary_categories
            // qui n'existent que sur MySQL (seedées en prod). SQLite en tests = skip.
            return;
        }
        if (! class_exists(Term::class) || ! class_exists(Category::class)) {
            echo "[glossaire] modèle Term/Category absent — ignoré\n";

            return;
        }

        $fallbackCatId = $this->resolveCategoryId('concepts-fondamentaux');
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
            $term->sort_order = 310 + $i;
            $term->save();

            echo "[glossaire] inséré : {$t['slug']} (id={$term->id})\n";
        }

        // Étape 2 : compléter le lien narrower manquant de « RGPD » (existant, non créé ici).
        $rgpd = Term::where('slug->fr_CA', 'rgpd')->first();
        if ($rgpd) {
            $narrower = is_array($rgpd->narrower_slugs) ? $rgpd->narrower_slugs : [];
            if (! in_array('data-privacy-framework', $narrower, true)) {
                $narrower[] = 'data-privacy-framework';
                $rgpd->narrower_slugs = $narrower;
                $rgpd->save();
                echo "[glossaire] rgpd : narrower_slugs += data-privacy-framework\n";
            }
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

        // Retire le lien narrower ajouté sur rgpd (seulement 'data-privacy-framework', rien d'autre touché).
        $rgpd = Term::where('slug->fr_CA', 'rgpd')->first();
        if ($rgpd) {
            $narrower = is_array($rgpd->narrower_slugs) ? $rgpd->narrower_slugs : [];
            $filtered = array_values(array_filter($narrower, fn ($s) => $s !== 'data-privacy-framework'));
            if ($filtered !== $narrower) {
                $rgpd->narrower_slugs = $filtered;
                $rgpd->save();
            }
        }
    }
};
