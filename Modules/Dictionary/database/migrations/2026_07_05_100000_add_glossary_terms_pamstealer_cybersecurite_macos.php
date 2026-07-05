<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Modules\Dictionary\Models\Category;
use Modules\Dictionary\Models\Term;

/**
 * Ajout de 9 termes au glossaire (batch cybersécurité macOS 2026-07-05) :
 * PamStealer, macOS, Jamf, Interface PAM, AppleScript, Malware, Ver informatique,
 * Virus (informatique), JavaScript.
 *
 * « Cheval de Troie » existe déjà (vérifié en prod avant rédaction) : exclu de ce batch,
 * mais son lien broader vers « malware » est complété ici (étape 2 de cette migration),
 * car sa fiche existante n'avait aucun broader_slugs renseigné.
 *
 * Contenu vérifié via mcp__perplexity-pro-playwright__pp_search (PamStealer, Jamf) et
 * openrouter/perplexity-sonar-pro en secours (AppleScript, PAM, historique malware/virus/ver) —
 * sources réelles vérifiées HTTP 200 avant écriture (TheHackerNews, AppleWorld Today, Jamf,
 * Wikipédia FR/EN, MDN, Linux-PAM, Apple Developer).
 *
 * Catégorie résolue DYNAMIQUEMENT par slug (pas d'ID hardcodé, la table categories est
 * absente en local) : sécurité-et-éthique pour les termes de menace/malware, outils-et-
 * techniques pour macOS/Jamf/PAM/AppleScript/JavaScript. Fallback concepts-fondamentaux
 * si une catégorie est introuvable.
 *
 * Images hero (6/9 : PamStealer, macOS, Jamf, Malware, Ver informatique, Virus informatique)
 * générées via le compte Gemini de l'utilisateur (skill /nanobanana, Playwright), format
 * isométrique 3D steel-blue/cyan cohérent avec le reste du glossaire (1200x669, webp <150 Ko
 * + jpg de secours pour og:image réseaux sociaux, déjà présents dans public/images/glossaire/).
 * AppleScript, Interface PAM et JavaScript restent sans image (emoji suffisant, jugement éditorial).
 *
 * Données dans database/data/glossaire-batch-2026-07-05-cybersecurite-macos.json.
 * Anti-doublon : skip si le slug existe déjà (vérifié absent en prod par curl avant écriture).
 * RÉVERSIBLE : down() supprime les 9 termes par slug et retire 'malware' du broader_slugs
 * de cheval-de-troie (seulement s'il l'avait ajouté, jamais aveuglément).
 */
return new class extends Migration
{
    private function terms(): array
    {
        $path = __DIR__.'/../data/glossaire-batch-2026-07-05-cybersecurite-macos.json';
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
            $term->sort_order = 300 + $i;
            $term->save();

            echo "[glossaire] inséré : {$t['slug']} (id={$term->id})\n";
        }

        // Étape 2 : compléter le lien broader manquant de « Cheval de Troie » (existant, non créé ici).
        $trojan = Term::where('slug->fr_CA', 'cheval-de-troie')->first();
        if ($trojan) {
            $broader = is_array($trojan->broader_slugs) ? $trojan->broader_slugs : [];
            if (! in_array('malware', $broader, true)) {
                $broader[] = 'malware';
                $trojan->broader_slugs = $broader;
                $trojan->save();
                echo "[glossaire] cheval-de-troie : broader_slugs += malware\n";
            }
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

        // Retire le lien broader ajouté sur cheval-de-troie (seulement 'malware', rien d'autre touché).
        $trojan = Term::where('slug->fr_CA', 'cheval-de-troie')->first();
        if ($trojan) {
            $broader = is_array($trojan->broader_slugs) ? $trojan->broader_slugs : [];
            $filtered = array_values(array_filter($broader, fn ($s) => $s !== 'malware'));
            if ($filtered !== $broader) {
                $trojan->broader_slugs = $filtered;
                $trojan->save();
            }
        }
    }
};
