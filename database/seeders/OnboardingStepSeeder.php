<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\OnboardingStep;
use Illuminate\Database\Seeder;

class OnboardingStepSeeder extends Seeder
{
    public function run(): void
    {
        $steps = [
            [
                'slug' => 'welcome',
                'title' => 'Bienvenue',
                'description' => 'Bienvenue sur la plateforme!',
                'icon' => 'M12 6v6m0 0v6m0-6h6m-6 0H6',
                'order' => 1,
                'is_active' => true,
            ],
            [
                'slug' => 'profile',
                'title' => 'Votre profil',
                'description' => 'Complétez votre profil pour une meilleure expérience.',
                'icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
                'order' => 2,
                'is_active' => true,
                'fields' => ['name', 'bio'],
            ],
            [
                'slug' => 'preferences',
                'title' => 'Préférences',
                'description' => 'Configurez vos préférences.',
                'icon' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0',
                'order' => 3,
                'is_active' => true,
                'fields' => ['locale', 'timezone'],
            ],
            [
                'slug' => 'done',
                'title' => 'Terminé',
                'description' => 'Vous êtes prêt à commencer!',
                'icon' => 'M5 13l4 4L19 7',
                'order' => 4,
                'is_active' => true,
            ],
        ];

        foreach ($steps as $data) {
            OnboardingStep::updateOrCreate(
                ['slug' => $data['slug']],
                $data,
            );
        }
    }
}
