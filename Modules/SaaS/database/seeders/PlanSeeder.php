<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\SaaS\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\SaaS\Models\Plan;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        Plan::updateOrCreate(
            ['slug' => 'free'],
            [
                'name' => 'Gratuit',
                'price' => 0.00,
                'currency' => 'cad',
                'interval' => 'monthly',
                'trial_days' => 0,
                'is_active' => true,
                'sort_order' => 0,
                'description' => 'Accès complet au contenu et aux fonctionnalités de base',
                'features' => [
                    'articles_illimites',
                    'repertoire_consultation',
                    'glossaire_consultation',
                    'acronymes_consultation',
                    'newsletter_hebdomadaire',
                    'bookmarks_10',
                    'votes_consultation',
                    'outils_gratuits',
                    'propositions_1_par_mois',
                ],
            ]
        );

        Plan::updateOrCreate(
            ['slug' => 'pro'],
            [
                'name' => 'Pro',
                'price' => 12.00,
                'currency' => 'cad',
                'interval' => 'monthly',
                'trial_days' => 14,
                'is_active' => true,
                'sort_order' => 1,
                'description' => 'Accès complet + fonctionnalités communautaires avancées',
                'features' => [
                    'articles_illimites',
                    'articles_exclusifs',
                    'repertoire_complet',
                    'repertoire_alertes',
                    'glossaire_complet',
                    'acronymes_complet',
                    'newsletter_quotidienne',
                    'newsletter_personnalisee',
                    'bookmarks_illimites',
                    'bookmarks_collections',
                    'votes_illimites',
                    'suggestions_illimitees',
                    'outils_gratuits',
                    'propositions_illimitees',
                    'badge_pro',
                    'reputation_complete',
                    'leaderboard',
                    'export_favoris',
                ],
            ]
        );

        Plan::updateOrCreate(
            ['slug' => 'enterprise'],
            [
                'name' => 'Enterprise',
                'price' => 49.00,
                'currency' => 'cad',
                'interval' => 'monthly',
                'trial_days' => 30,
                'is_active' => false,
                'sort_order' => 2,
                'description' => 'Pour les équipes et organisations',
                'features' => [
                    'tout_pro',
                    'acces_equipe',
                    'support_dedie',
                    'api_acces',
                ],
            ]
        );
    }
}
