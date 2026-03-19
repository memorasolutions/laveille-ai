<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Nwidart\Modules\Facades\Module;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Rôles et permissions (fondation)
        $this->call([
            RolesAndPermissionsSeeder::class,
        ]);

        // 2. Superadmin principal - indestructible
        $superAdmin = User::updateOrCreate(
            ['email' => config('app.superadmin_email')],
            [
                'name' => config('app.admin_name', 'Super Admin'),
                'password' => bcrypt(config('app.admin_password', 'Admin123!')),
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

        // 4. Seeders des modules actifs (module => seeder)
        $moduleSeeders = [
            'Settings' => \Modules\Settings\Database\Seeders\SettingsDatabaseSeeder::class,
            'SEO' => \Modules\SEO\Database\Seeders\SEODatabaseSeeder::class,
            'SaaS' => \Modules\SaaS\Database\Seeders\SaaSDatabaseSeeder::class,
            'Privacy' => \Modules\Privacy\Database\Seeders\CookieCategorySeeder::class,
        ];

        foreach ($moduleSeeders as $moduleName => $seeder) {
            $module = Module::find($moduleName);
            if ($module && $module->isEnabled() && class_exists($seeder)) {
                $this->call($seeder);
            }
        }

        $this->call(OnboardingStepSeeder::class);

        // 5. Données de démo (hors production)
        if (! app()->isProduction()) {
            $users = User::factory()->count(5)->create();
            foreach ($users as $user) {
                $user->assignRole('user');
            }
        }
    }
}
