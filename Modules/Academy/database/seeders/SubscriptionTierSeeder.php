<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Seed NEUTRE de 3 paliers d'exemple (freemium/pro/organisation). Idempotent
 * (updateOrCreate par slug). ZÉRO STRIPE : `price_cents` est laissé à null
 * (Pro/Organisation) ou 0 (Freemium) — « à configurer par l'administrateur » —
 * et `stripe_price_id` reste null. La liste de fonctionnalités par palier est
 * un point de départ éditable depuis /admin/academy/subscription-tiers, pas
 * une décision commerciale figée.
 *
 * Ce seeder n'est PAS exécuté automatiquement au déploiement (deploy.yml ne
 * lance que les migrations) : à lancer manuellement au besoin :
 *   php artisan db:seed --class="Modules\Academy\Database\Seeders\SubscriptionTierSeeder"
 */

declare(strict_types=1);

namespace Modules\Academy\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Academy\Models\SubscriptionTier;

class SubscriptionTierSeeder extends Seeder
{
    public function run(): void
    {
        SubscriptionTier::updateOrCreate(
            ['slug' => 'freemium'],
            [
                'name'            => 'Freemium',
                'description'     => 'Accès de base à l’Académie, sans fonctionnalité premium.',
                'price_cents'     => 0,
                'billing_period'  => 'monthly',
                'features'        => [],
                'max_seats'       => null,
                'stripe_price_id' => null,
                'is_default'      => true,
                'is_active'       => true,
                'sort_order'      => 0,
            ]
        );

        SubscriptionTier::updateOrCreate(
            ['slug' => 'pro'],
            [
                'name'            => 'Pro',
                'description'     => 'Pour l’apprenant individuel qui veut aller plus loin (prix à configurer).',
                'price_cents'     => null,
                'billing_period'  => 'monthly',
                'features'        => [
                    'academy_gamification',
                    'academy_srs',
                    'academy_open_badges',
                    'academy_live_sessions',
                ],
                'max_seats'       => null,
                'stripe_price_id' => null,
                'is_default'      => false,
                'is_active'       => true,
                'sort_order'      => 10,
            ]
        );

        SubscriptionTier::updateOrCreate(
            ['slug' => 'organisation'],
            [
                'name'            => 'Organisation',
                'description'     => 'Pour une équipe/organisation avec plusieurs sièges (prix et sièges à configurer).',
                'price_cents'     => null,
                'billing_period'  => 'monthly',
                'features'        => array_keys(config('academy.subscription_tier_feature_keys', [])),
                'max_seats'       => null,
                'stripe_price_id' => null,
                'is_default'      => false,
                'is_active'       => true,
                'sort_order'      => 20,
            ]
        );
    }
}
