<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Modules\Dictionary\Models\Category;
use Modules\Dictionary\Models\Term;

/**
 * Ajout d'un terme au glossaire (demande utilisateur, skill /glossaire, 2026-07-06) :
 * OmniDocBench (benchmark CVPR 2025 d'analyse/extraction de documents, Shanghai AI Laboratory).
 *
 * Contenu verifie via mcp__perplexity-pro-playwright__pp_search (2 requetes : etat du
 * benchmark v1.5 mi-2026, metriques Text Edit/TEDS/CDM/Read Order et classement 2026).
 * Redaction deleguee a mcp__hermes__model_invoke (task_type=synthesis, 2 passes : brouillon
 * initial a 287 mots - beaucoup trop detaille avec la formule mathematique complete du score
 * et les 9 categories/15 sous-categories listees en entier - puis resserrement cible a 158
 * mots pour respecter le standard 150-165 mots).
 *
 * Sans image (has_image=false) : benchmark/jeu de donnees d'evaluation technique abstrait,
 * pas de produit/interface grand public a illustrer - meme logique editoriale que Data
 * Privacy Framework, Noyb et Kernels.
 *
 * Liens broader vers « ocr » ET « benchmark-ia » (deja presents dans le glossaire -
 * OmniDocBench est un benchmark specialise dans l'evaluation de systemes OCR/extraction de
 * documents) ; complete aussi les liens narrower inverses sur ces deux fiches existantes pour
 * le graphe de connaissances bidirectionnel (motif repris des migrations 2026_07_06
 * precedentes).
 *
 * Anti-doublon : verifie absent en prod (sitemap.xml consulte avant redaction, aucun slug
 * omnidocbench existant ; ocr et benchmark-ia existent deja et sont reutilises comme parents
 * plutot que redondes). Sources verifiees une par une (curl, 200 confirme sur les 2 URLs).
 * Donnees dans database/data/glossaire-batch-2026-07-06-omnidocbench.json.
 * REVERSIBLE : down() supprime le terme par slug et retire 'omnidocbench' du narrower_slugs
 * de ocr et de benchmark-ia (seulement s'il l'avait ajoute, jamais aveuglement).
 */
return new class extends Migration
{
    private function terms(): array
    {
        $path = __DIR__.'/../data/glossaire-batch-2026-07-06-omnidocbench.json';
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

    private function addNarrowerLink(string $parentSlug): void
    {
        $parent = Term::where('slug->fr_CA', $parentSlug)->first();
        if (! $parent) {
            return;
        }

        $narrower = is_array($parent->narrower_slugs) ? $parent->narrower_slugs : [];
        if (! in_array('omnidocbench', $narrower, true)) {
            $narrower[] = 'omnidocbench';
            $parent->narrower_slugs = $narrower;
            $parent->save();
            echo "[glossaire] {$parentSlug} : narrower_slugs += omnidocbench\n";
        }
    }

    private function removeNarrowerLink(string $parentSlug): void
    {
        $parent = Term::where('slug->fr_CA', $parentSlug)->first();
        if (! $parent) {
            return;
        }

        $narrower = is_array($parent->narrower_slugs) ? $parent->narrower_slugs : [];
        $filtered = array_values(array_filter($narrower, fn ($s) => $s !== 'omnidocbench'));
        if ($filtered !== $narrower) {
            $parent->narrower_slugs = $filtered;
            $parent->save();
        }
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
            $term->sort_order = 316 + $i;
            $term->save();

            echo "[glossaire] inséré : {$t['slug']} (id={$term->id})\n";
        }

        // Étape 2 : compléter les liens narrower manquants (ocr, benchmark-ia).
        $this->addNarrowerLink('ocr');
        $this->addNarrowerLink('benchmark-ia');
    }

    public function down(): void
    {
        if (! class_exists(Term::class)) {
            return;
        }

        foreach ($this->terms() as $t) {
            Term::where('slug->fr_CA', $t['slug'])->delete();
        }

        $this->removeNarrowerLink('ocr');
        $this->removeNarrowerLink('benchmark-ia');
    }
};
