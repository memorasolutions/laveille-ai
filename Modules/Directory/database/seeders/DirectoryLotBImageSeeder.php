<?php

declare(strict_types=1);

namespace Modules\Directory\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Directory\Models\Category;
use Modules\Directory\Models\Tool;

class DirectoryLotBImageSeeder extends Seeder
{
    public function run(): void
    {
        $category = Category::where('slug->fr_CA', 'generation-images')->first();

        $tools = [
            ['name' => 'Adobe Firefly', 'slug' => 'adobe-firefly', 'url' => 'https://firefly.adobe.com', 'pricing' => 'freemium', 'launch_year' => 2023, 'short_description' => "Moteur de génération d'images IA d'Adobe, conçu pour un usage commercial sécuritaire et intégré dans Photoshop et Creative Cloud.", 'description' => "Adobe Firefly est le moteur d'IA générative d'Adobe, entrainé exclusivement sur du contenu sous licence et du domaine public. Cette approche garantit que les images générées sont sécuritaires pour un usage commercial. Firefly est intégré nativement dans Photoshop, Illustrator et les autres applications Creative Cloud, permettant de générer des images, d'appliquer des remplissages génératifs et de créer des effets de texte directement dans les flux de travail existants.", 'core_features' => "Génération d'images texte-vers-image, Remplissage génératif Photoshop, Extension d'arrière-plans, Effets de texte génératifs, Content Credentials, Intégration Creative Cloud", 'use_cases' => 'Visuels marketing conformes, Retouche photo avancée, Maquettes et concepts, Contenu réseaux sociaux', 'pros' => 'Usage commercial sécuritaire, Intégration Photoshop fluide, Content Credentials pour traçabilité', 'cons' => "Moins créatif que Midjourney, Crédits gratuits limités, Nécessite abonnement Adobe pour l'intégration complète", 'target_audience' => ['Designers graphiques', 'Photographes', 'Équipes marketing']],
            ['name' => 'FLUX', 'slug' => 'flux', 'url' => 'https://blackforestlabs.ai', 'pricing' => 'freemium', 'launch_year' => 2024, 'short_description' => "Modèle open source de génération d'images de haute qualité développé par Black Forest Labs, l'équipe derrière Stable Diffusion.", 'description' => "FLUX est une famille de modèles de génération d'images développée par Black Forest Labs. Disponible en variantes Pro, Dev et Schnell (open source), la plateforme offre un équilibre entre qualité exceptionnelle, fidélité aux descriptions et vitesse. FLUX se distingue par sa capacité remarquable à générer du texte lisible dans les images.", 'core_features' => 'Variantes Pro, Dev, Schnell (open source), Texte lisible dans les images, Compatible ComfyUI, Écosystème de LoRA, API disponible, 12 milliards de paramètres', 'use_cases' => "Images photoréalistes, Visuels avec texte intégré, Projets open source, Applications avec génération d'images", 'pros' => 'Qualité parmi les meilleures, Excellente génération de texte, Version Schnell open source', 'cons' => "Matériel puissant requis en local, Version Pro payante par API, Courbe d'apprentissage ComfyUI", 'target_audience' => ['Développeurs IA', 'Artistes numériques', 'Communauté open source']],
            ['name' => 'Krea AI', 'slug' => 'krea-ai', 'url' => 'https://krea.ai', 'pricing' => 'freemium', 'launch_year' => 2023, 'short_description' => "Plateforme de génération et d'édition d'images en temps réel qui permet de visualiser les résultats instantanément.", 'description' => "Krea AI se démarque par sa capacité de génération en temps réel. L'utilisateur peut dessiner, esquisser ou modifier une image et voir le résultat généré par l'IA se mettre à jour instantanément. La plateforme intègre plusieurs modèles dont FLUX et Stable Diffusion.", 'core_features' => 'Génération en temps réel, Croquis vers image, Amélioration et upscaling, Édition par IA, Support FLUX et Stable Diffusion, Mode vidéo temps réel', 'use_cases' => 'Exploration de concepts visuels, Transformation de croquis, Prototypage visuel interactif, Brainstorming visuel', 'pros' => 'Génération temps réel unique, Interface intuitive, Plusieurs modèles disponibles', 'cons' => 'Fonctionnalités avancées payantes, Qualité variable selon le mode, Moins adapté à la production massive', 'target_audience' => ['Designers', 'Illustrateurs', 'Concepteurs de jeux']],
            ['name' => 'Playground AI', 'slug' => 'playground-ai', 'url' => 'https://playground.ai', 'pricing' => 'freemium', 'launch_year' => 2022, 'short_description' => 'Plateforme collaborative de création artistique par IA offrant un éditeur de canevas puissant et une communauté active.', 'description' => "Playground AI combine un puissant éditeur de canevas avec des capacités de génération avancées. La plateforme a développé ses propres modèles propriétaires optimisés pour la qualité esthétique. L'éditeur de canevas permet de travailler sur des compositions complexes en combinant plusieurs générations.", 'core_features' => 'Éditeur de canevas avancé, Modèles propriétaires, Inpainting et outpainting, Transfert de style, Galerie communautaire, Générations gratuites quotidiennes', 'use_cases' => 'Art numérique, Compositions visuelles complexes, Exploration de styles, Visuels pour réseaux sociaux', 'pros' => 'Générations gratuites généreuses, Éditeur de canevas puissant, Communauté active', 'cons' => 'Qualité parfois inférieure à Midjourney, Options haute résolution limitées en gratuit', 'target_audience' => ['Artistes numériques', 'Hobbyistes', 'Étudiants en design']],
            ['name' => 'Freepik AI', 'slug' => 'freepik-ai', 'url' => 'https://www.freepik.com/ai', 'pricing' => 'freemium', 'launch_year' => 2023, 'short_description' => "Générateur d'images IA intégré à Freepik, permettant de créer des visuels directement utilisables dans des projets de design.", 'description' => "Freepik AI est le générateur d'images intégré à Freepik, l'une des plus grandes banques de ressources graphiques au monde. Le générateur offre plusieurs styles incluant le photoréalisme, l'illustration, le 3D et la peinture numérique. La licence commerciale incluse simplifie l'utilisation dans des projets professionnels.", 'core_features' => "Génération texte-vers-image, Styles multiples (photo, illustration, 3D), Amélioration d'images, Remplacement d'arrière-plans, Licence commerciale incluse, Intégration banque Freepik", 'use_cases' => 'Visuels marketing, Ressources graphiques, Contenu réseaux sociaux, Mockups et présentations', 'pros' => 'Intégration Freepik, Licence commerciale claire, Variété de styles', 'cons' => 'Qualité inférieure aux leaders, Générations limitées selon le plan', 'target_audience' => ['Designers', 'Marketeurs', 'Utilisateurs Freepik']],
            ['name' => 'DALL-E', 'slug' => 'dall-e', 'url' => 'https://openai.com/dall-e', 'pricing' => 'paid', 'launch_year' => 2021, 'short_description' => "Générateur d'images IA d'OpenAI, intégré directement dans ChatGPT pour créer des visuels à partir de descriptions textuelles.", 'description' => "DALL-E 3 est le modèle de génération d'images d'OpenAI, intégré nativement dans ChatGPT. L'utilisateur peut générer des images directement dans une conversation, avec la possibilité d'affiner les résultats par des échanges en langage naturel. Le modèle excelle dans la compréhension de descriptions complexes et la génération de texte lisible.", 'core_features' => 'Intégration native ChatGPT, Édition conversationnelle, Texte lisible dans les images, API pour développeurs, Garde-fous de sécurité, Métadonnées C2PA', 'use_cases' => 'Génération via conversation ChatGPT, Illustrations articles et présentations, Prototypage visuel, Applications via API', 'pros' => 'Intégration ChatGPT unique, Excellente compréhension des descriptions, Bonne génération de texte', 'cons' => 'Nécessite abonnement ChatGPT Plus, Moins de contrôle stylistique que Midjourney, Restrictions strictes sur certains contenus', 'target_audience' => ['Utilisateurs ChatGPT', 'Développeurs', 'Créateurs de contenu']],
        ];

        foreach ($tools as $data) {
            $tool = Tool::firstOrCreate(
                ['slug->fr_CA' => $data['slug']],
                ['name' => json_encode(['fr_CA' => $data['name']]), 'url' => $data['url'], 'pricing' => $data['pricing'], 'status' => 'published', 'sort_order' => 40, 'website_type' => 'website', 'launch_year' => $data['launch_year'], 'target_audience' => $data['target_audience']]
            );

            $tool->setTranslation('name', 'fr_CA', $data['name']);
            $tool->setTranslation('slug', 'fr_CA', $data['slug']);
            $tool->setTranslation('description', 'fr_CA', $data['description']);
            $tool->setTranslation('short_description', 'fr_CA', $data['short_description']);
            $tool->setTranslation('core_features', 'fr_CA', $data['core_features']);
            $tool->setTranslation('use_cases', 'fr_CA', $data['use_cases']);
            $tool->setTranslation('pros', 'fr_CA', $data['pros']);
            $tool->setTranslation('cons', 'fr_CA', $data['cons']);
            $tool->save();

            if ($category) {
                $tool->categories()->syncWithoutDetaching([$category->id]);
            }

            $this->command->info("Created: {$data['slug']}");
        }
    }
}
