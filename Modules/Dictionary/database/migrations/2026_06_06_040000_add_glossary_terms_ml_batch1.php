<?php

use Illuminate\Database\Migrations\Migration;
use Modules\Dictionary\Models\Term;

/**
 * Ajout de 3 termes fondamentaux d'entraînement ML au glossaire (batch P0 #1) :
 * Rétropropagation, Descente de gradient, Fonction de perte (catégorie 2 « Concepts fondamentaux »).
 *
 * Conforme au standard glossaire : champs AEO (one_sentence_answer), FAQPage (faq {question,answer}),
 * sources GEO ({label,url} vérifiées 200), image hero {slug}.webp + og:image {slug}.jpg.
 * Anti-doublon : skip si le slug existe déjà. RÉVERSIBLE : down() supprime les 3 termes par slug.
 * Contenu rédigé via délégation MCP (gpt-4o-mini) + faits sourcés (sonar-pro), affiné par le superviseur.
 */
return new class extends Migration
{
    private function terms(): array
    {
        return [
            [
                'slug' => 'retropropagation',
                'name' => 'Rétropropagation',
                'cat' => 2, 'type' => 'technique', 'difficulty' => 'advanced', 'icon' => '🔁',
                'definition' => "La rétropropagation est un algorithme fondamental de l'apprentissage profond qui permet de calculer efficacement le gradient de la fonction de perte par rapport à chaque poids d'un réseau de neurones. En propageant l'erreur de la couche de sortie vers la couche d'entrée à l'aide de la règle de chaîne (dérivation en chaîne), elle indique dans quelle direction et de combien ajuster chaque poids. Elle fournit ainsi les gradients nécessaires à la descente de gradient, ce qui est essentiel pour minimiser la fonction de perte au cours de l'entraînement. Popularisée pour les réseaux multicouches par Rumelhart, Hinton et Williams en 1986, elle forme, avec la fonction de perte et la descente de gradient, le trio au cœur de l'apprentissage supervisé. En permettant aux réseaux d'apprendre à partir de leurs erreurs, la rétropropagation est devenue une technique incontournable du deep learning moderne.",
                'analogy' => "C'est comme corriger une recette ratée en remontant la chaîne des étapes : on part du goût final décevant et on attribue à chaque étape (cuisson, dosage, mélange) sa part de responsabilité, pour savoir quoi rectifier la prochaine fois.",
                'example' => "Un réseau prédit une température de 25 °C alors que la vraie valeur est 20 °C. La rétropropagation mesure cette erreur de 5 °C, calcule la contribution de chaque poids à l'erreur, puis transmet ces gradients à la descente de gradient qui ajuste les poids pour rapprocher la prochaine prédiction de 20 °C.",
                'did_you_know' => "Bien que popularisée en 1986, l'idée mathématique sous-jacente — la différentiation automatique en mode inverse — avait déjà été décrite par Seppo Linnainmaa en 1970, soit plus de quinze ans plus tôt.",
                'one_sentence_answer' => "La rétropropagation est l'algorithme qui calcule, couche par couche, comment ajuster chaque poids d'un réseau de neurones pour réduire son erreur.",
                'faq' => [
                    ['question' => "Pourquoi la rétropropagation est-elle importante ?", 'answer' => "Elle calcule efficacement les gradients nécessaires pour optimiser les millions de poids d'un réseau de neurones, ce qui rend l'entraînement du deep learning possible en pratique."],
                    ['question' => "Qui a popularisé l'algorithme de rétropropagation ?", 'answer' => "David Rumelhart, Geoffrey Hinton et Ronald Williams, dans un article de référence publié en 1986."],
                ],
                'sources' => [
                    ['label' => "Wikipédia — Rétropropagation du gradient", 'url' => "https://fr.wikipedia.org/wiki/R%C3%A9tropropagation_du_gradient"],
                    ['label' => "IBM — Qu'est-ce que la rétropropagation ?", 'url' => "https://www.ibm.com/think/topics/backpropagation"],
                ],
            ],
            [
                'slug' => 'descente-de-gradient',
                'name' => 'Descente de gradient',
                'cat' => 2, 'type' => 'technique', 'difficulty' => 'intermediate', 'icon' => '📉',
                'definition' => "La descente de gradient est une méthode d'optimisation itérative qui ajuste les paramètres d'un modèle en suivant la pente de la fonction de perte. Elle applique la règle θ = θ − η·∇L(θ), où η est le taux d'apprentissage et ∇L(θ) le gradient de la perte par rapport aux paramètres θ. À chaque itération, on fait un petit pas dans la direction qui fait le plus baisser l'erreur. Il existe plusieurs variantes : la descente par lot complet, la descente stochastique (SGD, un exemple à la fois), la descente par mini-lots, ainsi que des optimiseurs avancés comme Adam ou l'ajout de momentum, qui accélèrent et stabilisent la convergence. Dans les réseaux de neurones, elle s'appuie sur les gradients calculés par la rétropropagation pour minimiser la fonction de perte. C'est l'un des moteurs essentiels de l'apprentissage automatique moderne.",
                'analogy' => "C'est comme descendre une colline dans un épais brouillard : on ne voit pas la vallée, alors on tâte le sol sous ses pieds et, à chaque pas, on avance dans la direction où ça descend le plus, jusqu'à atteindre le point le plus bas.",
                'example' => "Avec un taux d'apprentissage de 0,1, si un poids vaut 0,5 et que son gradient est de 0,2, le nouveau poids devient 0,5 − 0,1 × 0,2 = 0,48. En répétant ce calcul des milliers de fois sur l'ensemble des poids, le modèle converge vers une erreur minimale.",
                'did_you_know' => "Le taux d'apprentissage est un réglage critique : trop grand, l'algorithme « saute » par-dessus le minimum et diverge ; trop petit, l'entraînement devient extrêmement lent et peut rester coincé.",
                'one_sentence_answer' => "La descente de gradient met à jour les paramètres d'un modèle, petit pas par petit pas, dans la direction qui réduit le plus la fonction de perte.",
                'faq' => [
                    ['question' => "Qu'est-ce que le taux d'apprentissage ?", 'answer' => "C'est l'hyperparamètre qui fixe la taille des pas lors de la mise à jour des paramètres ; il contrôle la vitesse et la stabilité de l'apprentissage."],
                    ['question' => "Quelle différence avec la descente de gradient stochastique (SGD) ?", 'answer' => "La SGD met à jour les paramètres à partir d'un seul exemple (ou d'un mini-lot) à la fois, au lieu de tout le jeu de données, ce qui accélère l'entraînement."],
                ],
                'sources' => [
                    ['label' => "Wikipédia — Algorithme du gradient", 'url' => "https://fr.wikipedia.org/wiki/Algorithme_du_gradient"],
                    ['label' => "IBM — Gradient descent", 'url' => "https://www.ibm.com/think/topics/gradient-descent"],
                ],
            ],
            [
                'slug' => 'fonction-de-perte',
                'name' => 'Fonction de perte',
                'cat' => 2, 'type' => 'technique', 'difficulty' => 'intermediate', 'icon' => '🎯',
                'definition' => "La fonction de perte (ou fonction de coût) mesure la performance d'un modèle en quantifiant l'écart entre ses prédictions et les valeurs réelles. Elle produit un « score d'erreur » que l'entraînement cherche à minimiser. Elle prend différentes formes selon la tâche : l'erreur quadratique moyenne (MSE) pour la régression (prédire un nombre), ou l'entropie croisée (cross-entropy) pour la classification (prédire une catégorie). Durant l'entraînement, la rétropropagation calcule comment chaque poids influence la valeur de la perte, puis la descente de gradient ajuste les poids pour la faire diminuer. La fonction de perte, la rétropropagation et la descente de gradient forment ainsi le trio fondamental de l'apprentissage supervisé : sans signal d'erreur clair à minimiser, un modèle n'aurait aucune direction pour s'améliorer.",
                'analogy' => "C'est comme la note d'un examen : plus le score d'erreur est bas, mieux le modèle a « répondu ». Tout l'entraînement consiste à réviser pour faire baisser cette note, examen après examen.",
                'example' => "Pour une cible de 5 et une prédiction de 4, l'erreur quadratique donne une perte de (4 − 5)² = 1. Si le modèle s'améliore et prédit 4,8, la perte tombe à (4,8 − 5)² = 0,04 — signe qu'il se rapproche de la bonne réponse.",
                'did_you_know' => "Le choix de la fonction de perte oriente complètement ce que le modèle apprend : deux modèles identiques entraînés avec des fonctions de perte différentes peuvent produire des comportements très différents.",
                'one_sentence_answer' => "La fonction de perte est le score d'erreur qui mesure l'écart entre les prédictions d'un modèle et la réalité, et que l'entraînement cherche à minimiser.",
                'faq' => [
                    ['question' => "Quelle différence entre MSE et entropie croisée ?", 'answer' => "La MSE (erreur quadratique moyenne) sert surtout à la régression — prédire un nombre — tandis que l'entropie croisée est utilisée pour la classification — prédire une catégorie."],
                    ['question' => "Pourquoi minimise-t-on la fonction de perte ?", 'answer' => "Parce qu'une perte plus faible signifie des prédictions plus proches de la vérité : la minimiser revient à rendre le modèle plus précis."],
                ],
                'sources' => [
                    ['label' => "Wikipédia — Fonction de coût", 'url' => "https://fr.wikipedia.org/wiki/Fonction_de_co%C3%BBt"],
                    ['label' => "IBM — Loss function", 'url' => "https://www.ibm.com/think/topics/loss-function"],
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
