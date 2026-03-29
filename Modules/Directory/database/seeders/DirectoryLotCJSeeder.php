<?php

declare(strict_types=1);

namespace Modules\Directory\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Directory\Models\Category;
use Modules\Directory\Models\Tool;

class DirectoryLotCJSeeder extends Seeder
{
    public function run(): void
    {
        $tools = [
            // Lot C - AI Video
            ['name' => 'Synthesia', 'slug' => 'synthesia', 'url' => 'https://www.synthesia.io', 'pricing' => 'paid', 'launch_year' => 2017, 'short_description' => 'Plateforme de création de vidéos avec des avatars IA réalistes à partir de texte.', 'cat' => 'video'],
            ['name' => 'D-ID', 'slug' => 'd-id', 'url' => 'https://www.d-id.com', 'pricing' => 'freemium', 'launch_year' => 2017, 'short_description' => "Outil qui génère des vidéos de visages parlants animés par l'intelligence artificielle.", 'cat' => 'video'],
            ['name' => 'Sora', 'slug' => 'sora', 'url' => 'https://sora.com', 'pricing' => 'paid', 'launch_year' => 2024, 'short_description' => "Modèle d'OpenAI capable de générer des vidéos réalistes directement à partir de prompts texte.", 'cat' => 'video'],
            ['name' => 'Luma AI', 'slug' => 'luma-ai', 'url' => 'https://lumalabs.ai', 'pricing' => 'freemium', 'launch_year' => 2021, 'short_description' => "Outil de génération vidéo et de captures 3D photoréalistes propulsé par l'IA.", 'cat' => 'video'],
            ['name' => 'Kling AI', 'slug' => 'kling-ai', 'url' => 'https://klingai.com', 'pricing' => 'freemium', 'launch_year' => 2024, 'short_description' => "Générateur vidéo IA développé par Kuaishou offrant des clips haute qualité à partir de texte ou d'images.", 'cat' => 'video'],
            ['name' => 'Hailuo AI', 'slug' => 'hailuo', 'url' => 'https://hailuo.ai', 'pricing' => 'freemium', 'launch_year' => 2024, 'short_description' => 'Plateforme de generation video 4K avec physique realiste et synchronisation audio, developpee par MiniMax.', 'cat' => 'video'],

            // Lot D - AI Coding
            ['name' => 'Windsurf', 'slug' => 'windsurf', 'url' => 'https://codeium.com/windsurf', 'pricing' => 'freemium', 'launch_year' => 2024, 'short_description' => 'Éditeur de code IA agentique développé par Codeium qui combine copilotage et actions autonomes.', 'cat' => 'developpement'],
            ['name' => 'Replit', 'slug' => 'replit', 'url' => 'https://replit.com', 'pricing' => 'freemium', 'launch_year' => 2016, 'short_description' => 'Environnement de développement en ligne avec un assistant IA intégré pour coder, déployer et collaborer.', 'cat' => 'developpement'],
            ['name' => 'Tabnine', 'slug' => 'tabnine', 'url' => 'https://www.tabnine.com', 'pricing' => 'freemium', 'launch_year' => 2018, 'short_description' => "Assistant de complétion de code IA axé sur la confidentialité qui s'intègre aux principaux IDE.", 'cat' => 'developpement'],
            ['name' => 'Codeium', 'slug' => 'codeium', 'url' => 'https://codeium.com', 'pricing' => 'freemium', 'launch_year' => 2022, 'short_description' => "Outil gratuit d'autocomplétion et de recherche de code propulsé par l'IA pour plus de 70 langages.", 'cat' => 'developpement'],
            ['name' => 'Devin', 'slug' => 'devin', 'url' => 'https://devin.ai', 'pricing' => 'paid', 'launch_year' => 2024, 'short_description' => 'Premier ingénieur logiciel IA autonome capable de planifier, coder et déployer des projets complets.', 'cat' => 'developpement'],
            ['name' => 'GitHub Copilot', 'slug' => 'github-copilot', 'url' => 'https://github.com/features/copilot', 'pricing' => 'freemium', 'launch_year' => 2021, 'short_description' => "Assistant de programmation IA de GitHub qui suggère du code en temps réel directement dans l'éditeur.", 'cat' => 'developpement'],

            // Lot E - AI Audio
            ['name' => 'Murf AI', 'slug' => 'murf-ai', 'url' => 'https://murf.ai', 'pricing' => 'freemium', 'launch_year' => 2020, 'short_description' => 'Générateur de voix-off IA réalistes offrant plus de 120 voix dans une vingtaine de langues.', 'cat' => 'audio-voix'],
            ['name' => 'Play.ht', 'slug' => 'play-ht', 'url' => 'https://play.ht', 'pricing' => 'freemium', 'launch_year' => 2016, 'short_description' => 'Plateforme de synthèse vocale IA ultra-réaliste pour créer des voix-off et du contenu audio.', 'cat' => 'audio-voix'],
            ['name' => 'Descript', 'slug' => 'descript', 'url' => 'https://www.descript.com', 'pricing' => 'freemium', 'launch_year' => 2017, 'short_description' => 'Éditeur audio et vidéo tout-en-un qui permet de monter du contenu en modifiant simplement le texte transcrit.', 'cat' => 'audio-voix'],
            ['name' => 'AIVA', 'slug' => 'aiva', 'url' => 'https://www.aiva.ai', 'pricing' => 'freemium', 'launch_year' => 2016, 'short_description' => 'Compositeur musical IA qui génère des pièces originales pour des films, jeux vidéo et publicités.', 'cat' => 'audio-voix'],
            ['name' => 'Podcastle', 'slug' => 'podcastle', 'url' => 'https://podcastle.ai', 'pricing' => 'freemium', 'launch_year' => 2020, 'short_description' => "Studio de podcasting IA offrant l'enregistrement, le montage et la transcription dans une seule plateforme.", 'cat' => 'audio-voix'],

            // Lot F - AI Productivité
            ['name' => 'Otter.ai', 'slug' => 'otter-ai', 'url' => 'https://otter.ai', 'pricing' => 'freemium', 'launch_year' => 2016, 'short_description' => 'Assistant de réunion IA qui transcrit, résume et génère des notes en temps réel.', 'cat' => 'productivite'],
            ['name' => 'Fireflies', 'slug' => 'fireflies', 'url' => 'https://fireflies.ai', 'pricing' => 'freemium', 'launch_year' => 2016, 'short_description' => 'Outil IA qui enregistre, transcrit et analyse automatiquement les conversations de réunion.', 'cat' => 'productivite'],
            ['name' => 'Zapier AI', 'slug' => 'zapier-ai', 'url' => 'https://zapier.com/ai', 'pricing' => 'freemium', 'launch_year' => 2023, 'short_description' => "Couche d'intelligence artificielle de Zapier qui permet de créer des automatisations par description en langage naturel.", 'cat' => 'productivite'],
            ['name' => 'Taskade', 'slug' => 'taskade', 'url' => 'https://www.taskade.com', 'pricing' => 'freemium', 'launch_year' => 2017, 'short_description' => "Espace de travail collaboratif propulsé par l'IA pour gérer des projets, tâches et notes avec des agents autonomes.", 'cat' => 'productivite'],
            ['name' => 'Motion', 'slug' => 'motion', 'url' => 'https://www.usemotion.com', 'pricing' => 'paid', 'launch_year' => 2019, 'short_description' => 'Gestionnaire de calendrier et de tâches IA qui planifie automatiquement ta journée de façon optimale.', 'cat' => 'productivite'],
            ['name' => 'Reclaim AI', 'slug' => 'reclaim-ai', 'url' => 'https://reclaim.ai', 'pricing' => 'freemium', 'launch_year' => 2019, 'short_description' => 'Outil de planification intelligente qui optimise automatiquement ton calendrier Google pour protéger ton temps de focus.', 'cat' => 'productivite'],

            // Lot G - AI Recherche
            ['name' => 'You.com', 'slug' => 'you-com', 'url' => 'https://you.com', 'pricing' => 'freemium', 'launch_year' => 2021, 'short_description' => 'Moteur de recherche conversationnel IA qui fournit des réponses sourcées et personnalisées.', 'cat' => 'recherche'],
            ['name' => 'Elicit', 'slug' => 'elicit', 'url' => 'https://elicit.com', 'pricing' => 'freemium', 'launch_year' => 2021, 'short_description' => 'Assistant de recherche IA qui analyse des articles scientifiques et extrait automatiquement les données clés.', 'cat' => 'recherche'],
            ['name' => 'Consensus', 'slug' => 'consensus', 'url' => 'https://consensus.app', 'pricing' => 'freemium', 'launch_year' => 2022, 'short_description' => 'Moteur de recherche IA qui interroge directement la littérature scientifique pour fournir des réponses fondées sur des preuves.', 'cat' => 'recherche'],
            ['name' => 'Semantic Scholar', 'slug' => 'semantic-scholar', 'url' => 'https://www.semanticscholar.org', 'pricing' => 'free', 'launch_year' => 2015, 'short_description' => "Moteur de recherche académique gratuit développé par AI2 qui utilise l'IA pour naviguer dans des millions de publications.", 'cat' => 'recherche'],

            // Lot H - AI Éducation
            ['name' => 'Khanmigo', 'slug' => 'khanmigo', 'url' => 'https://www.khanacademy.org/khan-labs', 'pricing' => 'freemium', 'launch_year' => 2023, 'short_description' => 'Tuteur IA de Khan Academy qui guide les élèves pas à pas sans donner directement les réponses.', 'cat' => 'education'],
            ['name' => 'Duolingo Max', 'slug' => 'duolingo-max', 'url' => 'https://www.duolingo.com/max', 'pricing' => 'paid', 'launch_year' => 2023, 'short_description' => "Version premium de Duolingo intégrant des conversations IA et des explications personnalisées pour l'apprentissage des langues.", 'cat' => 'education'],
            ['name' => 'Photomath', 'slug' => 'photomath', 'url' => 'https://photomath.com', 'pricing' => 'freemium', 'launch_year' => 2014, 'short_description' => 'Application qui résout des problèmes mathématiques en les scannant avec la caméra et explique chaque étape.', 'cat' => 'education'],

            // Lot I - AI SEO/GEO/AEO
            ['name' => 'Semrush AI', 'slug' => 'semrush-ai', 'url' => 'https://www.semrush.com', 'pricing' => 'paid', 'launch_year' => 2023, 'short_description' => "Suite SEO complète enrichie de fonctionnalités IA pour l'analyse de mots-clés, l'audit et la rédaction optimisée.", 'cat' => 'seo-geo-aeo'],
            ['name' => 'Frase', 'slug' => 'frase', 'url' => 'https://www.frase.io', 'pricing' => 'paid', 'launch_year' => 2016, 'short_description' => 'Outil de rédaction SEO IA qui recherche, planifie et optimise le contenu pour mieux se positionner sur Google.', 'cat' => 'seo-geo-aeo'],
            ['name' => 'Clearscope', 'slug' => 'clearscope', 'url' => 'https://www.clearscope.io', 'pricing' => 'paid', 'launch_year' => 2016, 'short_description' => "Plateforme d'optimisation de contenu SEO qui analyse la concurrence et recommande les termes clés à intégrer.", 'cat' => 'seo-geo-aeo'],

            // Lot J - Agents/Divers
            ['name' => 'Humata', 'slug' => 'humata', 'url' => 'https://www.humata.ai', 'pricing' => 'freemium', 'launch_year' => 2023, 'short_description' => 'Agent IA qui analyse tes documents PDF et répond instantanément à tes questions sur leur contenu.', 'cat' => 'agents-ia'],
            ['name' => 'ClickUp Brain', 'slug' => 'clickup-brain', 'url' => 'https://clickup.com/ai', 'pricing' => 'paid', 'launch_year' => 2023, 'short_description' => 'Assistant IA intégré à ClickUp qui automatise les tâches, résume les projets et génère du contenu.', 'cat' => 'agents-ia'],
        ];

        foreach ($tools as $data) {
            $category = Category::where('slug->fr_CA', $data['cat'])->first();

            $tool = Tool::firstOrCreate(
                ['slug->fr_CA' => $data['slug']],
                ['name' => json_encode(['fr_CA' => $data['name']]), 'url' => $data['url'], 'pricing' => $data['pricing'], 'status' => 'published', 'sort_order' => 50, 'website_type' => 'website', 'launch_year' => $data['launch_year']]
            );

            $tool->setTranslation('name', 'fr_CA', $data['name']);
            $tool->setTranslation('slug', 'fr_CA', $data['slug']);
            $tool->setTranslation('short_description', 'fr_CA', $data['short_description']);
            $tool->save();

            if ($category) {
                $tool->categories()->syncWithoutDetaching([$category->id]);
            }

            $this->command->info("Created: {$data['slug']}");
        }
    }
}
