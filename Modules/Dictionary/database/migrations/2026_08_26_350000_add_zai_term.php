<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Modules\Dictionary\Models\Category;
use Modules\Dictionary\Models\Term;

/**
 * Ajout du terme "Z.ai" au glossaire (2026-08-26) - l'ENTREPRISE, pas le modèle.
 *
 * Anti-doublon vérifié AVANT rédaction (relevé complet du sitemap de production, 506 slugs) :
 * aucune correspondance pour les motifs "zhipu", "glm", "z-ai", "zai", "chatglm", "zh*" - le
 * glossaire ne contient encore aucune fiche sur cette entreprise ni sur ses modèles, malgré les
 * quatre actualités déjà publiées sur GLM-5.2. Décision : fiche nouvelle sur l'ENTREPRISE
 * uniquement (le modèle GLM reste une notion distincte, cf. skill /glossaire section
 * "PostgreSQL / licence PostgreSQL") - aucune fiche GLM n'existe donc aucune fusion à faire, et
 * narrower_slugs reste vide (à remplir le jour où une fiche GLM sera créée). broader_slugs reste
 * vide aussi : aucun terme parent pertinent n'existe (pas de "GAFAM chinois"), à l'image du
 * traitement déjà réservé aux fiches "xai" et "google" (entreprises) par opposition aux fiches
 * de MODÈLE ("claude-anthropic", "grok-xai", "qwen-alibaba", "deepseek", "gemini-google") qui
 * pointent, elles, broader_slugs=["llm"] - une entreprise n'est pas une instance de LLM.
 *
 * Alias : "Zhipu AI" SEUL (ancien nom international jusqu'en juillet 2025, légitime car même
 * entité). "GLM" n'est PAS un alias : un nom de modèle n'est pas un synonyme de son fabricant
 * (même défaut que celui corrigé le 2026-08-23 sur "Google" qui renvoyait vers "Gemini").
 *
 * Recherche : session sans mcp__perplexity-pro-playwright__pp_search (non enregistré dans cette
 * session sous-agent) - repli documenté CLAUDE.md sur mcp__openrouter__chat_with_model modèle
 * perplexity/sonar-pro (3 appels). Validation croisée : Wikipédia EN "Z.ai" (bien sourcée, lue en
 * entier) + Hugging Face (org "zai-org", licence MIT confirmée sur zai-org/GLM-4.6) + vérification
 * HTTP individuelle de chaque URL de sources (200 confirmé + horodatage Wayback Machine confirmé
 * pour CNBC ×2 et SCMP ; Federal Register vérifié via son API officielle). Les URLs Reuters
 * générées par sonar-pro pour l'IPO et le rebranding se sont révélées fabriquées (plausibles mais
 * absentes de Wayback) et ont été ÉCARTÉES au profit des URLs réelles retrouvées dans les
 * citations sourcées de Wikipédia (CNBC, SCMP) - un rappel du principe "jamais une URL plausible
 * reconstruite". Chaque fait clé (Entity List, IPO, licence MIT, rebranding) repose sur au moins
 * deux sources indépendantes et vérifiées individuellement.
 *
 * Angle retenu : ÉCOSYSTÈME ET POSITIONNEMENT (92/100) - Z.ai comme principal éditeur chinois à
 * publier ses modèles GLM en poids ouverts (licence MIT depuis juillet 2025), face aux modèles
 * fermés d'OpenAI/Anthropic. Écarté : angle produit pur (65/100, redondant avec les 4 actualités
 * GLM-5.2 déjà publiées, périmé au prochain modèle) ; angle gouvernance pur (70/100, trop niche
 * pour une réponse citable généraliste). L'angle retenu absorbe les faits de gouvernance (Entity
 * List, IPO) sans en faire le coeur du texte.
 *
 * Typographie OQLF appliquée (espace insécable U+00A0 réelle autour de « » ; aucune espace avant
 * ;!?) - contrôle grep -nP '(?<! ) :|\s+[;!?]' passé (aucune correspondance).
 *
 * Image : public/images/glossaire/zai.{webp,jpg} déposée AVANT cette migration (1200x669,
 * compressée) - render 3D isométrique libre de droit (collection "Visualising AI" de Google
 * DeepMind sur Pexels, via mcp__stock-photos__search_photos ; /nanobanana hors d'atteinte dans
 * cette session, outils browser_* absents). Aucun logo réel, aucune personne identifiable.
 *
 * Données dans database/data/glossaire-batch-2026-08-26-zai.json.
 * Anti-doublon : skip si le slug existe déjà. RÉVERSIBLE : down() supprime uniquement ce terme.
 */
return new class extends Migration
{
    private function terms(): array
    {
        $path = __DIR__.'/../data/glossaire-batch-2026-08-26-zai.json';
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

        $fallbackCatId = $this->resolveCategoryId('intelligence-artificielle');
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
            $term->reference_url = $t['reference_url'] ?? null;
            $term->reference_label = $t['reference_label'] ?? null;
            $term->is_published = true;
            $term->match_strategy = $t['match_strategy'] ?? 'loose';
            $term->sort_order = 900 + $i;
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
