<?php

declare(strict_types=1);

namespace Modules\Dictionary\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\Dictionary\Models\Category;
use Modules\Dictionary\Models\Term;

class DictionaryEnrichmentSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Catégories enrichies
        $categoriesConfig = [
            'concepts' => ['name' => 'Concepts fondamentaux', 'icon' => '🧠', 'color' => '#0B7285'],
            'acronymes' => ['name' => 'Acronymes et sigles', 'icon' => '🔤', 'color' => '#E67E22'],
            'securite' => ['name' => 'Sécurité et éthique', 'icon' => '🛡️', 'color' => '#E74C3C'],
            'outils' => ['name' => 'Outils et techniques', 'icon' => '🛠️', 'color' => '#8E44AD'],
            'donnees' => ['name' => 'Données et traitement', 'icon' => '📊', 'color' => '#2ECC71'],
        ];

        $categoryIds = [];
        foreach ($categoriesConfig as $key => $config) {
            $category = Category::whereRaw("JSON_EXTRACT(name, '$.fr_CA') = ?", ['"'.$config['name'].'"'])->first();
            if (! $category) {
                $category = new Category;
                $category->setTranslation('slug', 'fr_CA', Str::slug($config['name']));
            }
            $category->setTranslation('name', 'fr_CA', $config['name']);
            $category->icon = $config['icon'];
            $category->color = $config['color'];
            $category->save();
            $categoryIds[$key] = $category->id;
        }

        // 2. Données d'enrichissement
        $data = [
            'API' => [
                'cat' => 'acronymes', 'diff' => 'beginner', 'icon' => '🔌',
                'analogy' => "C'est comme un serveur au restaurant qui prend votre commande et l'apporte en cuisine, sans que vous ayez besoin de savoir cuisiner.",
                'example' => "Quand vous utilisez votre compte Facebook pour vous connecter sur un autre site, c'est une API qui fait le lien.",
                'fact' => "Les API sont le ciment du web moderne, permettant aux applications de se parler sans connaître le code de l'autre.",
            ],
            'Deepfake' => [
                'cat' => 'securite', 'diff' => 'beginner', 'icon' => '🎭',
                'analogy' => "C'est comme du maquillage de cinéma ultra-réaliste, mais appliqué numériquement après le tournage.",
                'example' => "Une vidéo virale où l'on voit le président dire des choses qu'il n'a jamais prononcées.",
                'fact' => 'Le terme est une contraction de « Deep Learning » (apprentissage profond) et « Fake » (faux).',
            ],
            'Edge AI' => [
                'cat' => 'outils', 'diff' => 'intermediate', 'icon' => '📱',
                'analogy' => "C'est comme avoir un chef cuisinier directement à votre table plutôt que d'envoyer la commande en cuisine centrale.",
                'example' => 'La reconnaissance faciale (FaceID) de votre iPhone qui fonctionne même sans internet.',
                'fact' => "C'est plus rapide et plus privé car vos données ne quittent jamais votre appareil.",
            ],
            'Fine-tuning' => [
                'cat' => 'outils', 'diff' => 'intermediate', 'icon' => '🔧',
                'analogy' => "C'est comme envoyer un médecin généraliste suivre une formation pointue pour devenir cardiologue.",
                'example' => "Une entreprise qui prend un modèle générique et l'entraîne spécifiquement sur ses propres manuels techniques.",
                'fact' => "Cela demande beaucoup moins de données et d'énergie que d'entraîner une IA depuis zéro.",
            ],
            'GPT' => [
                'cat' => 'acronymes', 'diff' => 'beginner', 'icon' => '🤖',
                'analogy' => "C'est comme un système d'autocomplétion sur votre téléphone, mais qui a lu tout internet et est devenu super intelligent.",
                'example' => 'Le moteur derrière ChatGPT qui peut rédiger un courriel complet à partir de trois mots.',
                'fact' => "L'acronyme signifie « Generative Pre-trained Transformer » (Transformateur génératif pré-entraîné).",
            ],
            'Hallucination' => [
                'cat' => 'securite', 'diff' => 'beginner', 'icon' => '🍄',
                'analogy' => "C'est comme un étudiant qui ne connaît pas la réponse à l'examen mais qui invente quelque chose avec une confiance absolue.",
                'example' => "Une IA qui cite une décision de la Cour suprême du Canada qui n'a jamais existé.",
                'fact' => "L'IA ne ment pas volontairement\u{00A0}; elle prédit juste le mot suivant le plus probable, même si c'est faux.",
            ],
            'IA agentique' => [
                'cat' => 'concepts', 'diff' => 'intermediate', 'icon' => '🕵️',
                'analogy' => "C'est la différence entre un stagiaire à qui il faut tout dicter et un employé autonome qui prend des initiatives.",
                'example' => "Une IA qui ne fait pas juste vous donner la recette, mais qui commande aussi les ingrédients à l'épicerie pour vous.",
                'fact' => 'Les agents peuvent utiliser des outils (navigateur web, calculatrice) pour accomplir des tâches complexes.',
            ],
            'IA constitutionnelle' => [
                'cat' => 'securite', 'diff' => 'advanced', 'icon' => '📜',
                'analogy' => "C'est comme donner une « Charte des droits et libertés » à l'IA qu'elle ne doit jamais violer.",
                'example' => 'La méthode utilisée par Anthropic pour empêcher son IA Claude de générer du contenu haineux.',
                'fact' => "On utilise souvent une autre IA pour surveiller si l'IA principale respecte bien sa « constitution ».",
            ],
            'IA générative' => [
                'cat' => 'concepts', 'diff' => 'beginner', 'icon' => '✨',
                'analogy' => "C'est un ordinateur qui est passé de simple archiviste à artiste créateur.",
                'example' => "Utiliser Midjourney pour créer une image d'un astronaute qui mange une poutine sur la Lune.",
                'fact' => "Elle ne fait pas que du texte ou des images\u{00A0}; elle peut créer du code, de la voix et même des vidéos.",
            ],
            'Inférence' => [
                'cat' => 'donnees', 'diff' => 'intermediate', 'icon' => '⚡',
                'analogy' => "C'est le moment où l'étudiant passe l'examen (l'utilisation), par opposition au moment où il étudie (l'entraînement).",
                'example' => "La fraction de seconde où ChatGPT réfléchit avant d'afficher sa réponse à votre écran.",
                'fact' => "L'inférence coûte moins cher en énergie que l'entraînement, mais elle se produit des milliards de fois par jour.",
            ],
            'LLM' => [
                'cat' => 'acronymes', 'diff' => 'beginner', 'icon' => '📚',
                'analogy' => "C'est une bibliothèque vivante qui a lu tous les livres du monde et comprend les liens entre les mots.",
                'example' => 'La technologie de base derrière les chatbots comme ChatGPT, Claude ou Gemini.',
                'fact' => 'Ces modèles sont entraînés sur des centaines de milliards de mots provenant du web public.',
            ],
            'MCP' => [
                'cat' => 'outils', 'diff' => 'advanced', 'icon' => '🔌',
                'analogy' => "C'est comme une prise USB universelle, mais pour connecter une IA à vos données personnelles.",
                'example' => "Connecter Claude Desktop directement à votre Google Drive pour qu'il analyse vos fichiers.",
                'fact' => "C'est un standard ouvert pour éviter que les IA ne soient prisonnières de leurs propres applications.",
            ],
            'NLP' => [
                'cat' => 'acronymes', 'diff' => 'intermediate', 'icon' => '🗣️',
                'analogy' => "C'est apprendre à un ordinateur à comprendre la grammaire, l'argot et le contexte, pas juste des 0 et des 1.",
                'example' => 'Quand Siri comprend la différence entre « Appelle Maman » et « Appelle-moi un taxi ».',
                'fact' => "Cela inclut la traduction, l'analyse de sentiments et la reconnaissance vocale.",
            ],
            'Prompt' => [
                'cat' => 'concepts', 'diff' => 'beginner', 'icon' => '⌨️',
                'analogy' => "C'est la formule magique ou les instructions précises que vous donnez au génie pour faire votre vœu.",
                'example' => 'Taper « Agis comme un nutritionniste et fais-moi un plan repas » dans ChatGPT.',
                'fact' => 'Changer un seul adjectif dans votre prompt peut transformer complètement le résultat obtenu.',
            ],
            'RAG' => [
                'cat' => 'outils', 'diff' => 'intermediate', 'icon' => '🔍',
                'analogy' => "C'est autoriser l'IA à faire un examen à « livre ouvert » plutôt que de se fier uniquement à sa mémoire.",
                'example' => "Un chatbot d'entreprise qui va lire le manuel PDF de l'employé avant de répondre à une question sur les vacances.",
                'fact' => "Cette technique réduit considérablement les hallucinations en forçant l'IA à se baser sur des sources réelles.",
            ],
            'RLHF' => [
                'cat' => 'donnees', 'diff' => 'advanced', 'icon' => '👍',
                'analogy' => "C'est le dressage de l'IA : on lui donne une friandise (bon point) quand elle répond bien et on la corrige quand elle se trompe.",
                'example' => 'Des humains qui notent les réponses de ChatGPT pour lui apprendre à être plus poli et utile.',
                'fact' => "C'est cette étape cruciale qui a rendu GPT-3 utilisable par le grand public sous forme de ChatGPT.",
            ],
            'Slop IA' => [
                'cat' => 'securite', 'diff' => 'beginner', 'icon' => '🗑️',
                'analogy' => "C'est l'équivalent du pourriel (spam) ou de la malbouffe, mais pour le contenu généré par IA.",
                'example' => "Ces pages Facebook remplies d'images bizarres générées à la chaîne juste pour obtenir des clics.",
                'fact' => "Ce contenu de basse qualité risque de polluer les résultats de recherche et les futurs entraînements d'IA.",
            ],
            'Token' => [
                'cat' => 'donnees', 'diff' => 'intermediate', 'icon' => '🪙',
                'analogy' => "Ce sont les briques de lego du langage pour l'IA\u{00A0}; un mot peut être coupé en plusieurs morceaux.",
                'example' => "Le mot « Anticonstitutionnellement » serait découpé en plusieurs tokens par l'IA.",
                'fact' => "C'est généralement l'unité de facturation des services d'IA (prix par million de tokens).",
            ],
            'Transformer' => [
                'cat' => 'concepts', 'diff' => 'advanced', 'icon' => '🏗️',
                'analogy' => "C'est une machine capable de lire une phrase entière d'un coup et de comprendre les relations entre tous les mots simultanément.",
                'example' => 'Comprendre que le mot « avocat » désigne le fruit ou le métier selon les autres mots de la phrase.',
                'fact' => "C'est l'architecture révolutionnaire (le « T » de GPT) inventée par Google en 2017 qui a tout changé.",
            ],
            'Vibe Coding' => [
                'cat' => 'outils', 'diff' => 'beginner', 'icon' => '🤙',
                'analogy' => "C'est programmer au « feeling » en expliquant ce qu'on veut en langage naturel, en laissant l'IA gérer la syntaxe compliquée.",
                'example' => "Dire à l'IA « Fais que le bouton soit plus punché et bouge quand on clique » sans écrire de CSS.",
                'fact' => "Cela déplace la compétence de l'écriture de code vers la supervision et la créativité.",
            ],
        ];

        // 3. Mise à jour des termes
        $terms = Term::all();

        foreach ($terms as $term) {
            $termName = $term->getTranslation('name', 'fr_CA', false) ?: $term->name;

            $matchedKey = null;
            foreach (array_keys($data) as $key) {
                if (Str::slug($key) === Str::slug($termName)) {
                    $matchedKey = $key;
                    break;
                }
            }

            if ($matchedKey) {
                $d = $data[$matchedKey];

                $term->setTranslation('analogy', 'fr_CA', $d['analogy']);
                $term->setTranslation('example', 'fr_CA', $d['example']);
                $term->setTranslation('did_you_know', 'fr_CA', $d['fact']);
                $term->difficulty = $d['diff'];
                $term->icon = $d['icon'];

                if (isset($categoryIds[$d['cat']])) {
                    $term->dictionary_category_id = $categoryIds[$d['cat']];
                }

                $term->save();
            }
        }
    }
}
