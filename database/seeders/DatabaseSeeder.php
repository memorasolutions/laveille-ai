<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Rôles et permissions (fondation)
        $this->call([
            RolesAndPermissionsSeeder::class,
        ]);

        // 2. Superadmin principal (ID #1 - JAMAIS supprimer)
        $superAdmin = User::updateOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@example.com')],
            [
                'name' => env('ADMIN_NAME', 'Admin'),
                'password' => bcrypt(env('ADMIN_PASSWORD', 'Admin123!')),
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );
        $superAdmin->assignRole('super_admin');

        $admin = User::firstOrCreate(
            ['email' => 'moderator@laravel-core.test'],
            [
                'name' => 'Modérateur',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]
        );
        $admin->assignRole('admin');

        // 3. Feature flags Pennant
        $this->call(FeatureFlagSeeder::class);

        // 4. Seeders des modules actifs
        $moduleSeeders = [
            \Modules\Settings\Database\Seeders\SettingsDatabaseSeeder::class,
            \Modules\SEO\Database\Seeders\SEODatabaseSeeder::class,
            \Modules\SaaS\Database\Seeders\SaaSDatabaseSeeder::class,
            CookieCategorySeeder::class,
            OnboardingStepSeeder::class,
        ];

        foreach ($moduleSeeders as $seeder) {
            if (class_exists($seeder)) {
                $this->call($seeder);
            }
        }

        // 5. Données de démo (hors production)
        if (! app()->isProduction()) {
            $users = User::factory()->count(5)->create();
            foreach ($users as $user) {
                $user->assignRole('user');
            }
        }
    }
}
