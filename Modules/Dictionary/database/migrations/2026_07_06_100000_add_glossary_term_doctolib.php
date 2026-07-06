<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Modules\Dictionary\Models\Category;
use Modules\Dictionary\Models\Term;

/**
 * Ajout d'un terme au glossaire (demande utilisateur, skill /glossaire, 2026-07-06) :
 * Doctolib (plateforme française de santé en ligne : prise de rendez-vous médicaux,
 * téléconsultation, logiciel de gestion de cabinet).
 *
 * Contenu vérifié via mcp__perplexity-pro-playwright__pp_search (faits Wikipedia FR +
 * amende novembre 2025 pour abus de position dominante confirmée par Le Figaro). Rédaction
 * déléguée à mcp__hermes__model_invoke (task_type=synthesis, 2 passes : brouillon puis
 * lissage de la définition trop "liste chronologique" + correction du mélange d'années
 * employés 2024/rentabilité 2025), relue et corrigée avant écriture (aliases auto-référentiels
 * retirés, exemple sans statistique de temps d'attente non vérifiée).
 *
 * Avec image (has_image=true) : plateforme/marque grand public bien identifiable,
 * contrairement au terme abstrait précédent (Data Privacy Framework) - bon candidat visuel.
 * Illustration générique (univers santé numérique) générée via /nanobanana, pas de logo réel.
 *
 * Pas de lien broader/narrower : aucun terme santé/télémédecine existant dans le glossaire
 * (vérifié absent en prod via sitemap.xml avant rédaction).
 *
 * Anti-doublon : vérifié absent en prod (sitemap.xml consulté avant rédaction, aucun slug
 * doctolib/sante/telemedecine existant). Données dans
 * database/data/glossaire-batch-2026-07-06-doctolib.json.
 * RÉVERSIBLE : down() supprime le terme par slug.
 */
return new class extends Migration
{
    private function terms(): array
    {
        $path = __DIR__.'/../data/glossaire-batch-2026-07-06-doctolib.json';
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
            $term->sort_order = 311 + $i;
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
