<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Modules\Dictionary\Models\Category;
use Modules\Dictionary\Models\Term;

/**
 * Ajout du terme umbrella "Cybermenaces" au glossaire (2026-07-07) : terme fondamental qui
 * chapeaute la taxonomie des menaces déjà présentes dans le glossaire (ENISA Threat Landscape,
 * ANSSI Panorama de la cybermenace, CISA).
 *
 * Contenu vérifié via mcp__openrouter__chat_with_model (perplexity/sonar-pro) - pp_search était
 * hors service au moment de la rédaction (browser_up=false, session Playwright fermée). Sources
 * vérifiées HTTP 200 avant écriture (ENISA, ANSSI/CERT-FR, CISA - pages d'accueil officielles,
 * pas d'article précis inventé).
 *
 * Construit le graphe de connaissances : "cybermenaces" devient le parent (broader) de 15 termes
 * déjà présents (malware, rancongiciel, virus-informatique, ver-informatique, cheval-de-troie,
 * deni-de-service, hameconnage, pamstealer, jadepuffer, attaque-adversariale, data-poisoning,
 * model-extraction, prompt-injection, jailbreak, zero-day), et un enfant (narrower) de
 * "cybersecurite". Mise à jour BIDIRECTIONNELLE : chacun des 15 termes reçoit "cybermenaces" dans
 * son propre broader_slugs (sans écraser les valeurs existantes).
 *
 * Image hero générée via /nanobanana (Gemini, style isométrique 3D cohérent, 1200x669, webp+jpg).
 *
 * Données dans database/data/glossaire-batch-2026-07-07-cybermenaces.json.
 * Anti-doublon : skip si le slug existe déjà.
 * RÉVERSIBLE : down() retire "cybermenaces" des broader_slugs des 15 termes enfants puis supprime
 * le terme.
 */
return new class extends Migration
{
    private array $childSlugs = [
        'malware', 'rancongiciel', 'virus-informatique', 'ver-informatique', 'cheval-de-troie',
        'deni-de-service', 'hameconnage', 'pamstealer', 'jadepuffer', 'attaque-adversariale',
        'data-poisoning', 'model-extraction', 'prompt-injection', 'jailbreak', 'zero-day',
    ];

    private function terms(): array
    {
        $path = __DIR__.'/../data/glossaire-batch-2026-07-07-cybermenaces.json';
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

        // Lien broader réciproque sur les 15 termes enfants existants.
        foreach ($this->childSlugs as $childSlug) {
            $child = Term::where('slug->fr_CA', $childSlug)->first();
            if ($child && ! in_array('cybermenaces', $child->broader_slugs ?? [], true)) {
                $broader = $child->broader_slugs ?? [];
                $broader[] = 'cybermenaces';
                $child->broader_slugs = $broader;
                $child->save();
                echo "[glossaire] broader_slugs 'cybermenaces' ajouté sur {$childSlug}\n";
            }
        }

        // Lien narrower réciproque sur "cybersecurite" (parent).
        $parent = Term::where('slug->fr_CA', 'cybersecurite')->first();
        if ($parent && ! in_array('cybermenaces', $parent->narrower_slugs ?? [], true)) {
            $narrower = $parent->narrower_slugs ?? [];
            $narrower[] = 'cybermenaces';
            $parent->narrower_slugs = $narrower;
            $parent->save();
            echo "[glossaire] narrower_slugs 'cybermenaces' ajouté sur cybersecurite\n";
        }
    }

    public function down(): void
    {
        if (! class_exists(Term::class)) {
            return;
        }

        foreach ($this->childSlugs as $childSlug) {
            $child = Term::where('slug->fr_CA', $childSlug)->first();
            if ($child && in_array('cybermenaces', $child->broader_slugs ?? [], true)) {
                $child->broader_slugs = array_values(array_diff($child->broader_slugs, ['cybermenaces']));
                $child->save();
            }
        }

        $parent = Term::where('slug->fr_CA', 'cybersecurite')->first();
        if ($parent && in_array('cybermenaces', $parent->narrower_slugs ?? [], true)) {
            $parent->narrower_slugs = array_values(array_diff($parent->narrower_slugs, ['cybermenaces']));
            $parent->save();
        }

        Term::where('slug->fr_CA', 'cybermenaces')->delete();
    }
};
