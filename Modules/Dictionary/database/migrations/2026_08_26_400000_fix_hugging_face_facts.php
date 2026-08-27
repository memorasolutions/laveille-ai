<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Modules\Dictionary\Models\Term;

/**
 * Corrige deux faits de la fiche « Hugging Face » (2026-08-26), suite à une demande de contrôle
 * de routine (l'actualité du jour cite deux fois la fiche, aucun signe de casse - mais le
 * mandat était de vérifier, pas de fabriquer une retouche).
 *
 * AUCUNE FICHE NOUVELLE, AUCUN ALIAS AJOUTÉ. Le terme `hugging-face` existe déjà et est complet
 * au standard (définition, analogie, exemple, saviez-vous, FAQ, sources, DefinedTerm, FAQPage,
 * paire webp+jpg). Les alias déjà posés (HuggingFace, Hugging-Face, huggingface.co, HF Hub,
 * Hugging Face Hub) couvrent les variantes réelles ; « HF » seul est délibérément ÉCARTÉ - un
 * sigle de deux lettres est trop générique (haute fréquence, hydrofluoric acid, argot « have
 * fun ») pour un site généraliste, exactement le défaut qui a produit de faux auto-liens
 * ailleurs sur le site (cf. incidents « Google »→Gemini du 2026-08-23 et prénom seul du
 * 2026-08-25).
 *
 * DEUX FAITS CORRIGÉS, VÉRIFIÉS LE 2026-08-26 :
 *
 *  1. `definition` - les volumes du Hub (« 500 000 modèles, 100 000 jeux de données, 100 000
 *     espaces ») datent en réalité de 2023 (déjà le chiffre cité par Reuters au moment de la
 *     série D). Comptage RÉEL pris directement sur huggingface.co (numTotalItems intégré à la
 *     page, source primaire - pas une estimation) le 2026-08-26 : 3 025 493 modèles,
 *     1 020 296 jeux de données, 1 457 210 espaces. Remplacés par des ordres de grandeur arrondis
 *     (3 millions / 1 million / 1,4 million) plutôt que le compte exact, qui change à la minute -
 *     un arrondi reste vrai plus longtemps qu'un chiffre précis.
 *
 *  2. `did_you_know` - la phrase affirmait que la mascotte de l'entreprise (câlin) précédait
 *     l'emoji 🤗 (« bien avant l'emoji »). C'est l'INVERSE : l'emoji 🤗 (U+1F917, « hugging
 *     face ») existe dans Unicode depuis la version 8.0, juin 2015 - un an avant la fondation de
 *     l'entreprise en 2016. Les fondateurs ont choisi ce nom et ce logo EN RÉFÉRENCE à l'emoji
 *     déjà existant, pas l'inverse. Profite de la réécriture pour retirer le tiret cadratin
 *     (interdit, règle 10) que portait la phrase d'origine.
 *
 * Recherche : `mcp__openrouter__chat_with_model` modèle `perplexity/sonar-pro`, en repli documenté
 * (`mcp__perplexity-pro-playwright__pp_search` non chargé dans ce contexte de sous-agent) -
 * chronologie Unicode 8.0/2015 recoupée sur deux référentiels indépendants (Codepoints.net,
 * Compart), fondation 2016 recoupée sur plusieurs profils d'entreprise. Comptages Hub confirmés
 * par appel direct à huggingface.co (source primaire, pas un oracle).
 *
 * IDEMPOTENTE : chaque remplacement ne s'applique que si l'ancien texte est encore présent.
 * RÉVERSIBLE : down() restaure exactement les deux phrases d'origine.
 *
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 */
return new class extends Migration
{
    private const SLUG = 'hugging-face';

    private const OLD_DEFINITION_CHIFFRES = 'plus de 500 000 modèles, 100 000 jeux de données et 100 000 espaces de démonstration Gradio';

    private const NEW_DEFINITION_CHIFFRES = "plus de 3 millions de modèles, 1 million de jeux de données et 1,4 million d'espaces de démonstration Gradio";

    private const OLD_DID_YOU_KNOW = "Le nom Hugging Face vient de l'idée que les machines devraient être amicales et empathiques — d'où leur mascotte originale inspirée d'un câlin, bien avant l'emoji 🤗.";

    private const NEW_DID_YOU_KNOW = "Le nom Hugging Face vient de l'emoji 🤗, déjà présent dans Unicode depuis 2015 - un an avant la fondation de l'entreprise, adopté comme logo pour incarner une IA amicale et accessible.";

    private function terme(): ?Term
    {
        // `slug` est TRADUISIBLE (Spatie) : la colonne contient un JSON, et `where('slug', ...)`
        // compare ce JSON entier à une chaîne simple - donc ne correspond JAMAIS.
        return Term::where('slug->fr_CA', self::SLUG)->first()
            ?? Term::where('slug->fr', self::SLUG)->first();
    }

    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql' || ! class_exists(Term::class)) {
            return;
        }

        $term = $this->terme();
        if (! $term) {
            // Base locale désynchronisée de la production : on ne crée SURTOUT pas la fiche ici.
            echo "[glossaire] terme 'hugging-face' absent de cette base, correctif ignoré\n";

            return;
        }

        $changed = false;

        foreach (['fr_CA', 'fr'] as $locale) {
            $definition = (string) $term->getTranslation('definition', $locale, false);
            if ($definition !== '' && str_contains($definition, self::OLD_DEFINITION_CHIFFRES)) {
                $term->setTranslation('definition', $locale, str_replace(self::OLD_DEFINITION_CHIFFRES, self::NEW_DEFINITION_CHIFFRES, $definition));
                $changed = true;
            }

            $didYouKnow = (string) $term->getTranslation('did_you_know', $locale, false);
            if ($didYouKnow !== '' && str_contains($didYouKnow, self::OLD_DID_YOU_KNOW)) {
                $term->setTranslation('did_you_know', $locale, str_replace(self::OLD_DID_YOU_KNOW, self::NEW_DID_YOU_KNOW, $didYouKnow));
                $changed = true;
            }
        }

        if ($changed) {
            $term->save();
            echo "[glossaire] hugging-face : chiffres du Hub et genèse de l'emoji corrigés\n";
        } else {
            echo "[glossaire] hugging-face : rien à corriger (texte déjà à jour ou différent de l'attendu)\n";
        }
    }

    public function down(): void
    {
        if (! class_exists(Term::class)) {
            return;
        }

        $term = $this->terme();
        if (! $term) {
            return;
        }

        $changed = false;

        foreach (['fr_CA', 'fr'] as $locale) {
            $definition = (string) $term->getTranslation('definition', $locale, false);
            if (str_contains($definition, self::NEW_DEFINITION_CHIFFRES)) {
                $term->setTranslation('definition', $locale, str_replace(self::NEW_DEFINITION_CHIFFRES, self::OLD_DEFINITION_CHIFFRES, $definition));
                $changed = true;
            }

            $didYouKnow = (string) $term->getTranslation('did_you_know', $locale, false);
            if (str_contains($didYouKnow, self::NEW_DID_YOU_KNOW)) {
                $term->setTranslation('did_you_know', $locale, str_replace(self::NEW_DID_YOU_KNOW, self::OLD_DID_YOU_KNOW, $didYouKnow));
                $changed = true;
            }
        }

        if ($changed) {
            $term->save();
        }
    }
};
