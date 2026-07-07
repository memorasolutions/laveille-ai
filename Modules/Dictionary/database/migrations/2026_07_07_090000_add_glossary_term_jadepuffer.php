<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Modules\Dictionary\Models\Category;
use Modules\Dictionary\Models\Term;

/**
 * Ajout du terme "JadePuffer" au glossaire (2026-07-07) : premier rançongiciel entièrement
 * autonome piloté par un agent LLM (Sysdig Threat Research Team, juillet 2026), via CVE-2025-3248
 * dans Langflow.
 *
 * Contenu vérifié via mcp__perplexity-pro-playwright__pp_search - sources réelles vérifiées
 * HTTP 200 avant écriture (Sysdig, BleepingComputer). Une URL The Hacker News générée par le
 * modèle de rédaction s'est révélée fausse (404 vérifié) et a été remplacée par BleepingComputer
 * (200 confirmé).
 *
 * Lié en broader_slugs à "rancongiciel" (ransomware) et "ia-agentique" (agent autonome), deux
 * termes déjà présents dans le glossaire.
 *
 * Catégorie résolue DYNAMIQUEMENT par slug (pas d'ID hardcodé). Image hero générée via /nanobanana
 * (Gemini, style isométrique 3D cohérent avec le reste du glossaire, 1200x669, webp+jpg).
 *
 * Données dans database/data/glossaire-batch-2026-07-07-jadepuffer.json.
 * Anti-doublon : skip si le slug existe déjà.
 * RÉVERSIBLE : down() supprime le terme par slug.
 */
return new class extends Migration
{
    private function terms(): array
    {
        $path = __DIR__.'/../data/glossaire-batch-2026-07-07-jadepuffer.json';
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
            // FK vers dictionary_categories, seedées uniquement en prod MySQL.
            return;
        }
        if (! class_exists(Term::class) || ! class_exists(Category::class)) {
            echo "[glossaire] modèle Term/Category absent — ignoré\n";

            return;
        }

        $fallbackCatId = $this->resolveCategoryId('concepts-fondamentaux');

        foreach ($this->terms() as $t) {
            if (Term::where('slug->fr_CA', $t['slug'])->exists()) {
                echo "[glossaire] slug déjà présent, skip : {$t['slug']}\n";

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

            echo "[glossaire] {$t['slug']} : terme créé\n";
        }

        // Lien narrower réciproque sur les termes parents existants.
        foreach (['rancongiciel', 'ia-agentique'] as $parentSlug) {
            $parent = Term::where('slug->fr_CA', $parentSlug)->first();
            if ($parent && ! in_array('jadepuffer', $parent->narrower_slugs ?? [], true)) {
                $narrower = $parent->narrower_slugs ?? [];
                $narrower[] = 'jadepuffer';
                $parent->narrower_slugs = $narrower;
                $parent->save();
                echo "[glossaire] narrower_slugs ajouté sur {$parentSlug}\n";
            }
        }
    }

    public function down(): void
    {
        if (! class_exists(Term::class)) {
            return;
        }

        foreach (['rancongiciel', 'ia-agentique'] as $parentSlug) {
            $parent = Term::where('slug->fr_CA', $parentSlug)->first();
            if ($parent && in_array('jadepuffer', $parent->narrower_slugs ?? [], true)) {
                $parent->narrower_slugs = array_values(array_diff($parent->narrower_slugs, ['jadepuffer']));
                $parent->save();
            }
        }

        Term::where('slug->fr_CA', 'jadepuffer')->delete();
    }
};
