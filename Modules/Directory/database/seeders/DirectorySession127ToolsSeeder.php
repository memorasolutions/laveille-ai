<?php

declare(strict_types=1);

namespace Modules\Directory\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Directory\Models\Category;
use Modules\Directory\Models\Tool;

/**
 * Outils ajoutés en session 127 (2026-03-25) en prod via script cron.
 * Ce seeder synchronise la DB locale.
 */
class DirectorySession127ToolsSeeder extends Seeder
{
    public function run(): void
    {
        $category = Category::first();

        $tools = [
            ['name' => 'Grok', 'slug' => 'grok', 'url' => 'https://grok.com', 'pricing' => 'freemium', 'launch_year' => 2023, 'short_description' => "L'assistant IA de xAI avec accès en temps réel aux données de X.", 'description' => "Grok est l'assistant conversationnel développé par xAI, l'entreprise fondée par Elon Musk. Il se distingue par son intégration directe avec la plateforme X et son ton plus direct que ses concurrents.", 'core_features' => 'Accès temps réel X, Aurora (images), DeepSearch, Think mode, Voix', 'use_cases' => 'Veille stratégique, Analyse de tendances, Recherche, Création de contenu', 'pros' => 'Données temps réel, Fenêtre 2M jetons, Ton direct', 'cons' => 'Lié à X, Ton parfois controversé', 'target_audience' => ['Veilleurs', 'Journalistes', 'Marketeurs']],
            ['name' => 'Mistral Le Chat', 'slug' => 'mistral-le-chat', 'url' => 'https://chat.mistral.ai', 'pricing' => 'freemium', 'launch_year' => 2025, 'short_description' => "L'IA européenne souveraine par Mistral AI, rapide et respectueuse de la vie privée.", 'description' => "Mistral Le Chat est l'interface conversationnelle de Mistral AI, entreprise française championne de l'IA européenne. Elle mise sur la performance, l'open source et la souveraineté des données.", 'core_features' => 'Deep Research, Flash Answers, Voxtral (voix), Code sandbox, Projects, Memories', 'use_cases' => 'Recherche, Rédaction, Programmation, Analyse de documents', 'pros' => 'Ultra rapide, Souveraineté données, Open source', 'cons' => "Écosystème plus jeune, Moins d'intégrations tierces", 'target_audience' => ['Développeurs', 'Entreprises européennes', 'Chercheurs']],
            ['name' => 'Stability AI', 'slug' => 'stability-ai', 'url' => 'https://stability.ai', 'pricing' => 'freemium', 'launch_year' => 2022, 'short_description' => "Le pionnier de la génération d'images open source avec Stable Diffusion.", 'description' => "Stability AI est la société derrière Stable Diffusion, le modèle open source qui a démocratisé la génération d'images par IA. Leur approche ouverte a inspiré une communauté mondiale de créateurs.", 'core_features' => "Stable Diffusion, Stable Video, Stable Audio, API d'images, Modèles open source", 'use_cases' => 'Art numérique, Design, Prototypage visuel, Génération de textures', 'pros' => 'Open source, Communauté massive, Personnalisable', 'cons' => 'Nécessite du matériel puissant en local, Interface moins intuitive', 'target_audience' => ['Artistes', 'Développeurs', 'Chercheurs en IA']],
            ['name' => 'Udio', 'slug' => 'udio', 'url' => 'https://udio.com', 'pricing' => 'freemium', 'launch_year' => 2024, 'short_description' => 'Créez de la musique de qualité studio avec une simple description textuelle.', 'description' => 'Udio est un générateur musical par IA qui produit des morceaux de qualité professionnelle dans tous les genres. De la pop au classique, il compose des chansons complètes avec voix et instruments.', 'core_features' => 'Génération musicale, Paroles automatiques, Styles variés, Extensions de morceaux, Remix', 'use_cases' => 'Musique de fond, Jingles, Prototypage musical, Divertissement', 'pros' => 'Qualité audio remarquable, Grande variété de styles, Interface simple', 'cons' => 'Droits musicaux complexes, Contrôle limité sur la composition', 'target_audience' => ['Créateurs de contenu', 'Musiciens', 'Podcasteurs']],
            ['name' => 'Pika', 'slug' => 'pika', 'url' => 'https://pika.art', 'pricing' => 'freemium', 'launch_year' => 2023, 'short_description' => 'Générez et éditez des vidéos créatives avec une IA accessible à tous.', 'description' => "Pika est un outil de génération vidéo par IA qui rend la création de clips et d'animations accessible sans compétences techniques. Son interface intuitive et ses effets spéciaux en font un choix populaire.", 'core_features' => 'Texte vers vidéo, Image vers vidéo, Effets spéciaux, Édition vidéo, Lip sync', 'use_cases' => 'Réseaux sociaux, Marketing, Animation, Effets visuels', 'pros' => 'Très accessible, Effets créatifs uniques, Gratuit pour commencer', 'cons' => 'Vidéos courtes, Résolution limitée en gratuit', 'target_audience' => ['Créateurs sociaux', 'Marketeurs', 'Animateurs']],
        ];

        foreach ($tools as $index => $data) {
            $tool = Tool::firstOrCreate(
                ['slug->fr_CA' => $data['slug']],
                ['name' => json_encode(['fr_CA' => $data['name']]), 'url' => $data['url'], 'pricing' => $data['pricing'], 'status' => 'published', 'sort_order' => 22 + $index, 'website_type' => 'website', 'launch_year' => $data['launch_year'], 'target_audience' => $data['target_audience'] ?? null]
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

            $this->command->info("Created/synced: {$data['slug']}");
        }
    }
}
