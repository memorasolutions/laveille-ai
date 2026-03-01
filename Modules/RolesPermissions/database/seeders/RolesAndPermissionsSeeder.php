<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
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

        $permissionNames = [
            // Gestion des entités
            'manage_users',
            'manage_roles',
            'manage_articles',
            'manage_comments',
            'manage_categories',
            'manage_pages',
            'manage_media',
            'manage_menus',
            'manage_faqs',
            'manage_testimonials',
            'manage_contacts',
            'manage_forms',
            'manage_widgets',
            'manage_settings',
            'manage_plans',
            'manage_seo',
            'manage_newsletter',
            'manage_campaigns',
            'manage_webhooks',
            'manage_notifications',
            'manage_translations',
            'manage_themes',
            'manage_branding',
            'manage_feature_flags',
            'manage_activity_logs',
            'manage_backups',
            'manage_exports',
            'manage_imports',
            'manage_api',
            // Gestion système et sécurité
            'manage_system',
            'manage_security',
            'manage_email_templates',
            'manage_shortcodes',
            'manage_cookies',
            'manage_onboarding',
            'manage_trash',
            // Accès en lecture
            'view_admin_panel',
            'view_dashboard',
            'view_health',
            'view_horizon',
            'view_logs',
            'view_telescope',
        ];

        foreach ($permissionNames as $name) {
            Permission::firstOrCreate(['name' => $name]);
        }

        // Super admin - toutes les permissions
        $superAdmin = Role::firstOrCreate(['name' => 'super_admin']);
        $superAdmin->givePermissionTo(Permission::all());

        // Admin - tout sauf gestion des rôles
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $admin->givePermissionTo(Permission::where('name', '!=', 'manage_roles')->get());

        // Éditeur - contenu uniquement
        $editor = Role::firstOrCreate(['name' => 'editor']);
        $editor->givePermissionTo([
            'manage_articles',
            'manage_comments',
            'manage_categories',
            'manage_media',
            'manage_pages',
            'view_admin_panel',
            'view_dashboard',
        ]);

        // Utilisateur - accès minimal
        $user = Role::firstOrCreate(['name' => 'user']);
        $user->givePermissionTo('view_dashboard');
    }
}
