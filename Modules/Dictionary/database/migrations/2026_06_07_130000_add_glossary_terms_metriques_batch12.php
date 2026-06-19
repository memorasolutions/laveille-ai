<?php

use Illuminate\Database\Migrations\Migration;
use Modules\Dictionary\Models\Term;

/**
 * Ajout de 3 termes « calcul & métriques » au glossaire (batch #12) :
 * CUDA (cat 3 « Acronymes et sigles »), F1-score (cat 6 « Données et traitement »), Perplexité (cat 6).
 * Images via le compte Gemini de l'utilisateur. Standard complet, sources vérifiées 200.
 * Anti-doublon par slug. RÉVERSIBLE (down()).
 */
return new class extends Migration
{
    private function terms(): array
    {
        return [
            [
                'slug' => 'cuda',
                'name' => 'CUDA (Compute Unified Device Architecture)',
                'cat' => 3, 'type' => 'technique', 'difficulty' => 'advanced', 'icon' => '⚡',
                'definition' => "CUDA (Compute Unified Device Architecture) est la plateforme de calcul parallèle et le modèle de programmation créés par NVIDIA en 2007, qui permettent d'utiliser un processeur graphique (GPU) pour des calculs généraux, et non plus seulement pour l'affichage d'images. Un GPU contient des milliers de petits cœurs capables d'exécuter la même opération sur de nombreuses données en parallèle ; CUDA donne aux développeurs les outils (extensions des langages C/C++, bibliothèques comme cuDNN, compilateur) pour répartir un calcul sur ces cœurs. C'est cette capacité de calcul massivement parallèle qui a rendu possible l'essor de l'apprentissage profond : entraîner un réseau de neurones revient à effectuer d'énormes quantités de multiplications de matrices, une tâche idéale pour un GPU. La quasi-totalité des bibliothèques d'IA modernes (PyTorch, TensorFlow) s'appuient sur CUDA pour accélérer l'entraînement et l'inférence sur les cartes NVIDIA. CUDA est aujourd'hui un quasi-standard de fait : sa domination explique en partie la position centrale de NVIDIA dans l'écosystème de l'IA, et l'existence d'alternatives (ROCm d'AMD, OpenCL) qui peinent à rivaliser avec son écosystème logiciel.",
                'analogy' => "Un processeur classique (CPU), c'est quelques ouvriers très polyvalents ; un GPU avec CUDA, c'est une armée de milliers d'ouvriers spécialisés qui font tous le même geste simple en même temps — parfait quand il y a une montagne de petits calculs identiques à abattre.",
                'example' => "Pour entraîner un réseau de neurones, on multiplie d'immenses matrices des millions de fois. Avec CUDA, ces multiplications sont réparties sur les milliers de cœurs d'un GPU NVIDIA, réduisant un entraînement de plusieurs semaines sur CPU à quelques heures.",
                'did_you_know' => "CUDA explique en grande partie pourquoi NVIDIA domine l'IA : ce n'est pas seulement le matériel, mais l'écosystème logiciel bâti depuis 2007 qui crée un « verrouillage » difficile à concurrencer pour AMD ou Intel.",
                'one_sentence_answer' => "CUDA est la plateforme de NVIDIA qui permet d'utiliser un GPU pour du calcul parallèle général, socle de l'accélération de l'apprentissage profond.",
                'faq' => [
                    ['question' => "Pourquoi CUDA est-il si important pour l'IA ?", 'answer' => "Parce que l'entraînement des réseaux de neurones repose sur d'énormes calculs matriciels parallélisables ; CUDA permet de les exécuter sur les milliers de cœurs d'un GPU, accélérant l'entraînement d'un facteur considérable."],
                    ['question' => "CUDA fonctionne-t-il sur d'autres cartes que NVIDIA ?", 'answer' => "Non : CUDA est propriétaire et réservé aux GPU NVIDIA. Des alternatives ouvertes ou concurrentes (OpenCL, ROCm d'AMD) existent, mais n'offrent pas le même écosystème logiciel mature."],
                ],
                'sources' => [
                    ['label' => "NVIDIA — CUDA Zone", 'url' => "https://developer.nvidia.com/cuda-zone"],
                    ['label' => "Wikipédia — CUDA", 'url' => "https://en.wikipedia.org/wiki/CUDA"],
                ],
            ],
            [
                'slug' => 'f1-score',
                'name' => 'F1-score (score F1)',
                'cat' => 6, 'type' => 'concept', 'difficulty' => 'intermediate', 'icon' => '📊',
                'definition' => "Le F1-score (score F1) est une métrique d'évaluation des modèles de classification qui résume en un seul nombre l'équilibre entre la précision et le rappel. Il vaut entre 0 (mauvais) et 1 (parfait). La précision (precision) mesure, parmi les cas que le modèle a déclarés positifs, la proportion qui l'était vraiment ; le rappel (recall) mesure, parmi tous les cas réellement positifs, la proportion que le modèle a su détecter. Ces deux mesures sont souvent en tension : améliorer l'une dégrade parfois l'autre. Le F1-score les combine par leur moyenne harmonique, selon la formule F1 = 2 × (précision × rappel) ÷ (précision + rappel). Le choix de la moyenne harmonique n'est pas anodin : elle est plus sévère que la moyenne arithmétique et tire le résultat vers la plus faible des deux valeurs. Un modèle qui aurait une excellente précision mais un rappel catastrophique (ou l'inverse) obtient donc un F1 médiocre. C'est pourquoi le F1-score est particulièrement utile sur des données déséquilibrées (par exemple détecter une maladie rare), où le simple taux de bonnes réponses (accuracy) serait trompeur.",
                'analogy' => "C'est comme noter un détecteur de pourriels sur deux qualités à la fois : ne pas jeter de vrais courriels à la poubelle (précision) ET ne pas laisser passer de pourriels (rappel). Le F1-score donne une note unique qui chute dès que l'une des deux est faible.",
                'example' => "Un test médical déclare 100 patients positifs ; 80 le sont vraiment (précision 80 %), mais il rate la moitié des vrais malades (rappel 50 %). Son F1-score est d'environ 0,62 — bien plus parlant qu'une accuracy qui masquerait les cas ratés.",
                'did_you_know' => "Le F1-score utilise la moyenne harmonique, pas la moyenne arithmétique : c'est volontaire. Elle pénalise les déséquilibres, de sorte qu'un modèle ne peut pas « tricher » en excellant sur une seule des deux mesures.",
                'one_sentence_answer' => "Le F1-score est la moyenne harmonique de la précision et du rappel, une note unique entre 0 et 1 mesurant la qualité d'une classification, surtout sur données déséquilibrées.",
                'faq' => [
                    ['question' => "Pourquoi ne pas se contenter de l'accuracy ?", 'answer' => "Sur des données déséquilibrées (ex. 1 % de cas positifs), un modèle qui répond toujours « négatif » atteint 99 % d'accuracy tout en étant inutile ; le F1-score, lui, révèle qu'il ne détecte rien."],
                    ['question' => "Que signifie un F1-score de 1 ?", 'answer' => "Une précision et un rappel parfaits : le modèle détecte tous les cas positifs sans aucune fausse alerte. En pratique on s'en approche rarement ; on compare plutôt les F1 de plusieurs modèles."],
                ],
                'sources' => [
                    ['label' => "Wikipédia — F-score", 'url' => "https://en.wikipedia.org/wiki/F-score"],
                    ['label' => "scikit-learn — sklearn.metrics.f1_score", 'url' => "https://scikit-learn.org/stable/modules/generated/sklearn.metrics.f1_score.html"],
                ],
            ],
            [
                'slug' => 'perplexite-metrique',
                'name' => 'Perplexité (perplexity)',
                'cat' => 6, 'type' => 'concept', 'difficulty' => 'advanced', 'icon' => '🎲',
                'definition' => "La perplexité (perplexity) est une métrique qui mesure à quel point un modèle de langage est « surpris » par un texte : plus elle est basse, mieux le modèle prédit ce texte. Intuitivement, elle correspond au nombre effectif de choix que le modèle hésite à faire à chaque mot ; une perplexité de 10 signifie qu'en moyenne, le modèle se comporte comme s'il hésitait entre dix possibilités également plausibles pour le mot suivant. Mathématiquement, la perplexité est l'exponentielle de l'entropie croisée moyenne par token, c'est-à-dire PPL = exp(− (1/N) Σ log P(mₙ | mots précédents)). Minimiser la perplexité revient donc à maximiser la probabilité que le modèle attribue au vrai texte. C'est une métrique « intrinsèque » : elle évalue la qualité de prédiction du modèle sur un corpus de test, indépendamment d'une tâche applicative précise, et sert surtout pendant le pré-entraînement et la validation. Attention : la comparaison n'a de sens qu'à corpus et tokenisation identiques, car le découpage en tokens influence la valeur. La perplexité ne mesure ni la véracité ni l'utilité d'une réponse — un modèle peut afficher une faible perplexité tout en produisant des textes faux.",
                'analogy' => "C'est comme mesurer l'aisance d'un lecteur à deviner le mot suivant d'un livre : s'il hésite entre dix mots à chaque fois, il est « perplexe » (perplexité 10) ; s'il devine presque toujours juste, sa perplexité est proche de 1.",
                'example' => "On teste deux modèles sur le même texte avec la même tokenisation : le premier obtient une perplexité de 25, le second de 12. Le second prédit mieux le texte — il lui attribue une probabilité plus élevée — et sera jugé statistiquement meilleur sur cette distribution.",
                'did_you_know' => "La perplexité est l'exponentielle de l'entropie croisée : réduire l'une réduit l'autre. C'est pourquoi minimiser l'entropie croisée pendant l'entraînement revient exactement à rendre le modèle moins « perplexe » devant le texte.",
                'one_sentence_answer' => "La perplexité mesure à quel point un modèle de langage est surpris par un texte ; plus elle est basse, meilleure est sa capacité à le prédire.",
                'faq' => [
                    ['question' => "Une perplexité basse garantit-elle un bon modèle ?", 'answer' => "Pour la prédiction de texte brut, oui ; mais elle ne mesure ni la véracité ni l'utilité des réponses. Un modèle peut afficher une faible perplexité et produire des contenus faux ou peu utiles."],
                    ['question' => "Peut-on comparer la perplexité de deux modèles différents ?", 'answer' => "Seulement à corpus de test ET tokenisation identiques : le découpage en tokens influence la valeur, donc deux modèles aux tokeniseurs différents ne sont pas directement comparables."],
                ],
                'sources' => [
                    ['label' => "Hugging Face — Perplexity of fixed-length models", 'url' => "https://huggingface.co/docs/transformers/perplexity"],
                    ['label' => "Wikipédia — Perplexity", 'url' => "https://en.wikipedia.org/wiki/Perplexity"],
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
