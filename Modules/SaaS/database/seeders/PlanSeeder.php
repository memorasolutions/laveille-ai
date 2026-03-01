<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
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
                'name' => 'Free',
                'slug' => 'free',
                'price' => 0,
                'currency' => 'cad',
                'interval' => 'monthly',
                'trial_days' => 0,
                'features' => ['1_user', 'basic_support', '1gb_storage'],
                'is_active' => true,
                'sort_order' => 0,
            ]
        );

        Plan::updateOrCreate(
            ['slug' => 'pro'],
            [
                'name' => 'Pro',
                'slug' => 'pro',
                'price' => 29.99,
                'currency' => 'cad',
                'interval' => 'monthly',
                'trial_days' => 14,
                'features' => ['10_users', 'priority_support', '50gb_storage', 'api_access', 'export'],
                'is_active' => true,
                'sort_order' => 1,
            ]
        );

        Plan::updateOrCreate(
            ['slug' => 'enterprise'],
            [
                'name' => 'Enterprise',
                'slug' => 'enterprise',
                'price' => 99.99,
                'currency' => 'cad',
                'interval' => 'monthly',
                'trial_days' => 30,
                'features' => ['unlimited_users', 'dedicated_support', 'unlimited_storage', 'api_access', 'export', 'webhooks', 'sso'],
                'is_active' => true,
                'sort_order' => 2,
            ]
        );
    }
}
