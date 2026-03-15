<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Privacy\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Privacy\Models\CookieCategory;

class CookieCategorySeeder extends Seeder
{
    public function run(): void
    {
        CookieCategory::updateOrCreate(
            ['name' => 'essential'],
            [
                'label' => 'Cookies essentiels',
                'description' => 'Necessaires au fonctionnement du site. Session, CSRF, securite.',
                'required' => true,
                'order' => 1,
                'is_active' => true,
            ]
        );

        CookieCategory::updateOrCreate(
            ['name' => 'functional'],
            [
                'label' => 'Cookies fonctionnels',
                'description' => 'Ameliorent la convivialite : preferences de langue, theme.',
                'required' => false,
                'order' => 2,
                'is_active' => true,
            ]
        );

        CookieCategory::updateOrCreate(
            ['name' => 'analytics'],
            [
                'label' => 'Cookies analytiques',
                'description' => "Mesure d'audience et statistiques de visite anonymisees.",
                'required' => false,
                'order' => 3,
                'is_active' => true,
            ]
        );

        CookieCategory::updateOrCreate(
            ['name' => 'marketing'],
            [
                'label' => 'Cookies marketing',
                'description' => 'Publicites personnalisees et suivi inter-sites.',
                'required' => false,
                'order' => 4,
                'is_active' => true,
            ]
        );
    }
}
