<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

namespace Modules\AI\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\AI\Services\KnowledgeBaseService;

class KnowledgeBaseSeeder extends Seeder
{
    public function run(): void
    {
        $kb = app(KnowledgeBaseService::class);

        $documents = [
            [
                'title' => 'À propos de MEMORA solutions',
                'content' => "MEMORA solutions est une agence web québécoise fondée pour accompagner les PME dans leur transformation numérique. Notre équipe de développeurs, designers et stratèges partage une passion commune : créer des solutions web performantes et accessibles.\n\nNous sommes spécialisés dans le développement sur mesure avec Laravel et WordPress, deux technologies qui nous permettent de répondre à une grande variété de besoins. Que vous soyez une startup en démarrage ou une entreprise établie, nous adaptons notre approche à votre réalité.\n\nBasés au Québec, nous travaillons principalement avec des entreprises canadiennes, mais notre expertise nous permet d'accompagner des clients partout dans le monde francophone. Notre approche se distingue par une écoute attentive, une transparence dans nos communications et un souci constant de la qualité.",
            ],
            [
                'title' => 'Services de création de sites web',
                'content' => "MEMORA solutions offre une gamme complète de services de création web adaptés à vos besoins.\n\nSites vitrines : parfaits pour présenter votre entreprise, vos services et vos réalisations. Design moderne, responsive et optimisé pour le référencement.\n\nSites e-commerce : boutiques en ligne complètes avec gestion des produits, paiements sécurisés (Stripe), suivi des commandes et tableaux de bord.\n\nApplications sur mesure : solutions personnalisées développées avec Laravel pour répondre à des besoins spécifiques (portails clients, systèmes de gestion, plateformes SaaS).\n\nNotre processus de création suit 5 étapes claires :\n1. Découverte : rencontre gratuite pour comprendre vos objectifs\n2. Design : maquettes et prototypes pour valider la direction visuelle\n3. Développement : programmation avec les meilleures pratiques\n4. Tests : vérification complète de la qualité et de la performance\n5. Lancement : mise en ligne, formation et accompagnement",
            ],
            [
                'title' => 'Services de marketing numérique',
                'content' => "Notre équipe de marketing numérique vous aide à attirer, convertir et fidéliser vos clients en ligne.\n\nSEO (référencement naturel) : audit technique, optimisation du contenu, stratégie de mots-clés, création de backlinks de qualité. Résultats mesurables en 3 à 6 mois.\n\nPublicité en ligne : campagnes Google Ads et Facebook Ads ciblées, gestion du budget publicitaire, optimisation continue du retour sur investissement.\n\nRéseaux sociaux : stratégie de contenu, création de publications, gestion de communauté sur Facebook, Instagram, LinkedIn.\n\nEmail marketing : création de campagnes d'infolettres, automatisation marketing, segmentation de votre audience.\n\nAnalyse de données : installation et configuration de Google Analytics, tableaux de bord personnalisés, rapports mensuels de performance.\n\nStratégie de contenu : rédaction de blogues, études de cas, pages d'atterrissage optimisées pour la conversion.",
            ],
            [
                'title' => 'Tarifs et forfaits indicatifs',
                'content' => "Voici nos fourchettes de prix pour vous donner une idée de l'investissement requis. Chaque projet étant unique, nous vous invitons à nous contacter pour obtenir un devis personnalisé gratuit.\n\nSite vitrine : à partir de 2 000 $ (4-6 semaines)\nInclut design responsive, 5-10 pages, formulaire de contact, optimisation SEO de base.\n\nSite e-commerce : à partir de 5 000 $ (6-10 semaines)\nInclut catalogue produits, paiements en ligne, gestion des commandes, tableau de bord.\n\nApplication sur mesure : à partir de 10 000 $ (8-16 semaines)\nInclut analyse complète, développement Laravel, API, formation.\n\nSEO mensuel : 500 $ à 1 500 $/mois\nSelon la compétitivité de votre secteur et vos objectifs de croissance.\n\nMaintenance et hébergement : à partir de 100 $/mois\nMises à jour, sauvegardes, surveillance, support technique.\n\nConsultation gratuite disponible pour discuter de votre projet et obtenir un devis adapté à vos besoins.",
            ],
            [
                'title' => 'Processus de travail et délais',
                'content' => "Chez MEMORA solutions, nous suivons un processus structuré en 5 étapes pour garantir le succès de chaque projet.\n\nÉtape 1 - Découverte et analyse des besoins (1 semaine)\nRencontre gratuite pour comprendre votre vision, vos objectifs d'affaires et les besoins de vos utilisateurs. Nous analysons votre marché et votre concurrence.\n\nÉtape 2 - Proposition et devis (3-5 jours)\nNous préparons une proposition détaillée avec le plan du projet, les livrables, le calendrier et le budget. Aucun engagement avant votre approbation.\n\nÉtape 3 - Design et maquettes (1-2 semaines)\nCréation des maquettes visuelles avec des outils comme Figma. Vous validez le design avant le développement.\n\nÉtape 4 - Développement et tests (2-8 semaines)\nProgrammation avec les meilleures pratiques, tests automatisés, révisions de code. Vous suivez l'avancement en temps réel.\n\nÉtape 5 - Lancement et formation (1 semaine)\nMise en ligne, formation à l'utilisation de votre plateforme, documentation. Support inclus pendant 30 jours après le lancement.\n\nDélais typiques : 4-8 semaines pour un site vitrine, 8-12 semaines pour un e-commerce, 12-20 semaines pour une application sur mesure.",
            ],
            [
                'title' => 'Questions fréquentes (FAQ)',
                'content' => "Combien coûte la création d'un site web ?\nLe coût varie selon la complexité du projet. Un site vitrine commence à 2 000 $, un e-commerce à 5 000 $ et une application sur mesure à 10 000 $. Contactez-nous pour un devis gratuit adapté à vos besoins.\n\nQuels sont vos délais de livraison ?\nUn site vitrine prend généralement 4 à 8 semaines. Un e-commerce, 8 à 12 semaines. Une application sur mesure, 12 à 20 semaines. Ces délais peuvent varier selon la complexité et votre réactivité pour les validations.\n\nQuelles technologies utilisez-vous ?\nNous travaillons principalement avec Laravel (PHP) pour les applications sur mesure et WordPress pour les sites vitrines et blogues. Nous utilisons aussi React, Livewire et Tailwind CSS pour les interfaces modernes.\n\nOffrez-vous du support après le lancement ?\nOui, nous offrons 30 jours de support gratuit après le lancement. Ensuite, nous proposons des forfaits de maintenance à partir de 100 $/mois incluant mises à jour, sauvegardes et support technique.\n\nComment puis-je commencer ?\nContactez-nous pour planifier une consultation gratuite. Nous discuterons de vos besoins, de votre budget et de vos objectifs pour vous proposer la meilleure solution.",
            ],
        ];

        foreach ($documents as $doc) {
            $kb->addDocument(
                title: $doc['title'],
                content: $doc['content'],
                sourceType: 'manual',
                metadata: ['category' => 'general'],
            );
        }
    }
}
