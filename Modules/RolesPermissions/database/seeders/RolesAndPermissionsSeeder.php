<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\RolesPermissions\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Pattern A - CRUD complet (20 entités × 4 = 80 permissions)
        // contacts retiré → Pattern B (view+manage uniquement, messages entrants non éditables)
        $patternAEntities = [
            'users', 'roles', 'articles', 'comments', 'categories', 'pages', 'media', 'menus',
            'faqs', 'testimonials', 'forms', 'widgets', 'plans', 'seo', 'newsletter',
            'campaigns', 'short_urls', 'teams', 'tenants', 'workflows',
        ];

        foreach ($patternAEntities as $entity) {
            Permission::firstOrCreate(['name' => 'view_'.$entity]);
            Permission::firstOrCreate(['name' => 'create_'.$entity]);
            Permission::firstOrCreate(['name' => 'update_'.$entity]);
            Permission::firstOrCreate(['name' => 'delete_'.$entity]);
        }

        // Pattern B - Opérationnel (21 entités × 2 = 42 permissions)
        // contacts ajouté ici : messages entrants, lecture + gestion sans CRUD complet
        $patternBEntities = [
            'backups', 'exports', 'imports', 'system', 'security', 'email_templates', 'shortcodes',
            'cookies', 'onboarding', 'trash', 'notifications', 'webhooks', 'ai', 'api',
            'activity_logs', 'feature_flags', 'branding', 'themes', 'translations', 'settings',
            'contacts', 'storage', 'usage', 'referrals', 'incidents', 'documentation',
            'booking', 'roadmap',
        ];

        foreach ($patternBEntities as $entity) {
            Permission::firstOrCreate(['name' => 'view_'.$entity]);
            Permission::firstOrCreate(['name' => 'manage_'.$entity]);
        }

        // Permissions système (6)
        Permission::firstOrCreate(['name' => 'view_admin_panel']);
        Permission::firstOrCreate(['name' => 'view_dashboard']);
        Permission::firstOrCreate(['name' => 'view_health']);
        Permission::firstOrCreate(['name' => 'view_horizon']);
        Permission::firstOrCreate(['name' => 'view_logs']);
        Permission::firstOrCreate(['name' => 'view_telescope']);

        // Super admin - toutes les permissions
        $superAdmin = Role::firstOrCreate(['name' => 'super_admin']);
        $superAdmin->update(['level' => 100]);
        $superAdmin->syncPermissions(Permission::all());

        // Admin - tout sauf create_roles, update_roles, delete_roles (peut voir les rôles)
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $admin->update(['level' => 80]);
        $admin->syncPermissions(
            Permission::whereNotIn('name', ['create_roles', 'update_roles', 'delete_roles'])->get()
        );

        // Éditeur - contenu uniquement
        $editor = Role::firstOrCreate(['name' => 'editor']);
        $editor->update(['level' => 40]);
        $editor->syncPermissions([
            'view_articles', 'create_articles', 'update_articles',
            'view_comments', 'create_comments', 'update_comments', 'delete_comments',
            'view_categories', 'create_categories', 'update_categories',
            'view_pages', 'create_pages', 'update_pages',
            'view_media', 'create_media', 'update_media', 'delete_media',
            'view_menus', 'create_menus', 'update_menus',
            'view_faqs', 'create_faqs', 'update_faqs',
            'view_testimonials', 'create_testimonials', 'update_testimonials',
            'view_forms', 'create_forms', 'update_forms',
            'view_widgets',
            'view_contacts',
            'view_admin_panel',
            'view_dashboard',
        ]);

        // Utilisateur - accès minimal
        $user = Role::firstOrCreate(['name' => 'user']);
        $user->update(['level' => 10]);
        $user->syncPermissions(['view_dashboard']);
    }
}
