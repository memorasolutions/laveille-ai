<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Laravel\Pennant\Feature;

class FeatureFlagSeeder extends Seeder
{
    public function run(): void
    {
        $activeModules = [
            'module-translation',
            'module-search',
            'module-export',
            'module-webhooks',
            'module-media',
            'module-backup',
        ];

        $inactiveModules = [
            'module-saas',
            'module-tenancy',
            'module-sms',
        ];

        foreach ($activeModules as $flag) {
            Feature::activateForEveryone($flag);
        }

        foreach ($inactiveModules as $flag) {
            Feature::deactivateForEveryone($flag);
        }
    }
}
