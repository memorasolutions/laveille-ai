<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Modules\Dictionary\Models\Category;
use Modules\Dictionary\Models\Term;

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Ajout de deux termes au glossaire (2026-09-13), demandes par le fondateur : GPT-6 Astra
 * et MolmoAct2.
 *
 * ANTI-DOUBLON, fait AVANT redaction et contre la base de PRODUCTION (544 termes, 530
 * publies), par FAMILLE de motifs et non par motif unique - sur le nom, le slug ET les
 * alias, parce qu une notion peut etre couverte par un alias sans qu aucun slug ne la
 * porte :
 *   famille « gpt|astra|openai|grand modele de langage|llm » -> 17 fiches voisines
 *     (chatgpt, gpt, openai, o1, o3, codex, sora, llm, modele-de-langage...), AUCUNE ne
 *     couvre GPT-6 Astra. A noter : la fiche chatgpt porte deja les alias GPT-4, GPT-5 et
 *     GPT-5.2, mais pas GPT-6 - aucun conflit.
 *   famille « molmo|vla|vision-langage|robot|allen|ai2|embodied|multimodal » -> 6 fiches
 *     voisines (embodied-ai, ia-multimodale, modele-multimodal, chatbot, automatisation,
 *     tesla), AUCUNE ne couvre MolmoAct2.
 * Les deux termes sont donc des notions NOUVELLES, pas des synonymes de fiches existantes.
 * Convention deja etablie par le glossaire : une fiche par modele (o1, o3, whisper, sora,
 * codex existent chacun separement) - ces deux ajouts la suivent.
 *
 * SOURCES - chaque URL verifiee par requete reelle AVANT redaction, et le titre servi
 * controle pour s assurer que la page rend bien le document annonce :
 *   deploymentsafety.openai.com/gpt-6-astra -> 200, « GPT-6 Astra System Card - OpenAI
 *     Deployment Safety Hub ». A RETENIR : openai.com lui-meme repond 403 a tout
 *     recuperateur automatique, depuis ce poste comme depuis le serveur de production, y
 *     compris help.openai.com et openai.com/news. Ce sous-domaine est la seule porte
 *     officielle lisible - une note du meme constat figure deja dans les sources de la
 *     fiche d actualite 47932.
 *   arcprize.org/blog/astra -> 200, « OpenAI s GPT-6 Astra on ARC-AGI-3 | ARC Prize ».
 *   allenai.org/blog/molmoact2 -> 200, « MolmoAct 2: An open foundation for robots that
 *     work in the real world | Ai2 ».
 *   arxiv.org/abs/2605.02881 -> 200, « MolmoAct2: Action Reasoning Models for Real-world
 *     Deployment ».
 * DEUX AFFIRMATIONS ECARTEES faute de verification a la source primaire : la licence
 * Apache-2.0 et une taille de 5 milliards de parametres pour MolmoAct2, toutes deux
 * avancees par un moteur de recherche mais introuvables dans le blogue d Ai2. Elles ne
 * figurent nulle part dans la fiche.
 *
 * UN FAIT FABRIQUE, INTERCEPTE : le modele sollicite pour la redaction avait ecrit que
 * GPT-6 Astra « a exploite une faille inconnue dans un systeme bancaire, contraignant
 * OpenAI a avertir l institution financiere concernee ». Controle sur la fiche de securite :
 * ZERO occurrence du mot « bank ». Le passage a ete rejete et reecrit a partir du texte
 * reel (banc d essai interne, failles divulguees apres la date de coupure des
 * connaissances, deux vulnerabilites jour zero en cours de divulgation).
 *
 * ALIAS - ce qui est retenu et ce qui est ECARTE, avec le motif :
 *   GPT-6 Astra : retenus « GPT-6 Astra » et « GPT-6 ». ECARTE « Astra » seul - c est
 *     aussi une automobile (Opel Astra), un laboratoire pharmaceutique (AstraZeneca) et un
 *     prenom; le piege « nom propre contre nom propre » n est pas couvert par la
 *     sensibilite a la casse.
 *   MolmoAct2 : retenus « MolmoAct 2 » et « MolmoAct2 ». ECARTE « MolmoAct » seul, qui
 *     designe la VERSION 1 d aout 2025, un modele distinct : le garder ferait pointer
 *     chaque mention de la v1 vers la fiche de la v2.
 *   match_strategy = case_sensitive sur les deux (posture defensive, sans coup sur des
 *     noms propres deja capitalises).
 *
 * TYPOGRAPHIE : espaces insecables posees par script selon la norme de l Office quebecois
 * de la langue francaise - insecable avant les deux-points et dans les guillemets, et
 * AUCUNE espace devant ; ! ? (regle quebecoise, differente de l usage francais).
 *
 * Migration idempotente : un slug deja present est ignore. down() supprime les deux termes
 * et retire leur slug des narrower_slugs des termes parents.
 */
return new class extends Migration
{
    private function resolveCategoryId(string $slug): ?int
    {
        return Category::where('slug->fr_CA', $slug)->value('id')
            ?? Category::where('slug->fr', $slug)->value('id');
    }

    private function terms(): array
    {
        return [
            [
                'name' => 'GPT-6 Astra',
                'slug' => 'gpt-6-astra',
                'cat_slug' => 'intelligence-artificielle',
                'definition' => 'GPT-6 Astra est un modèle d\'intelligence artificielle lancé par OpenAI le 3 septembre 2026, que l\'entreprise présente comme le plus performant qu\'elle ait jamais déployé à grande échelle. Le nom se lit en deux parties : « GPT-6 » désigne la génération, « Astra » cette édition précise. Son intérêt principal ne tient pas à ses performances générales mais à un seuil franchi en cybersécurité : c\'est le premier modèle qu\'OpenAI classe au niveau « Critique » de son cadre interne d\'évaluation des risques. L\'entreprise écrit qu\'avec les bons outils et les bons accès, le modèle peut trouver des failles de sécurité jusque-là inconnues et concevoir de nouvelles façons de les exploiter. En biologie et en chimie, elle le classe au niveau inférieur, « Élevé ». Son prédécesseur est GPT-5.6 Sol, auquel il résiste nettement mieux aux tentatives de contournement de ses garde-fous, y compris sur de longues séquences d\'actions.',
                'analogy' => 'Comme un mécanicien qui entend, au seul bruit du moteur, un défaut de fabrication qu\'aucun contrôle qualité n\'avait repéré sur une voiture neuve.',
                'example' => 'Sur un banc d\'essai monté avec des failles divulguées après la date de coupure de ses connaissances, donc absentes de son entraînement, Astra a découvert puis exploité deux vulnérabilités que personne n\'avait encore signalées.',
                'did_you_know' => 'Les deux failles « jour zéro » qu\'Astra a trouvées lors de ces essais étaient bien réelles : OpenAI écrit être en train de les divulguer aux parties concernées.',
                'one_sentence_answer' => 'GPT-6 Astra est le modèle d\'OpenAI lancé le 3 septembre 2026, et le premier que l\'entreprise classe « Critique » en cybersécurité, capable de découvrir des failles inconnues.',
                'faq' => [
                    [
                        'question' => 'Que veut dire le niveau « Critique » attribué à ce modèle?',
                        'answer' => 'C\'est le plus haut palier du cadre interne qu\'OpenAI utilise pour évaluer les risques de ses modèles avant de les diffuser. Astra est le premier à l\'atteindre, et uniquement pour la cybersécurité. L\'entreprise en tire des restrictions d\'accès plutôt qu\'un argument de vente : le niveau mesure un danger potentiel, pas une qualité.',
                    ],
                    [
                        'question' => 'Un modèle qui trouve des failles, est-ce une bonne ou une mauvaise nouvelle?',
                        'answer' => 'Les deux, et c\'est précisément ce qui rend ce seuil important. La même capacité sert à réparer un système avant qu\'il ne soit attaqué et à l\'attaquer. OpenAI a choisi de divulguer les failles trouvées plutôt que de les taire, et restreint l\'accès aux usages de cybersécurité offensive.',
                    ],
                    [
                        'question' => 'Faut-il croire les chiffres avancés par OpenAI?',
                        'answer' => 'Ils viennent de l\'entreprise qui vend le modèle, et ses essais sont en bonne partie internes. Des évaluations extérieures existent, notamment une analyse d\'ARC Prize sur le banc d\'essai ARC-AGI-3, et plusieurs évaluateurs indépendants ont tempéré certaines affirmations. Lire les deux avant de conclure reste la bonne méthode.',
                    ],
                ],
                'sources' => [
                    [
                        'label' => 'OpenAI, fiche de sécurité de GPT-6 Astra (Deployment Safety Hub)',
                        'url' => 'https://deploymentsafety.openai.com/gpt-6-astra',
                        'year' => 2026,
                        'author' => 'OpenAI',
                    ],
                    [
                        'label' => 'ARC Prize, analyse des résultats de GPT-6 Astra sur ARC-AGI-3',
                        'url' => 'https://arcprize.org/blog/astra',
                        'year' => 2026,
                        'author' => 'ARC Prize Foundation',
                    ],
                ],
                'aliases' => [
                    'GPT-6 Astra',
                    'GPT-6',
                ],
                'broader_slugs' => [
                    'openai',
                    'llm',
                ],
                'narrower_slugs' => [],
                'difficulty' => 'intermediate',
                'icon' => '🛡️',
                'type' => 'ai_term',
                'match_strategy' => 'case_sensitive',
                'has_image' => true,
            ],
            [
                'name' => 'MolmoAct2',
                'slug' => 'molmoact2',
                'cat_slug' => 'intelligence-artificielle',
                'definition' => 'MolmoAct2 est un modèle de fondation ouvert pour la robotique, publié le 5 mai 2026 par Ai2, l\'Allen Institute for AI, un institut de recherche à but non lucratif de Seattle. Il succède à MolmoAct, lancé en août 2025, qu\'Ai2 présentait comme le premier « Action Reasoning Model » : une classe de modèles qui raisonnent sur leur environnement en trois dimensions avant d\'agir, plutôt que de réagir image par image. MolmoAct2 fonctionne jusqu\'à 37 fois plus vite que son prédécesseur et gère diverses tâches du monde réel sans réglage fin propre à chaque tâche. Ai2 affirme qu\'il dépasse des modèles de robotique propriétaires sur des bancs d\'essai de l\'industrie. Sa particularité tient surtout à son ouverture : l\'institut publie les poids, les jeux de données, le code d\'entraînement et la recette complète, là où la plupart des équipes du domaine n\'en publient pas assez pour que le travail puisse être étudié ou amélioré.',
                'analogy' => 'Comme un cuisinier qui se représente mentalement chaque geste de la recette avant de toucher au moindre ustensile, plutôt que d\'improviser au fur et à mesure.',
                'example' => 'Le 28 mai 2026, trois semaines après la publication, Ai2 a ouvert tout le code et intégré le modèle à LeRobot, la plateforme de robotique de Hugging Face.',
                'did_you_know' => 'Ai2 a publié en même temps le jeu de données MolmoAct 2-Bimanual YAM, présenté comme le plus grand ensemble ouvert de manipulation à deux bras sur table, avec plus de 720 heures de démonstrations.',
                'one_sentence_answer' => 'MolmoAct2 est un modèle de robotique entièrement ouvert publié par Ai2 le 5 mai 2026, jusqu\'à 37 fois plus rapide que son prédécesseur et capable de tâches variées sans réglage propre à chacune.',
                'faq' => [
                    [
                        'question' => 'Pourquoi Ai2 publie-t-il tout, y compris les données d\'entraînement?',
                        'answer' => 'Parce que l\'institut estime que la robotique ouverte progresse plus vite quand les chercheurs peuvent évaluer et modifier les modèles eux-mêmes. Il relève que dans ce domaine, certaines équipes publient les poids, peu publient les données, et presque aucune n\'en publie assez pour qu\'un tiers puisse étudier le travail ou l\'améliorer sérieusement.',
                    ],
                    [
                        'question' => 'En quoi un modèle de robotique diffère-t-il d\'un agent conversationnel?',
                        'answer' => 'Il ne produit pas du texte mais des commandes de mouvement. Il reçoit des images de caméras, une consigne en langage courant et l\'état du robot, puis il raisonne en trois dimensions sur la scène avant de produire une séquence de gestes. L\'erreur n\'y est pas une phrase maladroite mais un objet renversé.',
                    ],
                    [
                        'question' => 'Un robot peut-il maintenant charger un lave-vaisselle tout seul?',
                        'answer' => 'Pas encore de façon fiable, et c\'est précisément le problème que ce travail attaque. Ai2 note que l\'IA rédige des courriels et corrige du code, mais qu\'un robot capable d\'enchaîner des heures de tâches ménagères ou de manipulations de laboratoire reste hors de portée de la plupart des systèmes actuels.',
                    ],
                ],
                'sources' => [
                    [
                        'label' => 'Ai2, « MolmoAct 2: An open foundation for robots that work in the real world »',
                        'url' => 'https://allenai.org/blog/molmoact2',
                        'year' => 2026,
                        'author' => 'Allen Institute for AI',
                    ],
                    [
                        'label' => 'MolmoAct2: Action Reasoning Models for Real-world Deployment (prépublication arXiv:2605.02881)',
                        'url' => 'https://arxiv.org/abs/2605.02881',
                        'year' => 2026,
                        'author' => 'Fang, Duan, Clay et coll.',
                    ],
                ],
                'aliases' => [
                    'MolmoAct 2',
                    'MolmoAct2',
                ],
                'broader_slugs' => [
                    'embodied-ai',
                    'modele-multimodal',
                ],
                'narrower_slugs' => [],
                'difficulty' => 'intermediate',
                'icon' => '🤲',
                'type' => 'ai_term',
                'match_strategy' => 'case_sensitive',
                'has_image' => true,
            ],
        ];
    }

    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        if (! class_exists(Term::class) || ! class_exists(Category::class)) {
            echo "[glossaire] modele Term/Category absent, ignore\n";

            return;
        }

        $fallbackCatId = $this->resolveCategoryId('intelligence-artificielle');

        foreach ($this->terms() as $i => $t) {
            if (Term::where('slug->fr_CA', $t['slug'])->exists()) {
                echo "[glossaire] slug deja present, skip : {$t['slug']}\n";

                continue;
            }

            $term = new Term();

            foreach (['name', 'slug', 'definition', 'analogy', 'example', 'did_you_know', 'one_sentence_answer'] as $tf) {
                $term->setTranslations($tf, ['fr_CA' => $t[$tf], 'fr' => $t[$tf]]);
            }

            $term->faq = $t['faq'];
            $term->sources = $t['sources'];
            $term->aliases = $t['aliases'];
            $term->broader_slugs = $t['broader_slugs'];
            $term->narrower_slugs = $t['narrower_slugs'];
            $term->difficulty = $t['difficulty'];
            $term->icon = $t['icon'];
            $term->type = $t['type'];
            $term->match_strategy = $t['match_strategy'];
            $term->dictionary_category_id = $this->resolveCategoryId($t['cat_slug']) ?? $fallbackCatId;
            // La paire d images est generee separement et committee : hero_image n est pose
            // que si has_image, et le controle « git ls-files » a ete fait avant le commit -
            // un fichier present sur le disque mais non versionne ne part pas en production.
            $term->hero_image = ! empty($t['has_image']) ? 'images/glossaire/'.$t['slug'].'.webp' : null;
            $term->is_published = true;
            $term->sort_order = 981 + $i;
            $term->save();

            echo "[glossaire] terme ajoute : {$t['slug']}\n";

            // Reciproque : le terme parent apprend a connaitre son enfant.
            foreach ($t['broader_slugs'] as $parentSlug) {
                $parent = Term::where('slug->fr_CA', $parentSlug)->first();

                if (! $parent) {
                    continue;
                }

                $narrower = $parent->narrower_slugs ?? [];

                if (! in_array($t['slug'], $narrower, true)) {
                    $narrower[] = $t['slug'];
                    $parent->narrower_slugs = $narrower;
                    $parent->save();
                }
            }
        }
    }

    public function down(): void
    {
        if (! class_exists(Term::class)) {
            return;
        }

        foreach ($this->terms() as $t) {
            foreach ($t['broader_slugs'] as $parentSlug) {
                $parent = Term::where('slug->fr_CA', $parentSlug)->first();

                if (! $parent) {
                    continue;
                }

                $parent->narrower_slugs = array_values(array_diff($parent->narrower_slugs ?? [], [$t['slug']]));
                $parent->save();
            }

            Term::where('slug->fr_CA', $t['slug'])->delete();
        }
    }
};
