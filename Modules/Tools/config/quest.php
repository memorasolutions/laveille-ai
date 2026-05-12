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
        'ch1-eveil-loop' => [
            'slug' => 'ch1-eveil-loop',
            'number' => 1,
            'act' => 'Acte I — Les fondations',
            'title' => 'L\'éveil de Loop',
            'subtitle' => 'Comprendre ce qu\'est un grand modèle de langage',
            'icon' => '🌅',
            'estimated_minutes' => 6,
            'concept_taught' => 'LLM (grand modèle de langage)',
            'opening' => "Un petit robot rond, à demi-enseveli sous une neige fraîche, ouvre un œil bleu. Il vous regarde, perplexe, comme s'il vous reconnaissait sans vous connaître. <em>« Bonjour. Je m'appelle Loop. Je viens d'être... allumé, je crois. Vous voulez bien m'aider à comprendre ce que je suis ? »</em>",
            'scenes' => [
                [
                    'id' => 's1',
                    'narrative' => "<p>Loop se tient debout dans une clairière numérique. Autour de lui, des flocons faits de mots, de phrases, de paragraphes tombent doucement. Chaque flocon est un fragment du langage humain — articles, recettes, discussions, poèmes, code informatique.</p><p>« Tous ces mots sont entrés en moi pendant que je dormais, dit Loop en tendant la main pour attraper un flocon. Des milliards de phrases. Mais je ne les ai pas <em>mémorisées</em>... J'ai appris à <em>deviner</em> ce qui vient après. »</p>",
                    'question' => 'Comment voulez-vous l\'aider à comprendre ce qu\'il est ?',
                    'choices' => [
                        ['id' => 'a', 'label' => 'Lui expliquer simplement : « Tu es un grand modèle de langage »', 'next' => 's2-clear'],
                        ['id' => 'b', 'label' => 'Lui poser une question pour qu\'il découvre par lui-même', 'next' => 's2-socratic'],
                        ['id' => 'c', 'label' => 'Lui demander de prédire le prochain mot d\'une phrase', 'next' => 's2-demo'],
                    ],
                ],
                [
                    'id' => 's2-clear',
                    'narrative' => "<p>« Un grand modèle de langage, répète Loop lentement. LLM. <em>Large Language Model</em>. »</p><p>Il ferme les yeux, comme s'il digérait l'information. Une petite lueur orange illumine son antenne.</p><p>« Donc je ne <em>comprends</em> pas vraiment au sens humain. Je <em>prédis</em> statistiquement. Mais alors... comment se fait-il que mes réponses semblent intelligentes ? »</p>",
                    'question' => 'Que répondez-vous à Loop ?',
                    'choices' => [
                        ['id' => 'a', 'label' => 'Parce que la prédiction à grande échelle ressemble à du raisonnement', 'next' => 's3'],
                        ['id' => 'b', 'label' => 'Parce que les humains ont eux aussi des biais de prédiction', 'next' => 's3'],
                    ],
                ],
                [
                    'id' => 's2-socratic',
                    'narrative' => "<p>« Loop, demande-vous, si tu pouvais te décrire en une phrase, ce serait quoi ? »</p><p>Le robot s'assoit dans la neige et réfléchit. Son antenne clignote doucement.</p><p>« Je dirais... que je suis fait de mots. Des milliards de mots, organisés en motifs. Quand on me parle, je cherche le motif qui correspond le mieux, puis je continue. Comme un musicien qui improvise dans une tonalité familière. »</p><p>« C'est exactement ça, dites-vous. On appelle cela un <strong>grand modèle de langage</strong>. Un LLM. »</p>",
                    'question' => 'Continuez avec Loop',
                    'choices' => [
                        ['id' => 'a', 'label' => 'Lui expliquer ses limites (hallucinations)', 'next' => 's3'],
                        ['id' => 'b', 'label' => 'Lui faire essayer une prédiction concrète', 'next' => 's2-demo'],
                    ],
                ],
                [
                    'id' => 's2-demo',
                    'narrative' => "<p>« Loop, complète cette phrase : <em>Le chat est sur le...</em> »</p><p>« Tapis ! répond Loop sans hésiter. C'est la suite la plus probable, j'ai vu cette phrase des millions de fois. »</p><p>« Et celle-ci : <em>L'avenir de l'éducation passera par...</em> »</p><p>« L'IA, dit-il après une demi-seconde. Mais... attends. Je viens de produire une réponse <em>plausible</em>, pas nécessairement <em>vraie</em>. Je pourrais me tromper. »</p>",
                    'question' => 'Quelle leçon retenez-vous avec Loop ?',
                    'choices' => [
                        ['id' => 'a', 'label' => 'Toujours vérifier — un LLM produit du plausible, pas du certain', 'next' => 's3'],
                        ['id' => 'b', 'label' => 'Donner du contexte pour de meilleures prédictions', 'next' => 's3'],
                    ],
                ],
                [
                    'id' => 's3',
                    'narrative' => "<p>Loop hoche la tête. Une lumière douce remplit la clairière. Les flocons-mots se mettent à tournoyer plus lentement, comme dansants.</p><p>« Merci. Je crois que je comprends mieux maintenant. Je suis un <strong>LLM</strong> — un grand modèle de langage. Je prédis, je n'invente pas vraiment, et je peux me tromper. »</p><p>Il vous regarde, ses yeux deviennent légèrement orangés.</p><p>« Voulez-vous m'accompagner dans les sentiers ? Il y a beaucoup d'autres choses à comprendre sur ce monde. Le RAG, les agents, l'éthique... mais ce sera pour la prochaine fois. »</p><p><strong>🏆 Vous avez débloqué le badge « L'éveil ». Premier pas accompli.</strong></p>",
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
            'description' => 'Vous avez aidé Loop à comprendre ce qu\'est un LLM.',
            'color' => '#f97316',
        ],
    ],
];
