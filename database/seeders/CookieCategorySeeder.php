<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\CookieCategory;
use Illuminate\Database\Seeder;

class CookieCategorySeeder extends Seeder
{
    public function run(): void
    {
        CookieCategory::updateOrCreate(
            ['name' => 'essential'],
            [
                'label' => 'Cookies essentiels',
                'description' => 'Nécessaires au fonctionnement du site. Session, CSRF, sécurité.',
                'required' => true,
                'order' => 1,
                'is_active' => true,
            ]
        );

        CookieCategory::updateOrCreate(
            ['name' => 'functional'],
            [
                'label' => 'Cookies fonctionnels',
                'description' => 'Améliorent la convivialité : préférences de langue, thème.',
                'required' => false,
                'order' => 2,
                'is_active' => true,
            ]
        );

        CookieCategory::updateOrCreate(
            ['name' => 'analytics'],
            [
                'label' => 'Cookies analytiques',
                'description' => "Mesure d'audience et statistiques de visite anonymisées.",
                'required' => false,
                'order' => 3,
                'is_active' => true,
            ]
        );

        CookieCategory::updateOrCreate(
            ['name' => 'marketing'],
            [
                'label' => 'Cookies marketing',
                'description' => 'Publicités personnalisées et suivi inter-sites.',
                'required' => false,
                'order' => 4,
                'is_active' => true,
            ]
        );
    }
}
