<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Modules\Dictionary\Models\Category;
use Modules\Dictionary\Models\Term;

/**
 * Ajout d'un terme au glossaire (demande utilisateur, skill /glossaire, 2026-07-06) :
 * Noyb (ONG autrichienne fondée par Max Schrems, à l'origine des arrêts Schrems I/II et
 * critique active du Data Privacy Framework en 2026).
 *
 * Contenu vérifié via mcp__perplexity-pro-playwright__pp_search (2 requêtes : histoire/mission
 * de l'organisation, puis confirmation fondation 2017/opérationnelle 2018). Rédaction déléguée
 * à mcp__hermes__model_invoke (task_type=synthesis), relue et corrigée avant écriture : tiret
 * cadratin retiré de la définition (interdit par la charte éditoriale du projet), définition
 * raccourcie de 182 à 160 mots pour respecter le standard 150-165 mots. URL Wikipedia FR
 * proposée par la recherche vérifiée manquante (404 réel testé) et remplacée par le site
 * officiel noyb.eu (200 confirmé) - anti-hallucination.
 *
 * Sans image (has_image=false) : organisation/entité juridique abstraite (même logique
 * éditoriale que le terme "Data Privacy Framework" du 2026-07-06 - un visuel n'apporterait
 * rien de plus qu'un pictogramme générique).
 *
 * Lien broader vers « rgpd » ET « data-privacy-framework » (Noyb est l'organisation à
 * l'origine de la jurisprudence RGPD sur les transferts transatlantiques et l'un des
 * principaux contestataires du DPF) ; complète aussi les liens narrower inverses sur ces
 * deux fiches existantes pour le graphe de connaissances bidirectionnel (étape 2 de cette
 * migration, motif repris de la migration 2026_07_06_090000).
 *
 * Anti-doublon : vérifié absent en prod (sitemap.xml consulté avant rédaction, aucun slug
 * noyb/schrems existant). Données dans database/data/glossaire-batch-2026-07-06-noyb.json.
 * RÉVERSIBLE : down() supprime le terme par slug et retire 'noyb' du narrower_slugs de rgpd
 * et de data-privacy-framework (seulement s'il l'avait ajouté, jamais aveuglément).
 */
return new class extends Migration
{
    private function terms(): array
    {
        $path = __DIR__.'/../data/glossaire-batch-2026-07-06-noyb.json';
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
        if (! in_array('noyb', $narrower, true)) {
            $narrower[] = 'noyb';
            $parent->narrower_slugs = $narrower;
            $parent->save();
            echo "[glossaire] {$parentSlug} : narrower_slugs += noyb\n";
        }
    }

    private function removeNarrowerLink(string $parentSlug): void
    {
        $parent = Term::where('slug->fr_CA', $parentSlug)->first();
        if (! $parent) {
            return;
        }

        $narrower = is_array($parent->narrower_slugs) ? $parent->narrower_slugs : [];
        $filtered = array_values(array_filter($narrower, fn ($s) => $s !== 'noyb'));
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
            $term->sort_order = 312 + $i;
            $term->save();

            echo "[glossaire] inséré : {$t['slug']} (id={$term->id})\n";
        }

        // Étape 2 : compléter les liens narrower manquants (rgpd, data-privacy-framework).
        $this->addNarrowerLink('rgpd');
        $this->addNarrowerLink('data-privacy-framework');
    }

    public function down(): void
    {
        if (! class_exists(Term::class)) {
            return;
        }

        foreach ($this->terms() as $t) {
            Term::where('slug->fr_CA', $t['slug'])->delete();
        }

        $this->removeNarrowerLink('rgpd');
        $this->removeNarrowerLink('data-privacy-framework');
    }
};
