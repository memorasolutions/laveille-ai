<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Modules\Dictionary\Models\Category;
use Modules\Dictionary\Models\Term;

/**
 * Ajout du terme "PinPoint Test" au glossaire (2026-07-09) : test sanguin de triage du cancer
 * base sur l'intelligence artificielle (Pinpoint Data Science, Royaume-Uni), evalue par le NHS.
 * Modele ML valide sur 371 799 recommandations urgentes retrospectives a Leeds (BMJ Open 2022,
 * Savage et al.), puis evaluation prospective NHS sur ~17 000 patients du West Yorkshire (5 ans).
 *
 * Contenu verifie via mcp__perplexity-pro-playwright__pp_search (2 passages) - sources BMJ Open
 * (pubmed.ncbi.nlm.nih.gov/35365520), pinpointdatascience.com, artificialintelligence-news.com.
 *
 * Aucun broader_slugs/narrower_slugs : pas de terme parent/enfant verifie existant dans le
 * glossaire au moment de la redaction (pas de lien invente, cf. standard skill /glossaire).
 *
 * Image hero generee via /nanobanana (Gemini, style illustratif coherent charte teal/orange,
 * 1200x669, webp+jpg).
 *
 * Donnees dans database/data/glossaire-batch-2026-07-09-pinpoint-test.json.
 * Anti-doublon : skip si le slug existe deja.
 */
return new class extends Migration
{
    private function terms(): array
    {
        $path = __DIR__.'/../data/glossaire-batch-2026-07-09-pinpoint-test.json';
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
                'dictionary_category_id' => $catId,
            ]);

            echo "[glossaire] {$t['slug']} : terme cree\n";
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
