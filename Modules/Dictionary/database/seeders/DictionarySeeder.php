<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Dictionary\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DictionarySeeder extends Seeder
{
    public function run(): void
    {
        $catId = DB::table('dictionary_categories')->insertGetId([
            'name' => json_encode(['fr_CA' => 'Intelligence artificielle', 'fr' => 'Intelligence artificielle']),
            'slug' => json_encode(['fr_CA' => 'intelligence-artificielle', 'fr' => 'intelligence-artificielle']),
            'description' => json_encode(['fr_CA' => 'Termes liés à l\'IA', 'fr' => 'Termes liés à l\'IA']),
            'sort_order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $terms = [
            ['name' => 'IA générative', 'definition' => 'Systèmes d\'intelligence artificielle capables de créer du contenu original (texte, image, code, musique) à partir de données d\'entraînement.', 'type' => 'ai_term'],
            ['name' => 'LLM', 'definition' => 'Large Language Model – modèle de langage à grande échelle, entraîné sur des milliards de tokens pour comprendre et générer du texte.', 'type' => 'acronym'],
            ['name' => 'Prompt', 'definition' => 'Instruction ou question donnée à un modèle d\'IA pour obtenir une réponse. La qualité du prompt influence directement la qualité du résultat.', 'type' => 'ai_term'],
            ['name' => 'RAG', 'definition' => 'Retrieval-Augmented Generation – technique combinant la recherche d\'information dans une base de données avec la génération de texte par IA.', 'type' => 'acronym'],
            ['name' => 'Token', 'definition' => 'Unité de base du traitement de texte par l\'IA. Un token peut être un mot, une partie de mot ou un caractère de ponctuation.', 'type' => 'ai_term'],
            ['name' => 'Fine-tuning', 'definition' => 'Processus d\'ajustement d\'un modèle IA pré-entraîné avec des données spécifiques pour l\'adapter à une tâche ou un domaine particulier.', 'type' => 'ai_term'],
            ['name' => 'Hallucination', 'definition' => 'Phénomène où une IA génère des informations fausses ou inventées présentées comme des faits, avec une apparence convaincante.', 'type' => 'ai_term'],
            ['name' => 'GPT', 'definition' => 'Generative Pre-trained Transformer – architecture de modèle de langage développée par OpenAI, base de ChatGPT.', 'type' => 'acronym'],
            ['name' => 'MCP', 'definition' => 'Model Context Protocol – protocole ouvert d\'Anthropic permettant aux modèles IA de se connecter à des outils et services externes.', 'type' => 'acronym'],
            ['name' => 'Deepfake', 'definition' => 'Contenu multimédia (vidéo, audio, image) généré ou modifié par IA pour imiter l\'apparence ou la voix d\'une personne réelle.', 'type' => 'ai_term'],
            ['name' => 'IA agentique', 'definition' => 'Systèmes IA autonomes capables de planifier, prendre des décisions et exécuter des tâches complexes sans intervention humaine constante.', 'type' => 'ai_term'],
            ['name' => 'Transformer', 'definition' => 'Architecture de réseau neuronal basée sur le mécanisme d\'attention, fondement de la plupart des modèles de langage modernes.', 'type' => 'ai_term'],
            ['name' => 'NLP', 'definition' => 'Natural Language Processing – traitement automatique du langage naturel, branche de l\'IA qui traite les interactions entre ordinateurs et langage humain.', 'type' => 'acronym'],
            ['name' => 'API', 'definition' => 'Application Programming Interface – interface permettant à des logiciels de communiquer entre eux, notamment pour accéder aux services IA.', 'type' => 'acronym'],
            ['name' => 'Edge AI', 'definition' => 'Intelligence artificielle embarquée qui s\'exécute directement sur l\'appareil de l\'utilisateur plutôt que dans le nuage.', 'type' => 'ai_term'],
            ['name' => 'Slop IA', 'definition' => 'Contenu de faible qualité généré en masse par IA, souvent répétitif ou erroné, qui pollue les environnements numériques.', 'type' => 'ai_term'],
            ['name' => 'RLHF', 'definition' => 'Reinforcement Learning from Human Feedback – méthode d\'entraînement des modèles IA utilisant les retours humains pour améliorer les réponses.', 'type' => 'acronym'],
            ['name' => 'Vibe Coding', 'definition' => 'Approche de programmation où le développeur décrit en langage naturel ce qu\'il veut, et l\'IA génère le code correspondant.', 'type' => 'ai_term'],
            ['name' => 'IA constitutionnelle', 'definition' => 'Approche d\'Anthropic où l\'IA est entraînée selon un ensemble de principes éthiques écrits, plutôt que par jugement humain cas par cas.', 'type' => 'ai_term'],
            ['name' => 'Inférence', 'definition' => 'Processus par lequel un modèle IA entraîné génère des prédictions ou du contenu à partir de nouvelles données d\'entrée.', 'type' => 'ai_term'],
        ];

        foreach ($terms as $term) {
            $slug = Str::slug($term['name']);
            DB::table('dictionary_terms')->updateOrInsert(
                ['slug' => json_encode(['fr_CA' => $slug, 'fr' => $slug])],
                [
                    'name' => json_encode(['fr_CA' => $term['name'], 'fr' => $term['name']]),
                    'definition' => json_encode(['fr_CA' => $term['definition'], 'fr' => $term['definition']]),
                    'type' => $term['type'],
                    'dictionary_category_id' => $catId,
                    'is_published' => true,
                    'sort_order' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
