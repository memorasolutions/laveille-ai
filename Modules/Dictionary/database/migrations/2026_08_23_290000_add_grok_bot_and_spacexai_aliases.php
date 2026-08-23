<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Modules\Dictionary\Models\Category;
use Modules\Dictionary\Models\Term;

/**
 * Ajoute « Grok Bot » au glossaire, et rattache « SpaceXAI » au terme « xAI » existant (2026-08-23).
 *
 * DEUX DEMANDES, DEUX TRAITEMENTS DIFFÉRENTS, et c'est tout l'intérêt du contrôle anti-doublon
 * fait avant d'écrire quoi que ce soit :
 *
 *  - « SpaceXAI » n'est PAS une notion nouvelle : c'est le nom actuel de l'entreprise déjà
 *    décrite par le terme « xai ». Créer une seconde fiche aurait divisé le référencement entre
 *    deux pages qui se cannibalisent, et cassé l'auto-lien, qui doit pouvoir choisir UNE cible.
 *    On ajoute donc des alias au terme existant et on met sa définition à jour.
 *  - « Grok Bot » EST une notion distincte : un produit d'agents persistants, différent de
 *    « Grok » (l'assistant conversationnel, terme `grok-xai`) et de « Grok Build » (l'agent de
 *    programmation). Trois noms proches, trois produits. La confusion est réelle et mesurée : une
 *    publication virale du 22 août 2026 les mélangeait. C'est exactement ce qu'une fiche lève.
 *
 * FAITS VÉRIFIÉS À LA SOURCE PRIMAIRE, au navigateur, le 2026-08-23 - pas sur un résumé :
 *  - la page d'accueil de x.ai porte le titre « SpaceXAI » et le compte social « @spacexai », et
 *    la chaîne « xAI » seule n'apparaît PAS une fois dans le corps de la page ;
 *  - la documentation officielle s'intitule « Grok Bot | SpaceXAI Docs » et définit un Bot comme
 *    un coéquipier disposant d'un ordinateur infonuagique persistant.
 * Cette vérification a tranché une CONTRADICTION entre deux réponses d'un même oracle, l'une
 * affirmant que « SpaceXAI » était le nom officiel, l'autre qu'il n'existait pas. La seconde
 * lisait le communiqué d'acquisition du 2 février 2026, qui parle bien de « SpaceX » et de
 * « xAI » : exacte sur ce document, fausse sur la marque actuelle.
 *
 * « SpaceX » n'est VOLONTAIREMENT pas un alias : c'est l'entreprise de fusées qui a acquis xAI,
 * pas le laboratoire d'IA. L'y mettre reproduirait exactement le défaut corrigé le matin même,
 * où « Google » renvoyait vers la fiche du modèle Gemini (cf. v1.206.4).
 *
 * IDEMPOTENTE : le terme est ignoré s'il existe déjà, et les alias sont fusionnés sans doublon.
 * RÉVERSIBLE : down() supprime le terme ajouté et retire UNIQUEMENT les alias posés ici.
 */
return new class extends Migration
{
    private const ALIAS_SPACEXAI = ['SpaceXAI', 'Space XAI'];

    private const AJOUT_DEFINITION = ' Le 2 février 2026, SpaceX a acquis xAI ; depuis, l\'entreprise se présente publiquement sous le nom de SpaceXAI, qui est celui porté aujourd\'hui par son site et par sa documentation.';

    private function terms(): array
    {
        $path = __DIR__.'/../data/glossaire-batch-2026-08-23-grok-bot.json';

        return is_file($path) ? (json_decode(file_get_contents($path), true) ?: []) : [];
    }

    private function termeXai(): ?Term
    {
        return Term::where('slug->fr_CA', 'xai')->first() ?? Term::where('slug->fr', 'xai')->first();
    }

    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql' || ! class_exists(Term::class) || ! class_exists(Category::class)) {
            return;
        }

        // ── 1. Grok Bot : terme nouveau ────────────────────────────────────────────────
        $catId = Category::where('slug->fr_CA', 'intelligence-artificielle')->value('id')
            ?? Category::where('slug->fr', 'intelligence-artificielle')->value('id');

        foreach ($this->terms() as $i => $t) {
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
            $term->dictionary_category_id = $catId;
            $term->hero_image = ! empty($t['has_image']) ? 'images/glossaire/'.$t['slug'].'.webp' : null;
            $term->is_published = true;
            $term->match_strategy = $t['match_strategy'] ?? 'loose';
            $term->sort_order = 940 + $i;
            $term->save();

            echo "[glossaire] inséré : {$t['slug']} (id={$term->id})\n";
        }

        // ── 2. xAI : alias SpaceXAI + définition remise à jour ─────────────────────────
        $xai = $this->termeXai();
        if (! $xai) {
            echo "[glossaire] terme 'xai' absent, alias SpaceXAI non posés\n";

            return;
        }

        $xai->aliases = array_values(array_unique(array_merge($xai->aliases ?? [], self::ALIAS_SPACEXAI)));

        foreach (['fr_CA', 'fr'] as $locale) {
            $definition = (string) $xai->getTranslation('definition', $locale, false);
            // Idempotent : on n'ajoute la phrase que si elle n'y est pas déjà.
            if ($definition !== '' && ! str_contains($definition, 'SpaceXAI')) {
                $xai->setTranslation('definition', $locale, $definition.self::AJOUT_DEFINITION);
            }
        }

        // Relation vers le produit d'agents, pour que le lecteur circule entre les deux.
        $xai->narrower_slugs = array_values(array_unique(array_merge($xai->narrower_slugs ?? [], ['grok-bot'])));
        $xai->save();

        echo "[glossaire] xai : alias SpaceXAI posés, définition mise à jour, lien vers grok-bot\n";
    }

    public function down(): void
    {
        if (! class_exists(Term::class)) {
            return;
        }

        Term::where('slug->fr_CA', 'grok-bot')->delete();

        $xai = $this->termeXai();
        if (! $xai) {
            return;
        }

        $xai->aliases = array_values(array_diff($xai->aliases ?? [], self::ALIAS_SPACEXAI));
        $xai->narrower_slugs = array_values(array_diff($xai->narrower_slugs ?? [], ['grok-bot']));

        foreach (['fr_CA', 'fr'] as $locale) {
            $definition = (string) $xai->getTranslation('definition', $locale, false);
            if (str_contains($definition, self::AJOUT_DEFINITION)) {
                $xai->setTranslation('definition', $locale, str_replace(self::AJOUT_DEFINITION, '', $definition));
            }
        }

        $xai->save();
    }
};
