<?php

use Illuminate\Database\Migrations\Migration;
use Modules\Dictionary\Models\Term;

/**
 * Ajout de 3 termes « boucle d'entraînement » au glossaire (batch #13, cat 2 « Concepts fondamentaux ») :
 * Époque (epoch), Batch (lot), Itération. Images via le compte Gemini de l'utilisateur.
 * Standard complet, sources vérifiées 200. Anti-doublon par slug. RÉVERSIBLE (down()).
 */
return new class extends Migration
{
    private function terms(): array
    {
        return [
            [
                'slug' => 'epoque',
                'name' => 'Époque (epoch)',
                'cat' => 2, 'type' => 'concept', 'difficulty' => 'intermediate', 'icon' => '🔄',
                'definition' => "Une époque (epoch) désigne un passage complet de l'algorithme d'entraînement sur l'intégralité du jeu de données d'apprentissage : à la fin d'une époque, chaque exemple d'entraînement a été « vu » exactement une fois par le modèle. L'entraînement d'un réseau de neurones se déroule presque toujours sur plusieurs époques (souvent des dizaines, voire des centaines) : à chaque passage, le modèle ajuste un peu plus ses paramètres pour réduire son erreur. Comme un jeu de données est rarement traité d'un seul bloc (faute de mémoire), chaque époque est découpée en lots (batches) ; le nombre de mises à jour des poids par époque vaut le nombre d'exemples divisé par la taille du lot. Le nombre d'époques est un hyperparamètre clé : trop peu, le modèle n'a pas assez appris (sous-apprentissage) ; trop, il risque de mémoriser les données au lieu de généraliser (surapprentissage). On surveille donc une courbe de validation pour arrêter l'entraînement au bon moment (early stopping). L'époque est l'unité « macro » de l'entraînement, à distinguer de l'itération (unité « micro », une mise à jour des poids).",
                'analogy' => "C'est comme réviser un manuel complet : une époque, c'est avoir lu le livre en entier une fois. On le relit plusieurs fois (plusieurs époques) pour mieux retenir — mais le relire trop de fois ne sert qu'à mémoriser par cœur sans comprendre.",
                'example' => "Avec 10 000 images d'entraînement, une époque correspond au moment où le modèle a traité les 10 000 images une fois. Entraîner sur 30 époques signifie que chaque image aura été vue 30 fois.",
                'did_you_know' => "Plus d'époques n'est pas toujours mieux : au-delà d'un certain point, le modèle cesse de généraliser et se met à « mémoriser » les données d'entraînement (surapprentissage). C'est pourquoi on arrête souvent l'entraînement dès que l'erreur de validation cesse de baisser.",
                'one_sentence_answer' => "Une époque est un passage complet de l'entraînement sur tout le jeu de données, où chaque exemple a été vu une fois.",
                'faq' => [
                    ['question' => "Combien d'époques faut-il pour entraîner un modèle ?", 'answer' => "Cela dépend des données et du modèle : de quelques-unes à des centaines. On choisit le nombre en surveillant l'erreur de validation et on arrête quand elle cesse de s'améliorer (early stopping)."],
                    ['question' => "Quelle différence entre une époque et une itération ?", 'answer' => "Une époque est un passage sur TOUT le jeu de données ; une itération est une seule mise à jour des poids, après le traitement d'un lot. Une époque contient donc plusieurs itérations."],
                ],
                'sources' => [
                    ['label' => "Google — Machine Learning Glossary (epoch)", 'url' => "https://developers.google.com/machine-learning/glossary"],
                    ['label' => "Wikipédia — Stochastic gradient descent", 'url' => "https://en.wikipedia.org/wiki/Stochastic_gradient_descent"],
                ],
            ],
            [
                'slug' => 'batch',
                'name' => 'Batch (lot d\'entraînement)',
                'cat' => 2, 'type' => 'concept', 'difficulty' => 'intermediate', 'icon' => '📦',
                'definition' => "Un batch (lot) est le sous-ensemble d'exemples d'entraînement que le modèle traite en une seule passe — une propagation avant puis arrière — avant de mettre à jour ses paramètres. La taille du lot (batch size) est le nombre d'exemples qu'il contient ; c'est un hyperparamètre courant (32, 64, 128…). Plutôt que de calculer le gradient sur tout le jeu de données (coûteux) ou sur un seul exemple à la fois (très bruité), on adopte le plus souvent une voie intermédiaire, la descente de gradient par mini-lots (mini-batch gradient descent), qui calcule le gradient sur un petit lot. La taille du lot a deux effets majeurs. Sur la mémoire : un grand lot exige davantage de mémoire vive (RAM/VRAM du GPU). Sur l'apprentissage : un petit lot donne un gradient plus « bruité » mais des mises à jour plus fréquentes (souvent meilleure généralisation) ; un grand lot donne un gradient plus stable mais moins de mises à jour par époque. Choisir la taille du lot est donc un compromis entre vitesse, stabilité, consommation mémoire et qualité de généralisation. Le batch est l'unité de travail concrète qui relie l'époque (passage complet) et l'itération (une mise à jour).",
                'analogy' => "Plutôt que d'avaler tout un buffet d'un coup (impossible) ou de manger grain de riz par grain de riz (interminable), on mange assiette par assiette : chaque assiette est un lot, et sa taille détermine le rythme des repas.",
                'example' => "Avec 1 000 exemples et une taille de lot de 100, le modèle traite 10 lots de 100 exemples par époque, en mettant à jour ses poids après chaque lot — soit 10 mises à jour par époque.",
                'did_you_know' => "La taille du lot influence la qualité du modèle, pas seulement la vitesse : des lots plus petits introduisent un « bruit » dans le gradient qui aide souvent le modèle à mieux généraliser, tandis que de très grands lots peuvent converger vers des solutions moins robustes.",
                'one_sentence_answer' => "Un batch (lot) est le groupe d'exemples traité en une passe avant la mise à jour des poids ; sa taille (batch size) est un hyperparamètre clé.",
                'faq' => [
                    ['question' => "Qu'est-ce que la descente de gradient par mini-lots ?", 'answer' => "C'est la méthode la plus courante : on calcule le gradient sur un petit lot d'exemples (ni tout le jeu de données, ni un seul exemple), pour combiner efficacité de calcul et stabilité raisonnable des mises à jour."],
                    ['question' => "Faut-il choisir une grande ou une petite taille de lot ?", 'answer' => "C'est un compromis : un grand lot est plus stable et exploite mieux le GPU mais consomme plus de mémoire et généralise parfois moins bien ; un petit lot est plus bruité mais souvent meilleur pour la généralisation."],
                ],
                'sources' => [
                    ['label' => "Google — Machine Learning Glossary (batch size)", 'url' => "https://developers.google.com/machine-learning/glossary"],
                    ['label' => "Wikipédia — Stochastic gradient descent", 'url' => "https://en.wikipedia.org/wiki/Stochastic_gradient_descent"],
                ],
            ],
            [
                'slug' => 'iteration',
                'name' => 'Itération (entraînement)',
                'cat' => 2, 'type' => 'concept', 'difficulty' => 'intermediate', 'icon' => '🔁',
                'definition' => "En apprentissage automatique, une itération désigne une seule mise à jour des paramètres (poids) du modèle, effectuée après le traitement d'un lot (batch) d'exemples : une propagation avant pour calculer la prédiction et l'erreur, une propagation arrière (rétropropagation) pour calculer le gradient, puis l'ajustement des poids. C'est l'unité élémentaire « micro » de l'entraînement, à distinguer de l'époque (unité « macro », un passage complet sur le jeu de données). Les trois notions sont mathématiquement liées : pour N exemples et une taille de lot B, une époque compte ⌈N/B⌉ itérations, et un entraînement de E époques en compte environ E × N/B. Par exemple, 3 000 exemples avec des lots de 32 donnent environ 94 itérations par époque, soit 940 itérations sur 10 époques. Cette distinction est importante en pratique : les courbes d'apprentissage, les journaux d'entraînement et certains planificateurs de taux d'apprentissage sont souvent exprimés en nombre d'itérations (ou « steps ») plutôt qu'en époques, car l'itération correspond exactement au moment où le modèle apprend réellement quelque chose.",
                'analogy' => "Si l'époque est la lecture complète d'un manuel, l'itération est l'étude d'une seule page : c'est à chaque page (chaque lot) que l'on ajuste réellement sa compréhension, et il faut beaucoup de pages pour finir le livre une fois.",
                'example' => "Avec 3 000 exemples et des lots de 32, le modèle réalise environ 94 itérations pour boucler une époque — c'est-à-dire 94 mises à jour de ses poids, une par lot traité.",
                'did_you_know' => "Beaucoup d'outils d'entraînement raisonnent en « steps » (itérations) plutôt qu'en époques, car c'est l'itération — et non l'époque — qui correspond au moment précis où les poids du modèle changent.",
                'one_sentence_answer' => "Une itération est une seule mise à jour des poids du modèle, effectuée après le traitement d'un lot d'exemples.",
                'faq' => [
                    ['question' => "Combien d'itérations dans une époque ?", 'answer' => "Le nombre d'exemples divisé par la taille du lot, arrondi au supérieur : ⌈N/B⌉. Par exemple, 1 000 exemples avec des lots de 100 donnent 10 itérations par époque."],
                    ['question' => "Itération et époque, est-ce la même chose ?", 'answer' => "Non : une itération est une mise à jour des poids (après un lot) ; une époque est un passage complet sur le jeu de données et contient généralement de nombreuses itérations."],
                ],
                'sources' => [
                    ['label' => "Google — Machine Learning Glossary (iteration)", 'url' => "https://developers.google.com/machine-learning/glossary"],
                    ['label' => "Wikipédia — Stochastic gradient descent", 'url' => "https://en.wikipedia.org/wiki/Stochastic_gradient_descent"],
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
