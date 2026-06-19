<?php

use Illuminate\Database\Migrations\Migration;
use Modules\Dictionary\Models\Term;

/**
 * Ajout de 3 termes « tendances 2025-2026 » au glossaire (batch P0 #5, catégorie 1 « IA ») :
 * SLM (petit modèle de langage), Modèle frontière, Poids ouverts.
 * Images générées via le compte Gemini de l'utilisateur (Playwright, règle absolue).
 * Standard complet (AEO, FAQPage, sources vérifiées 200). Anti-doublon par slug. RÉVERSIBLE (down()).
 */
return new class extends Migration
{
    private function terms(): array
    {
        return [
            [
                'slug' => 'slm',
                'name' => 'SLM (petit modèle de langage)',
                'cat' => 1, 'type' => 'concept', 'difficulty' => 'intermediate', 'icon' => '🤏',
                'definition' => "Un SLM (Small Language Model, ou petit modèle de langage) est un modèle de langage fondé sur la même architecture Transformer que les grands modèles (LLM), mais avec un nombre de paramètres nettement plus réduit. Là où un LLM compte des dizaines à des centaines de milliards de paramètres, un SLM se situe généralement sous la barre des 10 milliards, souvent entre 1 et 7 milliards. Il n'existe pas de seuil officiel : la frontière entre « petit » et « grand » est indicative. L'intérêt d'un SLM n'est pas la performance universelle, mais l'efficacité : il consomme beaucoup moins de mémoire et de calcul, répond plus vite, coûte moins cher et peut tourner localement — sur un ordinateur portable, un téléphone ou un appareil embarqué (edge) — ce qui renforce la confidentialité, les données ne quittant pas l'appareil. Les SLM brillent sur des tâches ciblées ou spécialisées, où un modèle bien ajusté suffit largement. Parmi les familles connues : Phi (Microsoft), Gemma (Google) et les petites déclinaisons de Llama (Meta) ou Mistral. C'est une tendance forte de 2025-2026 : faire « assez bien, plus petit, moins cher ».",
                'analogy' => "C'est comme un couteau suisse de poche face à un atelier complet : moins polyvalent qu'un immense LLM, mais largement suffisant, rapide et transportable pour les tâches du quotidien.",
                'example' => "Un assistant qui corrige et reformule vos courriels directement sur votre téléphone, sans connexion : un SLM d'environ 3 milliards de paramètres y suffit, là où un LLM de 400 milliards serait impossible à faire tourner localement.",
                'did_you_know' => "Il n'existe aucune définition officielle de la taille d'un SLM : selon le contexte, certains parlent de « moins de 10 milliards » de paramètres, d'autres exigent « moins de 3 milliards » pour des usages embarqués très contraints.",
                'one_sentence_answer' => "Un SLM est un modèle de langage à faible nombre de paramètres (souvent moins de 10 milliards), optimisé pour la rapidité, le faible coût et l'exécution locale.",
                'faq' => [
                    ['question' => "Quelle différence entre un SLM et un LLM ?", 'answer' => "Surtout la taille : un SLM compte généralement moins de 10 milliards de paramètres (souvent 1 à 7), contre des dizaines à des centaines de milliards pour un LLM — au prix d'une polyvalence moindre mais d'une efficacité bien supérieure."],
                    ['question' => "Pourquoi utiliser un SLM ?", 'answer' => "Pour la rapidité, le faible coût, l'exécution locale/edge et la confidentialité (les données restent sur l'appareil), sur des tâches ciblées où il est amplement suffisant."],
                ],
                'sources' => [
                    ['label' => "IBM — Small language models", 'url' => "https://www.ibm.com/think/topics/small-language-models"],
                    ['label' => "Wikipédia — Grand modèle de langage", 'url' => "https://fr.wikipedia.org/wiki/Grand_mod%C3%A8le_de_langage"],
                ],
            ],
            [
                'slug' => 'modele-frontiere',
                'name' => 'Modèle frontière',
                'cat' => 1, 'type' => 'concept', 'difficulty' => 'advanced', 'icon' => '🧗',
                'definition' => "Un modèle frontière (frontier model) désigne un modèle de fondation généraliste parmi les plus avancés au monde, qui repousse l'état de l'art et dont les capacités dépassent celles des modèles existants. Le terme est moins une mesure de taille qu'un concept centré sur le RISQUE : ces modèles peuvent faire émerger des capacités nouvelles (raisonnement complexe multi-étapes, comportements agentiques, contextes de centaines de milliers de tokens, multimodalité texte-image-audio-vidéo) qui posent des enjeux de sécurité publique et d'usage dual (cyberattaque, biosécurité, désinformation). C'est pourquoi ils font l'objet d'une gouvernance dédiée. Les principaux laboratoires (Google, Microsoft, OpenAI, Anthropic) ont fondé le Frontier Model Forum pour traiter ces risques. En Europe, l'AI Act ne parle pas toujours de « frontier » mais institue un régime pour les modèles à usage général « à risque systémique » : un modèle est présumé tel lorsque la puissance de calcul de son entraînement dépasse 10²⁵ FLOPs, ce qui déclenche des obligations d'évaluation, de tests adversariaux (red-teaming), de mitigation et de cybersécurité renforcée. En pratique, les versions de pointe de GPT, Claude et Gemini sont considérées comme des modèles frontières.",
                'analogy' => "C'est comme les prototypes de Formule 1 de l'IA : les plus rapides et les plus avancés, mais aussi les plus surveillés, parce que repousser la limite comporte des risques qu'on doit encadrer.",
                'example' => "Quand un nouveau modèle de pointe atteint un niveau expert en cybersécurité ou en biologie, les régulateurs et le Frontier Model Forum exigent des tests de sûreté approfondis avant un déploiement large — c'est le traitement réservé aux modèles frontières.",
                'did_you_know' => "L'AI Act européen fixe un seuil chiffré : au-delà de 10²⁵ FLOPs de calcul d'entraînement, un modèle est présumé « à risque systémique » et soumis à des obligations renforcées.",
                'one_sentence_answer' => "Un modèle frontière est un modèle d'IA généraliste parmi les plus avancés, dont les capacités de pointe justifient une gouvernance et une évaluation de sécurité spécifiques.",
                'faq' => [
                    ['question' => "Qu'est-ce que le Frontier Model Forum ?", 'answer' => "Une organisation à but non lucratif fondée par Google, Microsoft, OpenAI et Anthropic pour traiter les risques de sécurité posés par les modèles frontières (recherche, partage d'information, bonnes pratiques)."],
                    ['question' => "Pourquoi le mot « frontière » ?", 'answer' => "Parce que ces modèles repoussent la frontière des capacités de l'IA ; le terme met l'accent sur le risque lié à ces capacités émergentes, pas seulement sur la taille."],
                ],
                'sources' => [
                    ['label' => "Frontier Model Forum", 'url' => "https://www.frontiermodelforum.org/"],
                    ['label' => "AI Act — Article 55 (GPAI à risque systémique)", 'url' => "https://artificialintelligenceact.eu/article/55/"],
                ],
            ],
            [
                'slug' => 'poids-ouverts',
                'name' => 'Poids ouverts',
                'cat' => 1, 'type' => 'concept', 'difficulty' => 'intermediate', 'icon' => '🔓',
                'definition' => "« Poids ouverts » (open weights) qualifie un modèle d'IA dont les poids entraînés — les paramètres numériques appris pendant l'entraînement, qui encodent son comportement — sont publiquement téléchargeables. On peut alors exécuter le modèle localement, sur sa propre infrastructure, et l'adapter par fine-tuning sans repartir de zéro. Attention à ne pas confondre avec « open source » au sens strict : ouvrir les poids ne signifie pas forcément publier le code d'entraînement, les données utilisées, ni accorder une licence libre. Un modèle vraiment open source (au sens de l'OSI) suppose code + poids + description reproductible des données et de la méthode, sous une licence permissive (type Apache 2.0 ou MIT). Or la plupart des modèles présentés comme « open source » sont en réalité seulement open-weights, sous une licence « communautaire » plus ou moins restrictive (limites d'usage, interdiction de concurrencer l'éditeur, etc.). Exemples : Llama (Meta, licence communautaire), Mistral, DeepSeek, Gemma (Google, Gemma Terms of Use). Les poids ouverts ont démocratisé l'IA auto-hébergée, mais lire la licence reste essentiel.",
                'analogy' => "C'est comme recevoir un gâteau déjà cuit avec le droit de le re-décorer : tu as le résultat (les poids) et tu peux le personnaliser, mais pas forcément la recette complète (le code et les données d'entraînement).",
                'example' => "Télécharger les poids de Llama ou Mistral pour faire tourner un assistant IA sur ses propres serveurs et l'ajuster à son domaine : c'est possible grâce aux poids ouverts — mais la licence « communautaire » peut interdire certains usages commerciaux concurrents.",
                'did_you_know' => "Beaucoup de modèles annoncés « open source » dans le marketing ne le sont pas vraiment au sens de l'OSI : ce sont des modèles open-weights sous licence restrictive — la nuance a son importance juridique.",
                'one_sentence_answer' => "Un modèle à poids ouverts publie ses paramètres entraînés (téléchargeables, exécutables localement, ajustables), sans nécessairement ouvrir son code, ses données ni offrir une licence vraiment libre.",
                'faq' => [
                    ['question' => "Open weights ou open source : quelle différence ?", 'answer' => "Open weights = seulement les poids téléchargeables ; open source (au sens OSI) = code + poids + données/méthode reproductibles sous licence libre. La plupart des LLM « ouverts » sont en fait open-weights."],
                    ['question' => "À quoi servent les poids ouverts ?", 'answer' => "À exécuter le modèle localement (confidentialité, contrôle) et à le personnaliser par fine-tuning, sous réserve des conditions de la licence."],
                ],
                'sources' => [
                    ['label' => "Open Source Initiative — Open Weights", 'url' => "https://opensource.org/ai/open-weights"],
                    ['label' => "IBM — Open source LLMs", 'url' => "https://www.ibm.com/think/topics/open-source-llms"],
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

        // Cette migration insère des données avec des FK vers dictionary_categories
        // qui n'existent que sur MySQL (seedées en prod). SQLite en tests = skip.
        if (\Illuminate\Support\Facades\DB::connection()->getDriverName() !== 'mysql') {
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
