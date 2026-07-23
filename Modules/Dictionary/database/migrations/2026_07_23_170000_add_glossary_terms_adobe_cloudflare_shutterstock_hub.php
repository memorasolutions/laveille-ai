<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Modules\Dictionary\Models\Category;
use Modules\Dictionary\Models\Term;

/**
 * Ajout de 4 termes au glossaire (batch 2026-07-23, demandé explicitement par l'utilisateur) :
 * Adobe, Cloudflare, Shutterstock, Hub.
 *
 * Contenu vérifié via mcp__perplexity-pro-playwright__pp_search (Hub, Shutterstock - session
 * active) et mcp__openrouter__chat_with_model perplexity/sonar-pro + mcp__superagent__codex en
 * secours (Adobe, Cloudflare - session Perplexity expirée dans cette portion de session). Chaque
 * URL de `sources` vérifiée réellement joignable (curl HEAD, HTTP 200) avant écriture - aucune
 * URL "estimée"/devinée (standard durci par le skill /glossaire, section 0, 2026-07-23).
 *
 * Angle éditorial pour les 4 : rôle dans l'écosystème IA (pas une fiche corporative générique) -
 * Adobe (Firefly/Sensei/Content Credentials), Cloudflare (CDN mondial + blocage robots IA par
 * défaut depuis juillet 2025), Shutterstock (licence de contenu pour entraînement IA, générateur
 * maison sous licence), Hub (sens Hugging Face Hub retenu plutôt que le hub réseau matériel
 * obsolète, avec phrase de désambiguïsation dans la définition).
 *
 * Catégorie résolue DYNAMIQUEMENT par slug (intelligence-artificielle pour les 4, fallback
 * concepts-fondamentaux si introuvable) - pas d'ID hardcodé.
 *
 * Images hero (4/4) générées via le compte Gemini de l'utilisateur (skill /nanobanana,
 * Playwright), style isométrique 3D teal/cyan cohérent avec le reste du glossaire (1200x669,
 * webp + jpg de secours pour og:image réseaux sociaux).
 *
 * Données dans database/data/glossaire-batch-2026-07-23-adobe-cloudflare-shutterstock-hub.json.
 * Anti-doublon : skip si le slug existe déjà. RÉVERSIBLE : down() supprime les 4 termes par slug.
 */
return new class extends Migration
{
    private function terms(): array
    {
        $path = __DIR__.'/../data/glossaire-batch-2026-07-23-adobe-cloudflare-shutterstock-hub.json';
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
            $term->sort_order = 400 + $i;
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
