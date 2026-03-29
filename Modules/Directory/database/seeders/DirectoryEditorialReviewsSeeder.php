<?php

declare(strict_types=1);

namespace Modules\Directory\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Directory\Models\Tool;
use Modules\Directory\Models\ToolReview;

class DirectoryEditorialReviewsSeeder extends Seeder
{
    public function run(): void
    {
        $reviews = [
            ['slug' => 'chatgpt', 'rating' => 5, 'title' => "Le couteau suisse de l'IA", 'pros' => 'Polyvalent, multilangue, écosystème riche avec plugins et GPTs personnalisés', 'cons' => 'Hallucinations occasionnelles, limites strictes sur le plan gratuit', 'body' => "ChatGPT est vraiment un outil incroyable pour une multitude de tâches. Sa capacité à comprendre et à générer du texte dans plusieurs langues est impressionnante. L'écosystème d'applications et de plugins qui s'est développé autour est un énorme plus. Pour un professionnel québécois, c'est un investissement qui se rentabilise rapidement."],
            ['slug' => 'claude', 'rating' => 5, 'title' => "L'IA la plus humaine", 'pros' => 'Écriture naturelle et nuancée, contexte très long (200K tokens), approche éthique', 'cons' => 'Pas de navigation web sur le plan gratuit, peut être lent sur les requêtes complexes', 'body' => "Claude se distingue par son approche plus naturelle et conversationnelle. Il gère très bien les longs contextes, ce qui est idéal pour analyser des documents complets. Sa sensibilité à l'éthique et son refus de générer du contenu problématique en font un choix responsable pour les organisations."],
            ['slug' => 'gemini', 'rating' => 4, 'title' => "L'atout Google", 'pros' => 'Intégration native avec Google Workspace, multimodal puissant, plan gratuit généreux', 'cons' => 'Moins fiable que GPT-4 pour le raisonnement complexe, interface web perfectible', 'body' => "Gemini est une option solide, surtout pour ceux qui sont déjà dans l'écosystème Google. Sa capacité multimodale ouvre de nouvelles perspectives pour l'analyse d'images et de vidéos. Le plan gratuit est assez généreux pour une utilisation quotidienne régulière."],
            ['slug' => 'perplexity', 'rating' => 5, 'title' => 'Le Google de demain', 'pros' => 'Sources toujours citées, recherche en temps réel, réponses précises et vérifiables', 'cons' => "Pas de génération d'images, limites quotidiennes sur le plan gratuit", 'body' => "Perplexity change la donne pour la recherche d'informations. Pouvoir voir les sources directement est un gage de fiabilité que les autres chatbots n'offrent pas. C'est comme avoir un moteur de recherche intelligent qui comprend vos questions et vous donne des réponses structurées avec des preuves."],
            ['slug' => 'midjourney', 'rating' => 4, 'title' => 'La référence en image IA', 'pros' => 'Qualité artistique exceptionnelle, variété de styles impressionnante, communauté active', 'cons' => "Interface Discord obligatoire (pas de site web dédié), courbe d'apprentissage des prompts", 'body' => "Pour la génération d'images, Midjourney est difficile à battre en termes de qualité artistique. Les résultats sont souvent époustouflants et permettent d'explorer une grande variété de styles. Il faut juste s'habituer à l'interface Discord, ce qui peut rebuter les débutants."],
        ];

        foreach ($reviews as $data) {
            $tool = Tool::where('slug->fr_CA', $data['slug'])->first();

            if (! $tool) {
                $this->command?->warn("Outil '{$data['slug']}' non trouvé.");

                continue;
            }

            ToolReview::updateOrCreate(
                ['user_id' => 1, 'directory_tool_id' => $tool->id],
                [
                    'rating' => $data['rating'],
                    'title' => $data['title'],
                    'pros' => $data['pros'],
                    'cons' => $data['cons'],
                    'body' => $data['body'],
                    'is_approved' => true,
                ]
            );
        }
    }
}
