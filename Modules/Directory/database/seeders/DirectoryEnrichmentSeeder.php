<?php

declare(strict_types=1);

namespace Modules\Directory\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Directory\Models\Tool;

class DirectoryEnrichmentSeeder extends Seeder
{
    public function run(): void
    {
        $tools = Tool::where('status', 'published')->get();

        $data = [
            'chatgpt' => [
                'how_to_use' => "Créez simplement un compte sur le site d'OpenAI pour commencer. Tapez votre requête dans la boîte de clavardage et interagissez comme avec un humain pour raffiner les réponses.",
                'core_features' => "Traitement du langage naturel, Génération de code, Création d'images (DALL-E), Analyse de données, Reconnaissance vocale",
                'use_cases' => "Rédaction de courriels, Aide au codage et débogage, Résumé de textes complexes, Brainstorming d'idées",
                'pros' => "Extrêmement polyvalent, Interface intuitive, Large base de connaissances, Disponible en français",
                'cons' => "Peut halluciner des faits, Données parfois limitées dans le temps (selon le modèle)",
                'faq' => [
                    ['question' => "Est-ce que ChatGPT est gratuit ?", 'answer' => "Oui, la version de base (GPT-4o mini) est gratuite. L'abonnement Plus offre des modèles plus puissants."],
                    ['question' => "Peut-il écrire en français québécois ?", 'answer' => "Oui, il comprend bien les nuances si vous lui demandez spécifiquement d'adopter ce ton."],
                    ['question' => "Mes données sont-elles privées ?", 'answer' => "Par défaut, les conversations peuvent être utilisées pour l'entraînement, mais cela peut être désactivé dans les paramètres."],
                ],
                'website_type' => 'website', 'launch_year' => 2022,
                'target_audience' => ['Rédacteurs', 'Développeurs', 'Étudiants', 'Professionnels du marketing'],
            ],
            'claude' => [
                'how_to_use' => "Rendez-vous sur claude.ai et connectez-vous. Vous pouvez téléverser des documents PDF ou texte directement pour que l'IA les analyse et réponde à vos questions.",
                'core_features' => "Fenêtre de contexte massive, Analyse de documents longs, Raisonnement complexe, Sécurité et éthique, Codage avancé",
                'use_cases' => "Analyse de rapports financiers, Synthèse de livres, Écriture créative nuancée, Assistance à la programmation",
                'pros' => "Style d'écriture très naturel, Moins de refus injustifiés, Capable de lire des livres entiers, Excellent en français",
                'cons' => "Pas de génération d'images native, Moins d'extensions tierces que ChatGPT",
                'faq' => [
                    ['question' => "Quelle est la différence avec ChatGPT ?", 'answer' => "Claude se distingue par sa capacité à traiter beaucoup plus de texte à la fois et son ton plus humain."],
                    ['question' => "Claude peut-il accéder à internet ?", 'answer' => "Non, contrairement à d'autres, Claude se base uniquement sur ses données d'entraînement et vos documents."],
                    ['question' => "Est-ce bon pour le code ?", 'answer' => "Oui, Claude Sonnet est particulièrement réputé pour ses capacités en programmation."],
                ],
                'website_type' => 'website', 'launch_year' => 2023,
                'target_audience' => ['Chercheurs', 'Auteurs', "Analystes d'affaires", 'Développeurs'],
            ],
            'midjourney' => [
                'how_to_use' => "L'outil fonctionne principalement via Discord. Rejoignez leur serveur, allez dans un canal dédié et tapez la commande /imagine suivie de votre description pour générer une image.",
                'core_features' => "Génération d'images photoréalistes, Styles artistiques variés, Upscaling d'images, Inpainting (variation de régions), Mélange d'images",
                'use_cases' => "Création de maquettes web, Illustration de blogue, Concept art pour jeux vidéo, Matériel publicitaire",
                'pros' => "Qualité esthétique supérieure, Communauté très active, Mises à jour fréquentes, Rendu artistique impressionnant",
                'cons' => "Interface Discord peu conviviale pour les débutants, Pas de plan gratuit permanent",
                'faq' => [
                    ['question' => "Puis-je utiliser les images commercialement ?", 'answer' => "Oui, si vous avez un abonnement payant actif, vous possédez les droits commerciaux."],
                    ['question' => "Est-ce difficile à apprendre ?", 'answer' => "Les bases sont simples, mais maîtriser les paramètres avancés demande de la pratique."],
                    ['question' => "Existe-t-il une version web ?", 'answer' => "Une version web est disponible, mais Discord reste la voie principale pour la plupart des utilisateurs."],
                ],
                'website_type' => 'website', 'launch_year' => 2022,
                'target_audience' => ['Designers graphiques', 'Artistes', 'Directeurs artistiques', 'Créateurs de contenu'],
            ],
            'cursor' => [
                'how_to_use' => "Téléchargez et installez l'éditeur de code Cursor (un dérivé de VS Code). Importez vos projets existants et utilisez le raccourci Cmd+K pour générer ou modifier du code avec l'IA.",
                'core_features' => "Éditeur de code intégré, Chat avec la base de code, Auto-correction des erreurs, Prédiction de la prochaine modification, Support des extensions VS Code",
                'use_cases' => "Développement web rapide, Refactoring de code existant, Compréhension de nouveaux projets, Débogage assisté",
                'pros' => "Intégration fluide dans le workflow, Comprend le contexte global du projet, Gain de temps majeur, Interface familière",
                'cons' => "Courbe d'apprentissage pour les non-développeurs, Certaines fonctions avancées sont payantes",
                'faq' => [
                    ['question' => "Est-ce compatible avec mes extensions VS Code ?", 'answer' => "Oui, vous pouvez importer toutes vos extensions et configurations en un clic."],
                    ['question' => "Mes données de code sont-elles sécurisées ?", 'answer' => "Cursor propose un mode privé où le code n'est pas stocké sur leurs serveurs."],
                    ['question' => "Quel modèle IA est utilisé ?", 'answer' => "Cursor permet de choisir entre plusieurs modèles, dont GPT-4o et Claude Sonnet."],
                ],
                'website_type' => 'website', 'launch_year' => 2023,
                'target_audience' => ['Développeurs web', 'Ingénieurs logiciels', 'Data Scientists', 'Étudiants en informatique'],
            ],
            'perplexity' => [
                'how_to_use' => "Utilisez-le comme un moteur de recherche. Posez une question complexe et Perplexity scannera le web pour vous fournir une réponse synthétisée avec des notes de bas de page vers les sources.",
                'core_features' => "Recherche web en temps réel, Citations des sources, Mode Focus (Académique/YouTube/Reddit), Copilot pour recherches approfondies, Organisation en collections",
                'use_cases' => "Recherche documentaire, Vérification de faits (Fact-checking), Veille technologique, Préparation de dossiers",
                'pros' => "Transparence des sources, Pas de publicité dans les réponses, Très rapide, Interface épurée",
                'cons' => "Moins créatif pour la fiction, Dépend de la qualité des résultats de recherche web",
                'faq' => [
                    ['question' => "Est-ce que ça remplace Google ?", 'answer' => "Pour les questions nécessitant une réponse directe plutôt qu'une liste de liens, oui, c'est souvent plus efficace."],
                    ['question' => "Les sources sont-elles fiables ?", 'answer' => "Perplexity cite ses sources, ce qui vous permet de vérifier la crédibilité de l'information originale."],
                    ['question' => "Y a-t-il une application mobile ?", 'answer' => "Oui, une excellente application est disponible sur iOS et Android."],
                ],
                'website_type' => 'website', 'launch_year' => 2022,
                'target_audience' => ['Chercheurs', 'Journalistes', 'Étudiants', 'Curieux'],
            ],
            'gemini' => [
                'how_to_use' => "Connectez-vous avec votre compte Google habituel. Gemini est intégré à l'écosystème Google, vous permettant d'interagir avec vos Docs, Drive et Gmail si vous l'autorisez.",
                'core_features' => "Multimodalité native (texte/image/vidéo), Intégration Google Workspace, Fenêtre de contexte énorme, Vitesse d'exécution, Recherche Google intégrée",
                'use_cases' => "Planification de voyages (Maps/Flights), Rédaction assistée dans Docs, Analyse de vidéos YouTube, Gestion de courriels",
                'pros' => "Parfaitement intégré aux outils Google, Gratuit pour la version de base, Capable de « voir » et « entendre », Réponses rapides",
                'cons' => "Filtres de sécurité parfois trop sensibles, L'interface peut changer souvent",
                'faq' => [
                    ['question' => "Est-ce l'ancien Google Bard ?", 'answer' => "Oui, Google a renommé Bard en Gemini pour unifier ses modèles d'IA."],
                    ['question' => "Peut-il lire mes courriels ?", 'answer' => "Seulement si vous activez l'extension Workspace pour l'aider à résumer ou rechercher dans vos courriels."],
                    ['question' => "Peut-il générer des images ?", 'answer' => "Oui, Gemini peut créer des images, bien que cette fonction soit parfois suspendue pour ajustements."],
                ],
                'website_type' => 'website', 'launch_year' => 2023,
                'target_audience' => ['Utilisateurs Google Workspace', 'Professionnels de bureau', 'Créateurs', 'Grand public'],
            ],
        ];

        foreach ($tools as $tool) {
            $slug = $tool->getTranslation('slug', 'fr_CA', false) ?: $tool->slug;
            if (! array_key_exists($slug, $data)) {
                continue;
            }

            $info = $data[$slug];

            $tool->setTranslation('how_to_use', 'fr_CA', $info['how_to_use']);
            $tool->setTranslation('core_features', 'fr_CA', $info['core_features']);
            $tool->setTranslation('use_cases', 'fr_CA', $info['use_cases']);
            $tool->setTranslation('pros', 'fr_CA', $info['pros']);
            $tool->setTranslation('cons', 'fr_CA', $info['cons']);

            $tool->faq = $info['faq'];
            $tool->website_type = $info['website_type'];
            $tool->launch_year = $info['launch_year'];
            $tool->target_audience = $info['target_audience'];

            $tool->save();
        }
    }
}
