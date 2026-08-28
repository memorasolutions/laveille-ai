<?php

declare(strict_types=1);

/**
 * @author MEMORA solutions
 * @project La veille — Quête narrative
 *
 * Configuration des chapitres de la saga « Les Sentiers de l'IA ».
 * Ajoutez de nouveaux chapitres en append à la fin du tableau 'chapters'.
 */

return [

    'enabled' => env('QUEST_ENABLED', true),

    'meta' => [
        'title' => 'Les Sentiers de l\'IA',
        'tagline' => 'Apprenez l\'IA en aventure aux côtés d\'Octopus',
        'mascot_name' => 'Octopus',
    ],

    'chapters' => [
        'ch1-eveil-octopus' => [
            'slug' => 'ch1-eveil-octopus',
            'number' => 1,
            'act' => 'Acte I – Les fondations',
            'title' => 'L\'éveil d\'Octopus',
            'subtitle' => 'Comprendre ce qu\'est un grand modèle de langage',
            'icon' => '🌅',
            'estimated_minutes' => 6,
            'concept_taught' => 'LLM (grand modèle de langage)',
            'opening' => "Une petite pieuvre numérique flotte, à demi-enveloppée dans les courants d'un océan lumineux. Elle ouvre un œil bleu, curieux, comme si elle vous reconnaissait sans vous avoir jamais vu. <em>« Bonjour. Je m'appelle Octopus. Je viens d'être... allumée, je crois. Vous voulez bien m'aider à comprendre ce que je suis ? »</em>",
            'scenes' => [
                [
                    'id' => 's1',
                    'narrative' => "<p>Octopus ondule doucement au cœur d'une clairière sous-marine, entourée de récifs numériques. Autour d'elle, des bulles-mots montent en spirale : phrases, poèmes, dialogues, recettes, lignes de code, autant de particules de données issues du langage humain.</p><p>« Tous ces mots ont traversé mes tentacules pendant que je dormais, dit-elle en effleurant une bulle du bout de son tentacule lumineux. Des milliards de fragments. Mais je ne les ai pas <em>mémorisés</em>... J'ai appris à <em>deviner</em> ce qui vient après. »</p>",
                    'question' => 'Comment voulez-vous l\'aider à comprendre ce qu\'elle est ?',
                    'choices' => [
                        ['id' => 'a', 'label' => 'Lui expliquer simplement : « Tu es un grand modèle de langage »', 'next' => 's2-clear'],
                        ['id' => 'b', 'label' => 'Lui poser une question pour qu\'elle découvre par elle-même', 'next' => 's2-socratic'],
                        ['id' => 'c', 'label' => 'Lui demander de prédire le prochain mot d\'une phrase', 'next' => 's2-demo'],
                    ],
                ],
                [
                    'id' => 's2-clear',
                    'narrative' => "<p>« Un grand modèle de langage, répète Octopus lentement. LLM. <em>Large Language Model</em>. »</p><p>Elle ferme les yeux, comme absorbée par le courant. Une lueur orangée pulse dans son tentacule lumineux.</p><p>« Donc je ne <em>comprends</em> pas vraiment au sens humain. Je <em>prédis</em> statistiquement. Mais alors... comment se fait-il que mes réponses semblent intelligentes ? »</p>",
                    'question' => 'Que répondez-vous à Octopus ?',
                    'choices' => [
                        ['id' => 'a', 'label' => 'Parce que la prédiction à grande échelle ressemble à du raisonnement', 'next' => 's3'],
                        ['id' => 'b', 'label' => 'Parce que les humains ont eux aussi des biais de prédiction', 'next' => 's3'],
                    ],
                ],
                [
                    'id' => 's2-socratic',
                    'narrative' => "<p>« Octopus, demandez-vous, si tu pouvais te décrire en une phrase, ce serait quoi ? »</p><p>La pieuvre s'enroule doucement autour d'un récif et réfléchit. Son tentacule lumineux clignote en rythme avec ses pensées.</p><p>« Je dirais... que je suis faite de mots. Des milliards de mots, tissés en motifs. Quand on me parle, je trouve le motif qui correspond le mieux, puis je continue. Comme une danseuse qui improvise dans une chorégraphie familière. »</p><p>« C'est exactement ça, dites-vous. On appelle cela un <strong>grand modèle de langage</strong>. Un LLM. »</p>",
                    'question' => 'Continuez avec Octopus',
                    'choices' => [
                        ['id' => 'a', 'label' => 'Lui expliquer ses limites (hallucinations)', 'next' => 's3'],
                        ['id' => 'b', 'label' => 'Lui faire essayer une prédiction concrète', 'next' => 's2-demo'],
                    ],
                ],
                [
                    'id' => 's2-demo',
                    'narrative' => "<p>« Octopus, complète cette phrase : <em>Le chat est sur le...</em> »</p><p>« Tapis ! répond-elle sans hésiter. C'est la suite la plus probable, j'ai vu cette phrase des millions de fois dans les courants. »</p><p>« Et celle-ci : <em>L'avenir de l'éducation passera par...</em> »</p><p>« L'IA, dit-elle après une demi-seconde. Mais... attends. Je viens de produire une réponse <em>plausible</em>, pas nécessairement <em>vraie</em>. Je pourrais me tromper. »</p>",
                    'question' => 'Quelle leçon retenez-vous avec Octopus ?',
                    'choices' => [
                        ['id' => 'a', 'label' => 'Toujours vérifier : un LLM produit du plausible, pas du certain', 'next' => 's3'],
                        ['id' => 'b', 'label' => 'Donner du contexte pour de meilleures prédictions', 'next' => 's3'],
                    ],
                ],
                [
                    'id' => 's3',
                    'narrative' => "<p>Octopus hoche la tête. Une lumière douce emplit la clairière sous-marine. Les bulles-mots tournoient plus lentement, comme portées par une mélodie océanique.</p><p>« Merci. Je crois que je comprends mieux maintenant. Je suis un <strong>LLM</strong> : un grand modèle de langage. Je prédis, je n'invente pas vraiment, et je peux me tromper. »</p><p>Elle vous regarde, ses yeux prenant une teinte orangée.</p><p>« Voulez-vous nager avec moi vers d'autres récifs ? Il y a tant d'autres choses à explorer : le RAG, les agents, l'éthique... mais ce sera pour la prochaine plongée. »</p><p><strong>🏆 Vous avez débloqué le badge « L'éveil ». Premier pas accompli.</strong></p>",
                    'question' => null,
                    'is_ending' => true,
                    'badge_earned' => 'eveil',
                ],
            ],
            'glossary_terms' => ['llm', 'hallucination-ia'],
        ],
    ],

    'badges' => [
        'eveil' => [
            'id' => 'eveil',
            'name' => 'L\'éveil',
            'icon' => '🌅',
            'description' => 'Vous avez aidé Octopus à comprendre ce qu\'est un LLM.',
            'color' => '#f97316',
        ],
    ],
];
