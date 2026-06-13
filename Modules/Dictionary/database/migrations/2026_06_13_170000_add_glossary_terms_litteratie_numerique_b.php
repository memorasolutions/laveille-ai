<?php

use Illuminate\Database\Migrations\Migration;
use Modules\Dictionary\Models\Term;

/**
 * Ajout de 4 termes fondamentaux de littératie numérique au glossaire (batch 2026-06-13 b) :
 * hameçonnage (phishing), navigateur Web (fureteur), VPN (réseau privé virtuel), algorithme.
 *
 * Complète le batch principal : ces 4 notions répondaient à des questions du quiz QT
 * dont l'ancien slug ne pointait vers aucune fiche existante. Création vérifiée LIBRE
 * (aucun doublon parmi les 415 termes publiés).
 *
 * Même standard que le batch principal (AEO + FAQPage + sources GEO 200 + aliases).
 * Données dans database/data/glossaire-batch-2026-06-13b.json.
 * Anti-doublon : skip si le slug existe déjà. RÉVERSIBLE : down() supprime par slug.
 */
return new class extends Migration
{
    private function terms(): array
    {
        $path = __DIR__.'/../data/glossaire-batch-2026-06-13b.json';
        if (! is_file($path)) {
            return [];
        }

        return json_decode(file_get_contents($path), true) ?: [];
    }

    public function up(): void
    {
        if (! class_exists(Term::class)) {
            echo "[glossaire] modèle Term absent — ignoré\n";

            return;
        }

        foreach ($this->terms() as $t) {
            if (Term::where('slug->fr_CA', $t['slug'])->exists()) {
                echo "[glossaire] slug déjà présent, skip : {$t['slug']}\n";

                continue;
            }

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
            $term->dictionary_category_id = $t['cat'];
            $term->hero_image = 'images/glossaire/'.$t['slug'].'.webp';
            $term->is_published = true;
            $term->match_strategy = 'loose';
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
