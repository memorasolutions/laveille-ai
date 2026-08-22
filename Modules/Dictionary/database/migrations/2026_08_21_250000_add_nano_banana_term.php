<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Modules\Dictionary\Models\Category;
use Modules\Dictionary\Models\Term;

/**
 * Ajout du terme "Nano Banana" au glossaire (2026-08-21).
 *
 * Contenu vérifié par validation croisée avant écriture (Perplexity puis Codex, chaque fait
 * confirmé séparément sur les pages officielles de Google) - les quatre correspondances
 * nom public / nom technique, l'origine du nom, le filigrane SynthID et l'avertissement de
 * Google sur la fiabilité proviennent tous de blog.google et deepmind.google, jamais d'une
 * source secondaire. Les 4 URLs de sources ont été appelées une par une (HTTP 200) avant
 * d'être inscrites : aucune n'est devinée.
 *
 * ANGLE ÉDITORIAL : décoder les noms. Trois angles ont été notés avant rédaction - l'anecdote
 * du surnom (62/100), la provenance SynthID (74/100, qui relève plutôt d'une future fiche
 * dédiée) et le décodage de la gamme (91/100), retenu. Motif : "Nano Banana 2" et
 * "Nano Banana Pro" sont DEUX MODÈLES DIFFÉRENTS (Gemini 3.1 Flash Image contre Gemini 3 Pro
 * Image), et non deux versions d'un même produit. La confusion est réelle et mesurée : elle a
 * failli produire une affirmation fausse dans une fiche d'actualité du site le 2026-08-21,
 * où le tableau d'un fabricant comparait à "Nano-Banana-Pro" pendant que le message relayé
 * parlait de "Nano Banana 2". C'est précisément ce qu'une fiche de glossaire doit lever.
 *
 * match_strategy "loose" (défaut) : même critère que pour "ImgEdit-Bench" et "WeEdit" (cf.
 * migrations 2026_08_21_230000 et 2026_08_21_240000) - l'alias risque-t-il de matcher un mot
 * courant absent de STOP_LIST_FR ? "nano banana" est une paire distinctive sans usage courant
 * en français comme en anglais, et GlossaryLinkifier compare la chaîne COMPLÈTE de l'alias,
 * jamais des sous-mots séparés ("nano" et "banana" pris isolément ne sont jamais évalués). La
 * casse stricte n'apporterait donc aucune protection, seulement des occurrences manquées.
 * Le tri par spécificité du linkifier (cf. v1.198.0) fait que "Nano Banana 2 Lite" est reconnu
 * avant "Nano Banana 2", lui-même avant "Nano Banana" - les alias de la gamme ne se volent
 * donc pas mutuellement les occurrences.
 *
 * "Gemini" seul n'est VOLONTAIREMENT pas un alias : il désigne aussi les modèles de texte, et
 * le poser ici lierait vers cette fiche des mentions qui n'ont rien à voir avec l'image.
 *
 * Anti-doublon vérifié avant écriture (2026-08-21) : aucun terme au slug "nano-banana",
 * "gemini", "synthid" ou "imagen" n'existe, ni en base locale (132 termes) ni en production
 * (les quatre slugs sondés répondent 404). broader_slugs pointe vers "ia-generative" et
 * "modele-multimodal", dont l'existence en production a été vérifiée par appel HTTP 200.
 *
 * Images (nano-banana.jpg / .webp, 1200x669 vérifié) déposées dans public/images/glossaire/
 * AVANT cette migration - has_image=true dès le départ.
 *
 * Données dans database/data/glossaire-batch-2026-08-21-nano-banana.json.
 * Anti-doublon : skip si le slug existe déjà. RÉVERSIBLE : down() supprime le terme par slug.
 */
return new class extends Migration
{
    private function terms(): array
    {
        $path = __DIR__.'/../data/glossaire-batch-2026-08-21-nano-banana.json';
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
