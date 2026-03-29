<?php

declare(strict_types=1);

namespace Modules\Directory\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Directory\Models\Category;
use Modules\Directory\Models\Tool;

class DirectoryLotAWritingSeeder extends Seeder
{
    public function run(): void
    {
        $category = Category::where('slug->fr_CA', 'ecriture-ia')->first();

        $tools = [
            ['name' => 'Jasper', 'slug' => 'jasper', 'url' => 'https://www.jasper.ai', 'pricing' => 'paid', 'launch_year' => 2021, 'short_description' => 'Une plateforme de contenu IA avancée conçue pour les équipes marketing et les entreprises.', 'description' => 'Jasper est un copilote IA qui aide les créateurs et les entreprises à générer du contenu marketing de haute qualité, en respectant la voix de la marque. Il offre des modèles puissants pour les blogues, les médias sociaux et les campagnes publicitaires.', 'core_features' => "Jasper Brand Voice, Jasper Chat, Mode Boss, Générateur d'art IA, Intégration Surfer SEO", 'use_cases' => "Rédaction d'articles de blogue, Création de copies publicitaires, Stratégie de contenu marketing, Scripts vidéo", 'pros' => 'Qualité de texte supérieure, Respect du ton de marque, Grande variété de modèles', 'cons' => "Coût élevé, Courbe d'apprentissage pour les fonctions avancées", 'target_audience' => ['Équipes marketing', 'Agences', 'Entreprises']],
            ['name' => 'Copy.ai', 'slug' => 'copy-ai', 'url' => 'https://www.copy.ai', 'pricing' => 'freemium', 'launch_year' => 2020, 'short_description' => 'Une plateforme IA orientée GTM pour automatiser les ventes et le marketing.', 'description' => "Copy.ai se spécialise dans l'automatisation des flux de travail pour les équipes de vente et de marketing. Il permet de générer rapidement des courriels de prospection, des publications sociales et du contenu de site web à grande échelle.", 'core_features' => 'Workflows automatisés, Chat by Copy.ai, Modèles de rédaction, Support multilingue', 'use_cases' => 'Courriels de vente, Descriptions de produits e-commerce, Légendes de réseaux sociaux', 'pros' => 'Interface très intuitive, Excellent plan gratuit, Génération rapide', 'cons' => 'Moins adapté aux contenus très longs, Parfois répétitif', 'target_audience' => ['Équipes de vente', 'Gestionnaires médias sociaux', 'Freelances']],
            ['name' => 'Writesonic', 'slug' => 'writesonic', 'url' => 'https://writesonic.com', 'pricing' => 'freemium', 'launch_year' => 2021, 'short_description' => 'Un outil de rédaction IA polyvalent optimisé pour le SEO et le contenu factuel.', 'description' => 'Writesonic combine la génération de texte avec des données en temps réel via Google pour créer du contenu précis et optimisé pour le référencement. Il inclut également Chatsonic, une alternative puissante à ChatGPT.', 'core_features' => 'AI Article Writer, Chatsonic (accès web), Photosonic, Optimisation SEO', 'use_cases' => 'Articles optimisés SEO, Pages de destination, Descriptions de produits, Chatbot factuel', 'pros' => 'Intégration de données en temps réel, Interface conviviale, Bon rapport qualité-prix', 'cons' => 'La qualité varie selon le modèle choisi, Système de crédits complexe', 'target_audience' => ['Blogueurs', 'Spécialistes SEO', 'Marketeurs numériques']],
            ['name' => 'Grammarly', 'slug' => 'grammarly', 'url' => 'https://www.grammarly.com', 'pricing' => 'freemium', 'launch_year' => 2009, 'short_description' => "L'assistant d'écriture indispensable pour la grammaire, le style et le ton.", 'description' => "Grammarly va au-delà de la simple correction orthographique en utilisant l'IA pour améliorer la clarté, l'engagement et le ton de vos textes. Il s'intègre partout où vous écrivez pour assurer une communication professionnelle sans fautes.", 'core_features' => 'Correction grammaticale avancée, Détection de ton, Réécriture de phrases, Détecteur de plagiat, GrammarlyGO', 'use_cases' => 'Correction de courriels professionnels, Révision académique, Amélioration de style rédactionnel', 'pros' => 'Intégration universelle, Explications pédagogiques, Haute précision', 'cons' => 'Fonctionnalités avancées couteuses, Faux positifs occasionnels sur le style créatif', 'target_audience' => ['Étudiants', 'Professionnels', 'Rédacteurs']],
            ['name' => 'QuillBot', 'slug' => 'quillbot', 'url' => 'https://quillbot.com', 'pricing' => 'freemium', 'launch_year' => 2017, 'short_description' => 'Un outil de paraphrase et de résumé pour reformuler et clarifier vos textes.', 'description' => 'QuillBot est spécialisé dans la réécriture de contenu. Il aide à reformuler des phrases, résumer de longs documents et éviter le plagiat involontaire tout en conservant le sens original du texte.', 'core_features' => 'Paraphraseur multi-modes, Résumeur de texte, Correcteur grammatical, Générateur de citations, Traducteur', 'use_cases' => "Reformulation de textes académiques, Simplification de contenu complexe, Résumé d'articles", 'pros' => 'Excellent outil de paraphrase, Interface simple, Extensions utiles', 'cons' => 'Limité en génération de contenu original, Plan gratuit restrictif', 'target_audience' => ['Étudiants', 'Chercheurs', 'Rédacteurs de contenu']],
            ['name' => 'Rytr', 'slug' => 'rytr', 'url' => 'https://rytr.me', 'pricing' => 'freemium', 'launch_year' => 2021, 'short_description' => "Un assistant d'écriture IA abordable et simple pour générer du contenu rapidement.", 'description' => "Rytr se positionne comme une solution économique et facile à utiliser pour les besoins quotidiens de rédaction. Il propose une interface épurée et de nombreux cas d'utilisation prédéfinis pour générer du texte en quelques clics.", 'core_features' => "Plus de 40 cas d'utilisation, Analyseur de ton, Vérificateur de plagiat, Génération d'images", 'use_cases' => 'Rédaction de courriels, Idées de blogue, Descriptions YouTube, Profils bio', 'pros' => 'Très abordable, Facile à prendre en main, Rapide', 'cons' => 'Moins puissant pour les longs formats, Fonctionnalités limitées', 'target_audience' => ['Petites entreprises', 'Freelances débutants', 'Utilisateurs occasionnels']],
            ['name' => 'Sudowrite', 'slug' => 'sudowrite', 'url' => 'https://www.sudowrite.com', 'pricing' => 'paid', 'launch_year' => 2020, 'short_description' => "Une IA conçue spécifiquement pour les auteurs de fiction et l'écriture créative.", 'description' => "Sudowrite est l'outil de prédilection pour les romanciers. Il aide à surmonter le syndrome de la page blanche, à développer des intrigues, à décrire des scènes sensorielles et à étoffer des personnages avec une approche narrative unique.", 'core_features' => 'Story Engine, Fonction Describe (sensorielle), Brainstorming, Rewrite, Canvas', 'use_cases' => 'Écriture de romans, Développement de scénarios, Poésie, Fanfiction', 'pros' => 'Excellent pour la prose créative, Fonctionnalités uniques pour auteurs, Interface immersive', 'cons' => 'Pas adapté au contenu marketing ou factuel, Cout mensuel', 'target_audience' => ['Auteurs de fiction', 'Scénaristes', 'Écrivains créatifs']],
            ['name' => 'Surfer SEO', 'slug' => 'surfer-seo', 'url' => 'https://surferseo.com', 'pricing' => 'paid', 'launch_year' => 2017, 'short_description' => "Une plateforme d'intelligence de contenu pour dominer les résultats de recherche.", 'description' => 'Surfer SEO analyse les pages les mieux classées pour fournir des recommandations précises sur la structure, les mots-clés et la longueur du contenu. Son module Surfer AI peut rédiger des articles entiers optimisés pour le référencement.', 'core_features' => 'Éditeur de contenu, Audit SERP, Recherche de mots-clés, Surfer AI, Grow Flow', 'use_cases' => 'Optimisation on-page, Stratégie de contenu SEO, Audit de site existant', 'pros' => 'Données basées sur les faits, Amélioration concrète du ranking, Intégrations (Jasper, WordPress)', 'cons' => "Prix élevé, Courbe d'apprentissage technique", 'target_audience' => ['Agences SEO', 'Éditeurs de sites', 'Consultants marketing']],
        ];

        foreach ($tools as $data) {
            $tool = Tool::firstOrCreate(
                ['slug->fr_CA' => $data['slug']],
                ['name' => json_encode(['fr_CA' => $data['name']]), 'url' => $data['url'], 'pricing' => $data['pricing'], 'status' => 'published', 'sort_order' => 30, 'website_type' => 'website', 'launch_year' => $data['launch_year'], 'target_audience' => $data['target_audience']]
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
