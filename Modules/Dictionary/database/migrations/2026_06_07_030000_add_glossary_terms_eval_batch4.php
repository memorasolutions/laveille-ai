<?php

use Illuminate\Database\Migrations\Migration;
use Modules\Dictionary\Models\Term;

/**
 * Ajout de 3 termes « évaluation des modèles » au glossaire (batch P0 #4) :
 * Précision et rappel, Matrice de confusion (cat 6 « Données et traitement »), LLM-as-a-judge (cat 1 « IA »).
 * Même standard/structure que batch1-3. Anti-doublon par slug. RÉVERSIBLE via down().
 */
return new class extends Migration
{
    private function terms(): array
    {
        return [
            [
                'slug' => 'precision-et-rappel',
                'name' => 'Précision et rappel',
                'cat' => 6, 'type' => 'concept', 'difficulty' => 'intermediate', 'icon' => '📊',
                'definition' => "La précision et le rappel sont deux mesures complémentaires de la qualité d'un modèle de classification. La précision répond à la question « parmi tout ce que le modèle a désigné comme positif, quelle proportion l'était vraiment ? » : elle pénalise les fausses alertes (faux positifs). Le rappel répond à « parmi tous les cas réellement positifs, quelle proportion le modèle a-t-il retrouvée ? » : il pénalise les oublis (faux négatifs). Les deux se calculent à partir des vrais positifs, faux positifs et faux négatifs, souvent visualisés dans une matrice de confusion. Il existe presque toujours un compromis entre elles : augmenter le rappel (ne rien manquer) fait souvent baisser la précision (plus de fausses alertes), et inversement. On les résume parfois par le score F1, leur moyenne harmonique. Le bon équilibre dépend de l'enjeu : pour un test médical on privilégie le rappel (ne pas rater un malade) ; pour un filtre anti-pourriel, la précision (ne pas bloquer de vrais courriels).",
                'analogy' => "C'est comme pêcher au filet : la précision dit quelle part de ta prise est vraiment le poisson visé (et non des algues), tandis que le rappel dit quelle part de tous les poissons visés présents dans l'eau tu as réussi à attraper.",
                'example' => "Un détecteur de fraude signale 100 transactions ; 80 sont de vraies fraudes (précision = 80 %). Mais il existait 200 fraudes au total : il n'en a donc trouvé que 80 (rappel = 40 %). Bonne précision, mauvais rappel : il rate beaucoup de fraudes.",
                'did_you_know' => "Un modèle peut afficher 99 % d'exactitude tout en étant inutile : s'il y a 1 % de cas positifs et qu'il prédit toujours « négatif », il a 99 % de bonnes réponses mais un rappel de 0 %.",
                'one_sentence_answer' => "La précision mesure la proportion de prédictions positives qui sont correctes ; le rappel, la proportion de cas réellement positifs que le modèle a retrouvés.",
                'faq' => [
                    ['question' => "Quelle différence entre précision et rappel ?", 'answer' => "La précision pénalise les fausses alertes (faux positifs) ; le rappel pénalise les oublis (faux négatifs). On améliore rarement l'une sans dégrader l'autre."],
                    ['question' => "Qu'est-ce que le score F1 ?", 'answer' => "C'est la moyenne harmonique de la précision et du rappel : un chiffre unique qui résume l'équilibre entre les deux."],
                ],
                'sources' => [
                    ['label' => "Wikipédia — Précision et rappel", 'url' => "https://fr.wikipedia.org/wiki/Pr%C3%A9cision_et_rappel"],
                    ['label' => "Google — ML Crash Course : Accuracy, precision, recall", 'url' => "https://developers.google.com/machine-learning/crash-course/classification/accuracy-precision-recall"],
                ],
            ],
            [
                'slug' => 'matrice-de-confusion',
                'name' => 'Matrice de confusion',
                'cat' => 6, 'type' => 'concept', 'difficulty' => 'intermediate', 'icon' => '🧮',
                'definition' => "La matrice de confusion est un tableau qui résume les performances d'un modèle de classification en croisant ses prédictions avec la réalité. Pour une classification binaire, c'est un tableau 2×2 dont les quatre cases sont : les vrais positifs (cas positifs correctement prédits positifs), les vrais négatifs (négatifs prédits négatifs), les faux positifs (négatifs prédits positifs à tort, les « fausses alertes ») et les faux négatifs (positifs ratés). Elle offre une vue bien plus riche que la simple exactitude (accuracy), car elle montre QUELS types d'erreurs le modèle commet. La plupart des métriques d'évaluation — précision, rappel, score F1, spécificité — se calculent directement à partir de ses quatre valeurs. Pour un problème à plusieurs classes, la matrice s'agrandit (une ligne et une colonne par classe), et sa diagonale représente les prédictions correctes.",
                'analogy' => "C'est comme le bulletin détaillé d'un examen : au lieu d'une seule note globale, on voit exactement combien de bonnes réponses, mais aussi quelles questions ont été ratées et de quelle façon.",
                'example' => "Un test de dépistage donne : 70 vrais positifs, 20 faux positifs, 5 faux négatifs, 905 vrais négatifs. On en déduit directement la précision (70/90 ≈ 78 %) et le rappel (70/75 ≈ 93 %).",
                'did_you_know' => "Le nom « matrice de confusion » vient du fait qu'elle révèle quand un modèle « confond » deux classes — par exemple un système qui prend souvent des 9 pour des 4 dans la reconnaissance de chiffres.",
                'one_sentence_answer' => "La matrice de confusion est un tableau qui croise les prédictions d'un modèle et la réalité pour montrer précisément ses bonnes réponses et ses types d'erreurs.",
                'faq' => [
                    ['question' => "À quoi sert la matrice de confusion ?", 'answer' => "À voir non seulement combien d'erreurs un modèle commet, mais surtout lesquelles (faux positifs vs faux négatifs), et à en déduire précision, rappel et F1."],
                    ['question' => "Que sont les faux positifs et faux négatifs ?", 'answer' => "Un faux positif est une fausse alerte (négatif prédit positif) ; un faux négatif est un cas positif manqué (prédit négatif)."],
                ],
                'sources' => [
                    ['label' => "Wikipédia — Matrice de confusion", 'url' => "https://fr.wikipedia.org/wiki/Matrice_de_confusion"],
                    ['label' => "IBM — Precision and recall", 'url' => "https://www.ibm.com/think/topics/precision-recall"],
                ],
            ],
            [
                'slug' => 'llm-as-a-judge',
                'name' => 'LLM-as-a-judge',
                'cat' => 1, 'type' => 'technique', 'difficulty' => 'intermediate', 'icon' => '⚖️',
                'definition' => "« LLM-as-a-judge » (le LLM-juge, ou évaluation par un grand modèle de langage) est une technique consistant à utiliser un modèle de langage puissant pour évaluer automatiquement les réponses produites par un autre modèle. Plutôt que de comparer mot à mot à une réponse de référence — ce qui rate les reformulations valables — on demande au modèle-juge de noter une réponse selon des critères comme la pertinence, l'exactitude, la cohérence ou le respect d'une consigne, parfois en comparant deux réponses pour désigner la meilleure. Cette approche s'est imposée à partir de 2023 parce qu'elle est rapide, peu coûteuse et qu'elle s'adapte bien aux tâches ouvertes où il n'existe pas une seule bonne réponse. Elle a toutefois des limites connues : le modèle-juge peut présenter des biais (préférer les réponses longues, ou celles issues de modèles proches du sien), d'où l'importance de bien calibrer le prompt d'évaluation et de vérifier l'accord de ses jugements avec ceux d'évaluateurs humains.",
                'analogy' => "C'est comme confier la correction des copies à un examinateur expérimenté plutôt qu'à une grille de mots-clés rigide : il comprend les bonnes réponses formulées autrement, mais il faut s'assurer qu'il note de façon juste et constante.",
                'example' => "Pour évaluer un robot conversationnel, on envoie à un modèle-juge la question, la réponse du robot et une consigne : « note de 1 à 5 la pertinence et l'exactitude ». Le juge attribue par exemple 4/5 avec une justification, sur des milliers de réponses, en quelques minutes.",
                'did_you_know' => "Les modèles-juges présentent souvent un « biais de position » : dans une comparaison de deux réponses, ils tendent à préférer celle présentée en premier — on corrige cela en inversant l'ordre et en moyennant les deux jugements.",
                'one_sentence_answer' => "Le « LLM-as-a-judge » consiste à utiliser un grand modèle de langage pour évaluer ou comparer automatiquement les réponses d'autres modèles selon des critères de qualité.",
                'faq' => [
                    ['question' => "Pourquoi utiliser un LLM comme juge ?", 'answer' => "Parce qu'il évalue des réponses ouvertes (sans réponse unique) plus finement qu'une comparaison mot à mot, rapidement et à faible coût, sur de gros volumes."],
                    ['question' => "Quels sont les risques ?", 'answer' => "Des biais du juge (préférence pour les réponses longues, biais de position, affinité avec des modèles proches) ; il faut calibrer le prompt et vérifier l'accord avec l'humain."],
                ],
                'sources' => [
                    ['label' => "Hugging Face — LLM-as-a-judge (cookbook)", 'url' => "https://huggingface.co/learn/cookbook/en/llm_judge"],
                    ['label' => "Evidently AI — LLM-as-a-judge guide", 'url' => "https://www.evidentlyai.com/llm-guide/llm-as-a-judge"],
                ],
            ],
        ];
    }

    public function up(): void
    {
        if (\Illuminate\Support\Facades\DB::getDriverName() !== 'mysql') {
            return;
        }
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
