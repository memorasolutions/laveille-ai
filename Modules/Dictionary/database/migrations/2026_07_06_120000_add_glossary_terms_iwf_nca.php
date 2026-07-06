<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Modules\Dictionary\Models\Category;
use Modules\Dictionary\Models\Term;

/**
 * Ajout de 2 termes au glossaire (demande utilisateur, skill /glossaire, 2026-07-06) :
 * IWF (Internet Watch Foundation) et NCA (National Crime Agency), organismes britanniques
 * de lutte contre le matériel d'abus sexuel sur enfants en ligne (CSAM), mentionnés dans le
 * contexte de la hausse des contenus générés par IA en 2025 (rapport IWF : 8029 images, +14%).
 *
 * SUJET SENSIBLE : contenu rédigé en angle strictement factuel/institutionnel/prévention
 * (mission des organismes, statistiques de haut niveau, recommandations de confidentialité
 * aux familles) - AUCUN détail explicite ou graphique. Rédaction déléguée à
 * mcp__hermes__model_invoke (task_type=synthesis) avec consigne explicite de sensibilité,
 * relue et corrigée avant écriture (définitions raccourcies pour respecter le standard
 * 150-165 mots, aliases auto-référentiels retirés, icône NCA changée de 🛡️ à 🚔 pour éviter
 * la collision visuelle avec le terme "noyb" déjà présent).
 *
 * Sans image (has_image=false) pour les deux termes : sujet sensible (protection de
 * l'enfance/abus), aucune imagerie générée ne serait appropriée ni nécessaire - organismes
 * institutionnels sans identité visuelle grand public à illustrer.
 *
 * Pas de lien broader/narrower : IWF et NCA sont des organisations pairs (pas de relation
 * hiérarchique taxonomique), et aucun terme "RGPD"/"vie privée" existant ne justifie un lien
 * direct sans le forcer.
 *
 * Anti-doublon : vérifié absent en prod (sitemap.xml consulté avant rédaction, aucun slug
 * iwf/nca/csam/internet-watch/national-crime existant). Sources vérifiées une par une (curl,
 * 200 confirmé sur les 4 URLs) avant écriture - anti-hallucination. Données dans
 * database/data/glossaire-batch-2026-07-06-iwf-nca.json.
 * RÉVERSIBLE : down() supprime les 2 termes par slug.
 */
return new class extends Migration
{
    private function terms(): array
    {
        $path = __DIR__.'/../data/glossaire-batch-2026-07-06-iwf-nca.json';
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
            $term->sort_order = 313 + $i;
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
