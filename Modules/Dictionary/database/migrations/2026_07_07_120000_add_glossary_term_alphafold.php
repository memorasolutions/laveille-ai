<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Modules\Dictionary\Models\Category;
use Modules\Dictionary\Models\Term;

/**
 * Ajout du terme "AlphaFold" au glossaire (2026-07-07) : famille de systemes d'IA de Google
 * DeepMind qui predit la structure 3D des proteines (CASP13 2018, percee AlphaFold2 CASP14 2020,
 * AlphaFold3 2024 pour les complexes biomoleculaires), prix Nobel de chimie 2024 (Hassabis,
 * Jumper, Baker).
 *
 * Contenu verifie via mcp__openrouter__chat_with_model (perplexity/sonar-pro) - pp_search etait
 * hors service au moment de la redaction (browser_up=false). Toutes les sources verifiees HTTP 200
 * avant ecriture (nobelprize.org, deepmind.google, ebi.ac.uk).
 *
 * broader_slugs = ["transformer"] (architecture Evoformer inspiree des Transformers, terme deja
 * existant dans le glossaire) ; ajoute reciproquement "alphafold" au narrower_slugs de
 * "transformer" si absent (graphe de connaissances bidirectionnel, meme pattern que
 * cybermenaces/jadepuffer). Reversible : down() retire ce lien puis supprime le terme.
 *
 * Image hero generee via /nanobanana (Gemini, style isometrique 3D coherent, 1200x669, webp+jpg).
 *
 * Donnees dans database/data/glossaire-batch-2026-07-07-alphafold.json.
 * Anti-doublon : skip si le slug existe deja.
 */
return new class extends Migration
{
    private function terms(): array
    {
        $path = __DIR__.'/../data/glossaire-batch-2026-07-07-alphafold.json';
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
            echo "[glossaire] modele Term/Category absent - ignore\n";

            return;
        }

        $fallbackCatId = $this->resolveCategoryId('concepts-fondamentaux');

        foreach ($this->terms() as $t) {
            if (Term::where('slug->fr_CA', $t['slug'])->exists()) {
                echo "[glossaire] slug deja present, skip : {$t['slug']}\n";

                continue;
            }

            $catId = $this->resolveCategoryId($t['cat_slug']) ?? $fallbackCatId;

            Term::create([
                'name' => ['fr_CA' => $t['name'], 'fr' => $t['name']],
                'slug' => ['fr_CA' => $t['slug'], 'fr' => $t['slug']],
                'definition' => ['fr_CA' => $t['definition'], 'fr' => $t['definition']],
                'analogy' => ['fr_CA' => $t['analogy'], 'fr' => $t['analogy']],
                'example' => ['fr_CA' => $t['example'], 'fr' => $t['example']],
                'did_you_know' => ['fr_CA' => $t['did_you_know'], 'fr' => $t['did_you_know']],
                'one_sentence_answer' => ['fr_CA' => $t['one_sentence_answer'], 'fr' => $t['one_sentence_answer']],
                'faq' => $t['faq'],
                'sources' => $t['sources'],
                'aliases' => $t['aliases'],
                'broader_slugs' => $t['broader_slugs'],
                'narrower_slugs' => $t['narrower_slugs'],
                'icon' => $t['icon'],
                'type' => $t['type'],
                'difficulty' => $t['difficulty'],
                'match_strategy' => $t['match_strategy'],
                'hero_image' => $t['has_image'] ? 'images/glossaire/'.$t['slug'].'.webp' : null,
                'category_id' => $catId,
            ]);

            echo "[glossaire] {$t['slug']} : terme cree\n";

            foreach ($t['broader_slugs'] as $parentSlug) {
                $parent = Term::where('slug->fr_CA', $parentSlug)->first();
                if (! $parent) {
                    continue;
                }
                $narrower = $parent->narrower_slugs ?? [];
                if (! in_array($t['slug'], $narrower, true)) {
                    $narrower[] = $t['slug'];
                    $parent->narrower_slugs = $narrower;
                    $parent->save();
                    echo "[glossaire] lien reciproque ajoute : {$parentSlug} -> narrower_slugs += {$t['slug']}\n";
                }
            }
        }
    }

    public function down(): void
    {
        if (! class_exists(Term::class)) {
            return;
        }

        foreach ($this->terms() as $t) {
            foreach ($t['broader_slugs'] as $parentSlug) {
                $parent = Term::where('slug->fr_CA', $parentSlug)->first();
                if (! $parent) {
                    continue;
                }
                $narrower = $parent->narrower_slugs ?? [];
                $narrower = array_values(array_filter($narrower, fn ($s) => $s !== $t['slug']));
                $parent->narrower_slugs = $narrower;
                $parent->save();
            }
        }

        Term::where('slug->fr_CA', 'alphafold')->delete();
    }
};
