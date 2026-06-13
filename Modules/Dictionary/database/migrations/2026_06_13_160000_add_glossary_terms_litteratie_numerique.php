<?php

use Illuminate\Database\Migrations\Migration;
use Modules\Dictionary\Models\Term;

/**
 * Ajout de 17 termes de littératie numérique au glossaire (batch 2026-06-13) :
 * HTTPS, témoin (cookie), 5G, DOM, loi de Moore, confiance zéro, web sémantique,
 * informatique en périphérie, entropie, pourriel, cheval de Troie, JPEG,
 * mot de passe fort, minimisation des données, déni de service (DoS),
 * rançongiciel, mot de passe maître.
 *
 * Ces fiches répondent aux 17 questions du quiz « QT — Quotient Techno » qui
 * n'avaient pas encore de fiche (notions web/sécurité générales). Chaque question
 * y sera reliée → maillage « rester et apprendre ».
 *
 * Conforme au standard glossaire : champs AEO (one_sentence_answer ≤ 40 mots),
 * FAQPage (faq {question,answer}), sources GEO ({label,url} vérifiées 200),
 * image hero {slug}.webp + og:image {slug}.jpg, maillage broader/narrower + aliases.
 * Contenu rédigé via délégation MCP (deepseek-v4-flash via Hermes) + faits ancrés,
 * affiné par le superviseur. Données dans database/data/glossaire-batch-2026-06-13.json.
 * Anti-doublon : skip si le slug existe déjà. RÉVERSIBLE : down() supprime par slug.
 */
return new class extends Migration
{
    private function terms(): array
    {
        $path = __DIR__.'/../data/glossaire-batch-2026-06-13.json';
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
