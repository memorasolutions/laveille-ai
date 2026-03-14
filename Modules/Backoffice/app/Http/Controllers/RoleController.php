<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Backoffice\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Modules\Backoffice\Http\Requests\StoreRoleRequest;
use Modules\Backoffice\Http\Requests\UpdateRoleRequest;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController
{
    use ChecksRoleElevation;
    private function permissionCategories(): array
    {
        return [
            'contenu' => [
                'label' => 'Contenu',
                'icon' => 'file-text',
                'permissions' => [
                    'view_articles' => ['label' => 'Voir les articles', 'desc' => 'Consulter la liste des articles du blog.'],
                    'create_articles' => ['label' => 'Créer des articles', 'desc' => 'Rédiger et publier de nouveaux articles.'],
                    'update_articles' => ['label' => 'Modifier les articles', 'desc' => 'Éditer les articles existants.'],
                    'delete_articles' => ['label' => 'Supprimer les articles', 'desc' => 'Supprimer définitivement des articles.'],

                    'view_comments' => ['label' => 'Voir les commentaires', 'desc' => 'Consulter la liste des commentaires.'],
                    'create_comments' => ['label' => 'Créer des commentaires', 'desc' => 'Ajouter de nouveaux commentaires.'],
                    'update_comments' => ['label' => 'Modifier les commentaires', 'desc' => 'Éditer les commentaires existants.'],
                    'delete_comments' => ['label' => 'Supprimer les commentaires', 'desc' => 'Supprimer définitivement des commentaires.'],

                    'view_categories' => ['label' => 'Voir les catégories', 'desc' => 'Consulter la liste des catégories.'],
                    'create_categories' => ['label' => 'Créer des catégories', 'desc' => 'Ajouter de nouvelles catégories.'],
                    'update_categories' => ['label' => 'Modifier les catégories', 'desc' => 'Éditer les catégories existantes.'],
                    'delete_categories' => ['label' => 'Supprimer les catégories', 'desc' => 'Supprimer définitivement des catégories.'],

                    'view_pages' => ['label' => 'Voir les pages', 'desc' => 'Consulter la liste des pages statiques.'],
                    'create_pages' => ['label' => 'Créer des pages', 'desc' => 'Ajouter de nouvelles pages statiques.'],
                    'update_pages' => ['label' => 'Modifier les pages', 'desc' => 'Éditer les pages statiques existantes.'],
                    'delete_pages' => ['label' => 'Supprimer les pages', 'desc' => 'Supprimer définitivement des pages statiques.'],

                    'view_media' => ['label' => 'Voir les médias', 'desc' => 'Consulter la bibliothèque de médias.'],
                    'create_media' => ['label' => 'Téléverser des médias', 'desc' => 'Ajouter de nouveaux fichiers médias.'],
                    'update_media' => ['label' => 'Modifier les médias', 'desc' => 'Éditer les informations des médias existants.'],
                    'delete_media' => ['label' => 'Supprimer les médias', 'desc' => 'Supprimer définitivement des fichiers médias.'],

                    'view_menus' => ['label' => 'Voir les menus', 'desc' => 'Consulter la liste des menus de navigation.'],
                    'create_menus' => ['label' => 'Créer des menus', 'desc' => 'Ajouter de nouveaux menus de navigation.'],
                    'update_menus' => ['label' => 'Modifier les menus', 'desc' => 'Éditer les menus de navigation existants.'],
                    'delete_menus' => ['label' => 'Supprimer les menus', 'desc' => 'Supprimer définitivement des menus.'],

                    'view_faqs' => ['label' => 'Voir les FAQ', 'desc' => 'Consulter la liste des questions fréquentes.'],
                    'create_faqs' => ['label' => 'Créer des FAQ', 'desc' => 'Ajouter de nouvelles questions fréquentes.'],
                    'update_faqs' => ['label' => 'Modifier les FAQ', 'desc' => 'Éditer les questions fréquentes existantes.'],
                    'delete_faqs' => ['label' => 'Supprimer les FAQ', 'desc' => 'Supprimer définitivement des entrées FAQ.'],

                    'view_testimonials' => ['label' => 'Voir les témoignages', 'desc' => 'Consulter la liste des témoignages clients.'],
                    'create_testimonials' => ['label' => 'Créer des témoignages', 'desc' => 'Ajouter de nouveaux témoignages.'],
                    'update_testimonials' => ['label' => 'Modifier les témoignages', 'desc' => 'Éditer les témoignages existants.'],
                    'delete_testimonials' => ['label' => 'Supprimer les témoignages', 'desc' => 'Supprimer définitivement des témoignages.'],

                    'view_forms' => ['label' => 'Voir les formulaires', 'desc' => 'Consulter la liste des formulaires.'],
                    'create_forms' => ['label' => 'Créer des formulaires', 'desc' => 'Ajouter de nouveaux formulaires.'],
                    'update_forms' => ['label' => 'Modifier les formulaires', 'desc' => 'Éditer les formulaires existants.'],
                    'delete_forms' => ['label' => 'Supprimer les formulaires', 'desc' => 'Supprimer définitivement des formulaires.'],

                    'view_widgets' => ['label' => 'Voir les widgets', 'desc' => 'Consulter la liste des widgets.'],
                    'create_widgets' => ['label' => 'Créer des widgets', 'desc' => 'Ajouter de nouveaux widgets.'],
                    'update_widgets' => ['label' => 'Modifier les widgets', 'desc' => 'Éditer les widgets existants.'],
                    'delete_widgets' => ['label' => 'Supprimer les widgets', 'desc' => 'Supprimer définitivement des widgets.'],
                ],
            ],
            'utilisateurs' => [
                'label' => 'Utilisateurs',
                'icon' => 'users',
                'permissions' => [
                    'view_users' => ['label' => 'Voir les utilisateurs', 'desc' => 'Consulter la liste des comptes utilisateurs.'],
                    'create_users' => ['label' => 'Créer des utilisateurs', 'desc' => 'Ajouter de nouveaux comptes utilisateurs.'],
                    'update_users' => ['label' => 'Modifier les utilisateurs', 'desc' => 'Éditer les comptes utilisateurs existants.'],
                    'delete_users' => ['label' => 'Supprimer les utilisateurs', 'desc' => 'Supprimer définitivement des comptes utilisateurs.'],

                    'view_roles' => ['label' => 'Voir les rôles', 'desc' => 'Consulter la liste des rôles et leurs permissions.'],
                    'create_roles' => ['label' => 'Créer des rôles', 'desc' => 'Ajouter de nouveaux rôles personnalisés. Accès sensible.'],
                    'update_roles' => ['label' => 'Modifier les rôles', 'desc' => 'Éditer les rôles et leurs permissions. Accès sensible.'],
                    'delete_roles' => ['label' => 'Supprimer les rôles', 'desc' => 'Supprimer définitivement des rôles. Accès sensible.'],

                    'view_teams' => ['label' => 'Voir les équipes', 'desc' => 'Consulter la liste des équipes et leurs membres.'],
                    'create_teams' => ['label' => 'Créer des équipes', 'desc' => 'Ajouter de nouvelles équipes.'],
                    'update_teams' => ['label' => 'Modifier les équipes', 'desc' => 'Éditer les équipes existantes.'],
                    'delete_teams' => ['label' => 'Supprimer les équipes', 'desc' => 'Supprimer définitivement des équipes.'],

                    'view_tenants' => ['label' => 'Voir les espaces', 'desc' => 'Consulter la liste des espaces (tenants) multi-tenant.'],
                    'create_tenants' => ['label' => 'Créer des espaces', 'desc' => 'Ajouter de nouveaux espaces multi-tenant.'],
                    'update_tenants' => ['label' => 'Modifier les espaces', 'desc' => 'Éditer les espaces existants.'],
                    'delete_tenants' => ['label' => 'Supprimer les espaces', 'desc' => 'Supprimer définitivement des espaces.'],

                    'view_contacts' => ['label' => 'Voir les contacts', 'desc' => 'Consulter les messages reçus via le formulaire de contact.'],
                    'manage_contacts' => ['label' => 'Gérer les contacts', 'desc' => 'Marquer comme lu, archiver ou supprimer les messages de contact.'],
                ],
            ],
            'marketing' => [
                'label' => 'Marketing',
                'icon' => 'megaphone',
                'permissions' => [
                    'view_newsletter' => ['label' => 'Voir la newsletter', 'desc' => 'Consulter les abonnés et les statistiques de la newsletter.'],
                    'create_newsletter' => ['label' => 'Créer des newsletters', 'desc' => 'Ajouter de nouvelles listes ou entrées newsletter.'],
                    'update_newsletter' => ['label' => 'Modifier la newsletter', 'desc' => 'Éditer les paramètres et abonnés de la newsletter.'],
                    'delete_newsletter' => ['label' => 'Supprimer des newsletters', 'desc' => 'Supprimer des abonnés ou listes de diffusion.'],

                    'view_campaigns' => ['label' => 'Voir les campagnes', 'desc' => 'Consulter la liste des campagnes email marketing.'],
                    'create_campaigns' => ['label' => 'Créer des campagnes', 'desc' => 'Créer et planifier de nouvelles campagnes email.'],
                    'update_campaigns' => ['label' => 'Modifier les campagnes', 'desc' => 'Éditer les campagnes existantes.'],
                    'delete_campaigns' => ['label' => 'Supprimer les campagnes', 'desc' => 'Supprimer définitivement des campagnes.'],

                    'view_workflows' => ['label' => 'Voir les workflows', 'desc' => 'Consulter la liste des workflows d\'automatisation.'],
                    'create_workflows' => ['label' => 'Créer des workflows', 'desc' => 'Ajouter de nouveaux workflows automatisés.'],
                    'update_workflows' => ['label' => 'Modifier les workflows', 'desc' => 'Éditer les workflows existants.'],
                    'delete_workflows' => ['label' => 'Supprimer les workflows', 'desc' => 'Supprimer définitivement des workflows.'],

                    'view_short_urls' => ['label' => 'Voir les liens courts', 'desc' => 'Consulter la liste des liens raccourcis.'],
                    'create_short_urls' => ['label' => 'Créer des liens courts', 'desc' => 'Générer de nouveaux liens raccourcis.'],
                    'update_short_urls' => ['label' => 'Modifier les liens courts', 'desc' => 'Éditer les liens courts existants.'],
                    'delete_short_urls' => ['label' => 'Supprimer les liens courts', 'desc' => 'Supprimer définitivement des liens courts.'],

                    'view_notifications' => ['label' => 'Voir les notifications', 'desc' => 'Consulter l\'historique des notifications envoyées.'],
                    'manage_notifications' => ['label' => 'Gérer les notifications', 'desc' => 'Envoyer et configurer les notifications système et push.'],
                ],
            ],
            'configuration' => [
                'label' => 'Configuration',
                'icon' => 'settings',
                'permissions' => [
                    'view_seo' => ['label' => 'Voir le SEO', 'desc' => 'Consulter les configurations de référencement.'],
                    'create_seo' => ['label' => 'Créer des règles SEO', 'desc' => 'Ajouter de nouvelles configurations SEO.'],
                    'update_seo' => ['label' => 'Modifier le SEO', 'desc' => 'Éditer les balises meta, titres et descriptions.'],
                    'delete_seo' => ['label' => 'Supprimer des règles SEO', 'desc' => 'Supprimer définitivement des configurations SEO.'],

                    'view_plans' => ['label' => 'Voir les forfaits', 'desc' => 'Consulter la liste des forfaits d\'abonnement SaaS.'],
                    'create_plans' => ['label' => 'Créer des forfaits', 'desc' => 'Ajouter de nouveaux plans tarifaires.'],
                    'update_plans' => ['label' => 'Modifier les forfaits', 'desc' => 'Éditer les prix, périodes et fonctionnalités des plans.'],
                    'delete_plans' => ['label' => 'Supprimer les forfaits', 'desc' => 'Supprimer définitivement des forfaits.'],

                    'view_settings' => ['label' => 'Voir les paramètres', 'desc' => 'Consulter les paramètres globaux de l\'application.'],
                    'manage_settings' => ['label' => 'Gérer les paramètres', 'desc' => 'Modifier la configuration générale, email et apparence du site.'],

                    'view_branding' => ['label' => 'Voir l\'image de marque', 'desc' => 'Consulter les éléments de marque (logos, couleurs, polices).'],
                    'manage_branding' => ['label' => 'Gérer l\'image de marque', 'desc' => 'Modifier le logo, les couleurs et l\'identité visuelle.'],

                    'view_themes' => ['label' => 'Voir les thèmes', 'desc' => 'Consulter les thèmes disponibles du backoffice.'],
                    'manage_themes' => ['label' => 'Gérer les thèmes', 'desc' => 'Changer le thème actif et configurer l\'apparence.'],

                    'view_translations' => ['label' => 'Voir les traductions', 'desc' => 'Consulter les textes traduits dans toutes les langues.'],
                    'manage_translations' => ['label' => 'Gérer les traductions', 'desc' => 'Modifier les fichiers de langue et les chaînes de traduction.'],

                    'view_email_templates' => ['label' => 'Voir les modèles d\'email', 'desc' => 'Consulter les gabarits d\'emails transactionnels.'],
                    'manage_email_templates' => ['label' => 'Gérer les modèles d\'email', 'desc' => 'Personnaliser le contenu et le design des emails.'],

                    'view_shortcodes' => ['label' => 'Voir les shortcodes', 'desc' => 'Consulter la liste des shortcodes disponibles.'],
                    'manage_shortcodes' => ['label' => 'Gérer les shortcodes', 'desc' => 'Créer et modifier les shortcodes personnalisés.'],

                    'view_cookies' => ['label' => 'Voir les cookies', 'desc' => 'Consulter la politique de cookies et les bannières.'],
                    'manage_cookies' => ['label' => 'Gérer les cookies', 'desc' => 'Configurer le consentement et les catégories de cookies (RGPD).'],

                    'view_onboarding' => ['label' => 'Voir l\'onboarding', 'desc' => 'Consulter les parcours d\'accueil des nouveaux utilisateurs.'],
                    'manage_onboarding' => ['label' => 'Gérer l\'onboarding', 'desc' => 'Configurer les étapes d\'intégration des nouveaux utilisateurs.'],

                    'view_feature_flags' => ['label' => 'Voir les feature flags', 'desc' => 'Consulter l\'état des fonctionnalités activées ou désactivées.'],
                    'manage_feature_flags' => ['label' => 'Gérer les feature flags', 'desc' => 'Activer ou désactiver des fonctionnalités et leurs conditions.'],
                ],
            ],
            'systeme' => [
                'label' => 'Système',
                'icon' => 'server',
                'permissions' => [
                    'view_system' => ['label' => 'Voir le système', 'desc' => 'Consulter les informations et l\'état du système.'],
                    'manage_system' => ['label' => 'Gérer le système', 'desc' => 'Maintenance, cache, scheduler, jobs échoués et infos système.'],

                    'view_security' => ['label' => 'Voir la sécurité', 'desc' => 'Consulter le tableau de bord sécurité et l\'historique des connexions.'],
                    'manage_security' => ['label' => 'Gérer la sécurité', 'desc' => 'Configurer les IPs bloquées et les règles de sécurité.'],

                    'view_backups' => ['label' => 'Voir les sauvegardes', 'desc' => 'Consulter l\'historique des sauvegardes.'],
                    'manage_backups' => ['label' => 'Gérer les sauvegardes', 'desc' => 'Lancer, télécharger et supprimer des sauvegardes.'],

                    'view_activity_logs' => ['label' => 'Voir les journaux d\'activité', 'desc' => 'Consulter l\'historique des actions des utilisateurs.'],
                    'manage_activity_logs' => ['label' => 'Gérer les journaux d\'activité', 'desc' => 'Exporter ou purger les journaux d\'activité.'],

                    'view_trash' => ['label' => 'Voir la corbeille', 'desc' => 'Consulter les éléments supprimés récemment.'],
                    'manage_trash' => ['label' => 'Gérer la corbeille', 'desc' => 'Restaurer ou vider définitivement la corbeille.'],

                    'view_ai' => ['label' => 'Voir l\'IA', 'desc' => 'Consulter l\'utilisation et les requêtes d\'intelligence artificielle.'],
                    'manage_ai' => ['label' => 'Gérer l\'IA', 'desc' => 'Configurer les clés et les modèles d\'intelligence artificielle.'],

                    'view_api' => ['label' => 'Voir l\'API', 'desc' => 'Consulter les accès et l\'utilisation de l\'API.'],
                    'manage_api' => ['label' => 'Gérer l\'API', 'desc' => 'Créer, révoquer des tokens API et configurer les rate limits.'],

                    'view_webhooks' => ['label' => 'Voir les webhooks', 'desc' => 'Consulter l\'historique et les endpoints des webhooks.'],
                    'manage_webhooks' => ['label' => 'Gérer les webhooks', 'desc' => 'Configurer et tester les webhooks pour les intégrations externes.'],
                ],
            ],
            'acces' => [
                'label' => 'Accès',
                'icon' => 'eye',
                'permissions' => [
                    'view_admin_panel' => ['label' => 'Accéder au backoffice', 'desc' => 'Permet l\'accès à l\'interface d\'administration.'],
                    'view_dashboard' => ['label' => 'Voir le tableau de bord', 'desc' => 'Afficher les statistiques et l\'activité récente sur le dashboard.'],
                    'view_health' => ['label' => 'Voir la santé système', 'desc' => 'Consulter l\'état des vérifications système (base de données, cache, etc.).'],
                    'view_horizon' => ['label' => 'Accéder à Horizon', 'desc' => 'Surveiller les files d\'attente et les jobs en temps réel.'],
                    'view_logs' => ['label' => 'Voir les logs applicatifs', 'desc' => 'Consulter les fichiers de logs Laravel pour le débogage.'],
                    'view_telescope' => ['label' => 'Accéder à Telescope', 'desc' => 'Inspecter les requêtes, exceptions et performances de l\'application.'],
                ],
            ],
            'import_export' => [
                'label' => 'Import / Export',
                'icon' => 'upload',
                'permissions' => [
                    'view_exports' => ['label' => 'Voir les exports', 'desc' => 'Consulter l\'historique des fichiers exportés.'],
                    'manage_exports' => ['label' => 'Gérer les exports', 'desc' => 'Générer et télécharger des exports de données (CSV, etc.).'],

                    'view_imports' => ['label' => 'Voir les imports', 'desc' => 'Consulter l\'historique des fichiers importés.'],
                    'manage_imports' => ['label' => 'Gérer les imports', 'desc' => 'Lancer de nouvelles procédures d\'importation de données.'],
                ],
            ],
        ];
    }

    private function buildPermissionData(): array
    {
        $allPermissions = Permission::orderBy('name')->get()->keyBy('name');
        $categories = $this->permissionCategories();

        foreach ($categories as $key => &$category) {
            foreach ($category['permissions'] as $permName => &$permMeta) {
                $permMeta['model'] = $allPermissions->get($permName);
            }
            unset($permMeta);
        }
        unset($category);

        return $categories;
    }

    private function buildPermMap(array $categories): array
    {
        $map = [];
        foreach ($categories as $catKey => $category) {
            foreach ($category['permissions'] as $permName => $permMeta) {
                if ($permMeta['model'] !== null) {
                    $map[$permName] = [
                        'id' => $permMeta['model']->id,
                        'category' => $catKey,
                    ];
                }
            }
        }

        return $map;
    }

    public function index(): View
    {
        return view('backoffice::roles.index');
    }

    public function create(): View
    {
        $categories = $this->buildPermissionData();

        return view('backoffice::roles.create', [
            'categories' => $categories,
            'permMap' => $this->buildPermMap($categories),
        ]);
    }

    public function store(StoreRoleRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        /** @var \App\Models\User $user */
        $user = $request->user();
        $this->ensureNoElevation($user, $validated['permissions'] ?? []);
        $this->ensureNoLevelElevation($user, (int) ($validated['level'] ?? 10));

        DB::transaction(function () use ($validated) {
            $role = Role::create([
                'name' => $validated['name'],
                'guard_name' => 'web',
                'level' => $validated['level'] ?? 10,
                'requires_password' => $validated['requires_password'] ?? true,
            ]);

            if (! empty($validated['permissions'])) {
                $permissionNames = Permission::whereIn('id', $validated['permissions'])->pluck('name');
                $role->syncPermissions($permissionNames);
            }
        });

        return redirect()->route('admin.roles.index')->with('success', 'Rôle créé.');
    }

    public function show(Role $role): View
    {
        $role->load('permissions');

        return view('backoffice::roles.show', compact('role'));
    }

    public function edit(Role $role): View
    {
        $role->load('permissions');
        $categories = $this->buildPermissionData();

        return view('backoffice::roles.edit', [
            'role' => $role,
            'categories' => $categories,
            'permMap' => $this->buildPermMap($categories),
        ]);
    }

    public function update(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        $validated = $request->validated();

        /** @var \App\Models\User $user */
        $user = $request->user();
        $this->ensureNoElevation($user, $validated['permissions'] ?? []);
        $this->ensureNoLevelElevation($user, (int) ($validated['level'] ?? $role->level));

        DB::transaction(function () use ($validated, $role) {
            $role->update([
                'name' => $validated['name'],
                'level' => $validated['level'] ?? $role->level,
                'requires_password' => $validated['requires_password'] ?? true,
            ]);

            $permissionNames = Permission::whereIn('id', $validated['permissions'] ?? [])->pluck('name');
            $role->syncPermissions($permissionNames);
        });

        return redirect()->route('admin.roles.index')->with('success', 'Rôle modifié.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        if (in_array($role->name, ['super_admin', 'admin'])) {
            return back()->with('error', 'Ce rôle ne peut pas être supprimé.');
        }

        $role->delete();

        return redirect()->route('admin.roles.index')->with('success', 'Rôle supprimé.');
    }
}
