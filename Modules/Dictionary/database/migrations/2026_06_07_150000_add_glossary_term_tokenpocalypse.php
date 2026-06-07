<?php

use Illuminate\Database\Migrations\Migration;
use Modules\Dictionary\Models\Term;

/**
 * Ajout du terme « Tokenpocalypse » au glossaire (cat 1 « Intelligence artificielle »).
 * Néologisme 2026 : explosion des coûts de tokens (agents IA), fin des forfaits illimités.
 * Image via le compte Gemini de l'utilisateur. Standard complet, sources vérifiées 200.
 * Anti-doublon par slug. RÉVERSIBLE (down()).
 */
return new class extends Migration
{
    private function terms(): array
    {
        return [
            [
                'slug' => 'tokenpocalypse',
                'name' => 'Tokenpocalypse (apocalypse des tokens)',
                'cat' => 1, 'type' => 'concept', 'difficulty' => 'intermediate', 'icon' => '💸',
                'definition' => "« Tokenpocalypse » (mot-valise de token et apocalypse) est un néologisme apparu dans la communauté technologique en 2026 pour décrire le moment où le coût et la gestion des tokens deviennent un problème majeur pour les utilisateurs d'IA. Rappel : un token est la plus petite unité de texte qu'un grand modèle de langage manipule (un mot ou un morceau de mot), et c'est aussi l'unité de facturation de la plupart des fournisseurs, ainsi que la limite du « contexte » que le modèle peut traiter. Le terme décrit la convergence de trois phénomènes : l'explosion de la consommation de tokens — surtout avec les agents autonomes, qui relisent le contexte à chaque étape (planifier, agir, observer) et peuvent consommer jusqu'à mille fois plus de tokens qu'une simple question ; le durcissement des limites techniques (fenêtres de contexte, quotas par minute) ; et la fin des forfaits « illimités » subventionnés, remplacés par une facturation à l'usage (prix au million de tokens). Le mot s'est popularisé après des changements de tarification très commentés, comme le passage de GitHub Copilot à une facturation au token. Ce n'est pas un concept académique mais un label médiatique : il traduit la prise de conscience que l'IA agentique, autrefois quasi gratuite, peut désormais coûter cher et de façon difficilement prévisible.",
                'analogy' => "C'est comme un forfait de données mobiles « illimité » qui se met soudain à être facturé au mégaoctet : tant que c'était gratuit, on diffusait sans compter ; du jour où chaque octet est payant, une application gourmande qui tourne en arrière-plan peut faire exploser la facture sans qu'on s'en aperçoive.",
                'example' => "Une PME branche un agent IA qui, pour chaque tâche, relit toute une page web et de longs journaux à chaque étape de son raisonnement. Ce qui semblait rentable en démonstration sur un abonnement fixe se transforme, en production, en flux où chaque traitement complexe coûte plusieurs dollars de tokens — l'effet « tokenpocalypse ».",
                'did_you_know' => "Selon des analyses de 2026, une tâche confiée à un agent autonome peut consommer jusqu'à mille fois plus de tokens qu'une question de raisonnement classique, principalement à cause de l'« empilement de contexte » relu à chaque étape — d'où des factures imprévisibles.",
                'one_sentence_answer' => "La « tokenpocalypse » est un terme informel de 2026 désignant l'explosion des coûts de tokens — surtout avec les agents IA — combinée à la fin des forfaits illimités et au durcissement des limites.",
                'faq' => [
                    ['question' => "Pourquoi les agents IA déclenchent-ils une « tokenpocalypse » ?", 'answer' => "Parce qu'un agent enchaîne des étapes (planifier, agir, observer) et relit tout ou partie du contexte à chacune ; cette « boule de neige » de tokens peut multiplier la consommation par mille par rapport à une simple question."],
                    ['question' => "« Tokenpocalypse » est-il un terme officiel ?", 'answer' => "Non : c'est un néologisme communautaire (Reddit, LinkedIn) repris par certains médias en 2026 pour décrire la hausse des coûts de tokens et la fin des offres illimitées, pas un concept académique."],
                ],
                'sources' => [
                    ['label' => "Stanford Digital Economy Lab — How are AI agents spending your tokens?", 'url' => "https://digitaleconomy.stanford.edu/news/how-are-ai-agents-spending-your-tokens/"],
                    ['label' => "Yahoo Finance — The dawn of the tokenpocalypse", 'url' => "https://finance.yahoo.com/sectors/technology/articles/dawn-tokenpocalypse-202613851.html"],
                ],
            ],
        ];
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
