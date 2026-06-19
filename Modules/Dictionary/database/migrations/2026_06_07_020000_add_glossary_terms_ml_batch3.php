<?php

use Illuminate\Database\Migrations\Migration;
use Modules\Dictionary\Models\Term;

/**
 * Ajout de 3 termes fondamentaux ML/réseaux au glossaire (batch P0 #3) :
 * Sous-apprentissage, Généralisation, Fonction d'activation (catégorie 2 « Concepts fondamentaux »).
 * Même standard/structure que batch1-2. Anti-doublon par slug. RÉVERSIBLE via down().
 */
return new class extends Migration
{
    private function terms(): array
    {
        return [
            [
                'slug' => 'sous-apprentissage',
                'name' => 'Sous-apprentissage',
                'cat' => 2, 'type' => 'concept', 'difficulty' => 'intermediate', 'icon' => '🫤',
                'definition' => "Le sous-apprentissage (underfitting) survient lorsqu'un modèle est trop simple pour capturer la structure réelle des données : il ne parvient pas à apprendre les relations importantes et obtient de mauvaises performances même sur les données d'entraînement. C'est le défaut opposé du surapprentissage : alors qu'un modèle en surapprentissage « mémorise » ses exemples, un modèle en sous-apprentissage n'apprend pas assez. Le signe caractéristique est une erreur élevée à la fois sur le jeu d'entraînement et sur le jeu de test (le surapprentissage, lui, donne une faible erreur en entraînement mais une forte erreur en test). Les causes typiques : un modèle pas assez complexe (par exemple une droite pour des données courbes), trop peu de variables (features) pertinentes, ou un entraînement interrompu trop tôt. Les remèdes : choisir un modèle plus expressif, ajouter des variables informatives, réduire la régularisation, ou prolonger l'entraînement. Trouver le bon équilibre entre sous-apprentissage et surapprentissage est au cœur du compromis biais-variance.",
                'analogy' => "C'est comme réviser un examen en ne retenant qu'une règle générale trop vague : on rate aussi bien les exercices du cours que les questions de l'examen, faute d'avoir vraiment compris la matière.",
                'example' => "On tente de prédire le prix de maisons avec une simple droite alors que le vrai lien est courbe : le modèle se trompe d'environ 30 % autant sur les maisons d'entraînement que sur de nouvelles, signe qu'il est trop simple.",
                'did_you_know' => "Un modèle en sous-apprentissage peut donner l'illusion d'être « stable » parce qu'il se trompe de façon constante ; c'est pourtant le signe qu'il n'a presque rien appris.",
                'one_sentence_answer' => "Le sous-apprentissage survient quand un modèle est trop simple pour apprendre la structure des données, d'où de mauvaises performances même à l'entraînement.",
                'faq' => [
                    ['question' => "Comment reconnaître le sous-apprentissage ?", 'answer' => "Par une erreur élevée à la fois sur les données d'entraînement et de test ; le surapprentissage, lui, donne une faible erreur en entraînement mais élevée en test."],
                    ['question' => "Comment y remédier ?", 'answer' => "Utiliser un modèle plus complexe, ajouter des variables pertinentes, réduire la régularisation ou entraîner plus longtemps."],
                ],
                'sources' => [
                    ['label' => "Wikipédia — Surapprentissage (et sous-apprentissage)", 'url' => "https://fr.wikipedia.org/wiki/Surapprentissage"],
                    ['label' => "IBM — Underfitting", 'url' => "https://www.ibm.com/think/topics/underfitting"],
                ],
            ],
            [
                'slug' => 'generalisation',
                'name' => 'Généralisation',
                'cat' => 2, 'type' => 'concept', 'difficulty' => 'intermediate', 'icon' => '🧩',
                'definition' => "La généralisation désigne la capacité d'un modèle d'apprentissage automatique à bien performer sur de nouvelles données, jamais vues pendant l'entraînement. C'est le but ultime de l'apprentissage : on ne veut pas un modèle qui récite par cœur ses exemples, mais un modèle qui a saisi des régularités transposables à des cas inédits. Un modèle qui généralise bien obtient des performances comparables sur ses données d'entraînement et sur un jeu de test indépendant. À l'inverse, un fort écart entre les deux signale un surapprentissage (le modèle a « mémorisé » au lieu d'apprendre) ; de mauvaises performances partout signalent un sous-apprentissage. La généralisation est étroitement liée au compromis biais-variance et se favorise par des techniques comme la régularisation, l'augmentation de données ou la validation croisée. C'est elle qui distingue un modèle réellement utile d'un modèle qui ne fonctionne qu'en laboratoire.",
                'analogy' => "C'est comme un élève qui a vraiment compris une matière : il sait répondre à des questions nouvelles à l'examen, et pas seulement réciter les exercices corrigés en classe.",
                'example' => "Un modèle entraîné à reconnaître des chats sur 10 000 photos est jugé sur sa généralisation : s'il identifie correctement des chats sur des photos jamais vues, avec une précision proche de celle de l'entraînement, il généralise bien.",
                'did_you_know' => "Augmenter indéfiniment la précision sur les données d'entraînement peut nuire à la généralisation : passé un certain point, le modèle se met à apprendre le « bruit » plutôt que le signal.",
                'one_sentence_answer' => "La généralisation est la capacité d'un modèle à rester performant sur des données nouvelles, jamais rencontrées pendant l'entraînement.",
                'faq' => [
                    ['question' => "Pourquoi la généralisation est-elle si importante ?", 'answer' => "Parce qu'un modèle n'a de valeur que s'il fonctionne sur des données réelles inédites, pas seulement sur ses exemples d'entraînement."],
                    ['question' => "Quel lien avec le surapprentissage ?", 'answer' => "Le surapprentissage est précisément un défaut de généralisation : le modèle excelle sur l'entraînement mais échoue sur les nouvelles données."],
                ],
                'sources' => [
                    ['label' => "Google — Machine Learning Crash Course : Generalization", 'url' => "https://developers.google.com/machine-learning/crash-course/overfitting/generalization"],
                    ['label' => "Wikipédia — Dilemme biais-variance", 'url' => "https://fr.wikipedia.org/wiki/Dilemme_biais-variance"],
                ],
            ],
            [
                'slug' => 'fonction-d-activation',
                'name' => "Fonction d'activation",
                'cat' => 2, 'type' => 'technique', 'difficulty' => 'advanced', 'icon' => '⚡',
                'definition' => "Une fonction d'activation est une fonction mathématique appliquée à la sortie de chaque neurone d'un réseau de neurones, afin d'introduire de la non-linéarité. Son rôle est essentiel : sans fonction d'activation, empiler des couches de neurones reviendrait à une simple combinaison linéaire, et le réseau entier se réduirait à une régression linéaire incapable d'apprendre des relations complexes. Grâce à elle, le réseau peut modéliser des frontières de décision courbes et des motifs riches. Les fonctions les plus courantes sont la ReLU (Rectified Linear Unit, qui renvoie max(0, x)), très populaire en apprentissage profond pour sa simplicité et son efficacité ; la sigmoïde et la tangente hyperbolique (tanh), qui « écrasent » les valeurs dans un intervalle borné ; et la softmax, utilisée en sortie des classifieurs pour transformer des scores en probabilités. Le choix de la fonction d'activation influence la vitesse d'entraînement et la capacité du réseau à apprendre.",
                'analogy' => "C'est comme un interrupteur intelligent placé à la sortie de chaque neurone : il décide quelle part du signal laisser passer, ce qui permet au réseau de réagir de façon nuancée plutôt que tout ou rien.",
                'example' => "Avec la fonction ReLU, un neurone qui calcule la valeur −3 renvoie 0 (le signal est coupé), tandis qu'une valeur de 2 est laissée telle quelle ; cette simple règle suffit à donner au réseau sa puissance non linéaire.",
                'did_you_know' => "La ReLU, aujourd'hui omniprésente, est d'une simplicité déconcertante — renvoyer max(0, x) — et c'est en partie cette simplicité qui a permis d'entraîner des réseaux beaucoup plus profonds à partir des années 2010.",
                'one_sentence_answer' => "Une fonction d'activation introduit de la non-linéarité à la sortie d'un neurone, sans quoi un réseau de neurones ne serait qu'une régression linéaire.",
                'faq' => [
                    ['question' => "Pourquoi une fonction d'activation est-elle indispensable ?", 'answer' => "Sans elle, additionner des couches de neurones resterait une opération linéaire ; le réseau ne pourrait pas apprendre de relations complexes ou courbes."],
                    ['question' => "Quelle fonction d'activation choisir ?", 'answer' => "ReLU est le choix par défaut en apprentissage profond ; la softmax sert en sortie de classification, la sigmoïde et la tanh dans des cas spécifiques."],
                ],
                'sources' => [
                    ['label' => "Wikipédia — Fonction d'activation", 'url' => "https://fr.wikipedia.org/wiki/Fonction_d%27activation"],
                    ['label' => "IBM — Activation function", 'url' => "https://www.ibm.com/think/topics/activation-function"],
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
