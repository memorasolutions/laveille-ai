<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Modules\Dictionary\Models\Category;
use Modules\Dictionary\Models\Term;

/**
 * Ajout de 20 termes de glossaire pour les licences open source (2026-07-25), demandé via
 * /glossaire "de chacune des licences" (batch complet couvrant les licences listées par
 * l'utilisateur : MIT, Apache 2.0, BSD 2/3-Clause, ISC, zlib, Boost, GPL v2/v3, AGPL v3, LGPL,
 * MPL 2.0, EPL 2.0, CDDL, Artistic 2.0, Unlicense, CC0, Creative Commons, PostgreSQL, SIL OFL).
 *
 * Sources officielles (opensource.org, gnu.org, mozilla.org, eclipse.org, creativecommons.org,
 * postgresql.org, scripts.sil.org, unlicense.org, spdx.org) vérifiées joignables (curl HTTP 200),
 * sauf gnu.org/licenses/{agpl-3.0,lgpl-3.0,old-licenses/lgpl-2.1}.html : panne réseau temporaire
 * constatée pendant la vérification (même schéma d'URL que gpl-2.0/gpl-3.0.html déjà vérifiées
 * HTTP 200 quelques minutes plus tôt sur ce même domaine - URLs canoniques FSF stables depuis
 * plus d'une décennie, confiance élevée malgré l'impossibilité de re-vérifier au moment présent).
 *
 * Données réparties en 4 fichiers par famille de licence :
 * - licences-a.json (7) : permissives (MIT, Apache 2.0, BSD 2/3-Clause, ISC, zlib, Boost)
 * - licences-b.json (5) : domaine public/contenu (Unlicense, CC0, Creative Commons, PostgreSQL, SIL OFL)
 * - licences-c.json (4) : copyleft fort GNU (GPL v2, GPL v3, AGPL v3, LGPL)
 * - licences-d.json (4) : copyleft faible (MPL 2.0, EPL 2.0, CDDL, Artistic 2.0)
 *
 * Anti-doublon : skip si le slug existe déjà. RÉVERSIBLE : down() supprime les 20 termes par slug.
 */
return new class extends Migration
{
    private function files(): array
    {
        return [
            'licences-a.json',
            'licences-b.json',
            'licences-c.json',
            'licences-d.json',
        ];
    }

    private function terms(): array
    {
        $all = [];
        foreach ($this->files() as $file) {
            $path = __DIR__.'/../data/glossaire-batch-2026-07-25-'.$file;
            if (! is_file($path)) {
                continue;
            }
            $decoded = json_decode(file_get_contents($path), true) ?: [];
            $all = array_merge($all, $decoded);
        }

        return $all;
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
            $term->sort_order = 600 + $i;
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
