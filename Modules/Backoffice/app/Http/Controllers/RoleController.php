<?php

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
    private function permissionCategories(): array
    {
        return [
            'contenu' => [
                'label' => 'Contenu',
                'icon' => 'file-text',
                'permissions' => [
                    'manage_articles' => ['label' => 'Gérer les articles', 'desc' => 'Créer, modifier, publier et supprimer les articles du blog.'],
                    'manage_comments' => ['label' => 'Gérer les commentaires', 'desc' => 'Modérer, approuver et supprimer les commentaires.'],
                    'manage_categories' => ['label' => 'Gérer les catégories', 'desc' => 'Créer, modifier et supprimer les catégories d\'articles.'],
                    'manage_pages' => ['label' => 'Gérer les pages', 'desc' => 'Créer, modifier et supprimer les pages statiques du site.'],
                    'manage_media' => ['label' => 'Gérer les médias', 'desc' => 'Téléverser, organiser et supprimer les fichiers médias (images, documents).'],
                    'manage_seo' => ['label' => 'Gérer le SEO', 'desc' => 'Configurer les balises meta, titres et descriptions pour le référencement.'],
                ],
            ],
            'utilisateurs' => [
                'label' => 'Utilisateurs',
                'icon' => 'users',
                'permissions' => [
                    'manage_users' => ['label' => 'Gérer les utilisateurs', 'desc' => 'Créer, modifier, activer/désactiver et supprimer les comptes utilisateurs.'],
                    'manage_roles' => ['label' => 'Gérer les rôles', 'desc' => 'Créer et modifier les rôles, attribuer les permissions. Accès sensible.'],
                    'manage_newsletter' => ['label' => 'Gérer la newsletter', 'desc' => 'Gérer les abonnés, consulter les statistiques d\'abonnement.'],
                    'manage_campaigns' => ['label' => 'Gérer les campagnes', 'desc' => 'Créer, planifier et envoyer des campagnes email marketing.'],
                    'manage_notifications' => ['label' => 'Gérer les notifications', 'desc' => 'Envoyer et configurer les notifications système et utilisateur.'],
                ],
            ],
            'configuration' => [
                'label' => 'Configuration',
                'icon' => 'settings',
                'permissions' => [
                    'manage_settings' => ['label' => 'Gérer les paramètres', 'desc' => 'Modifier les paramètres généraux, email, SEO, apparence du site.'],
                    'manage_branding' => ['label' => 'Personnaliser la marque', 'desc' => 'Modifier le logo, les couleurs, le nom du site et l\'identité visuelle.'],
                    'manage_themes' => ['label' => 'Gérer les thèmes', 'desc' => 'Changer le thème du backoffice et configurer l\'apparence.'],
                    'manage_translations' => ['label' => 'Gérer les traductions', 'desc' => 'Modifier les traductions du site dans toutes les langues.'],
                    'manage_feature_flags' => ['label' => 'Gérer les feature flags', 'desc' => 'Activer/désactiver des fonctionnalités et configurer leurs conditions.'],
                    'manage_plans' => ['label' => 'Gérer les plans SaaS', 'desc' => 'Créer et modifier les plans tarifaires, prix et périodes d\'essai.'],
                    'manage_webhooks' => ['label' => 'Gérer les webhooks', 'desc' => 'Configurer les endpoints webhook pour les intégrations externes.'],
                ],
            ],
            'outils' => [
                'label' => 'Outils',
                'icon' => 'wrench',
                'permissions' => [
                    'manage_backups' => ['label' => 'Gérer les sauvegardes', 'desc' => 'Lancer, télécharger et supprimer les sauvegardes de la base de données.'],
                    'manage_activity_logs' => ['label' => 'Gérer les journaux', 'desc' => 'Consulter, exporter et purger les journaux d\'activité.'],
                    'manage_exports' => ['label' => 'Exporter les données', 'desc' => 'Exporter les données au format CSV (utilisateurs, articles, plans, etc.).'],
                    'manage_imports' => ['label' => 'Importer les données', 'desc' => 'Importer des données depuis des fichiers CSV.'],
                    'manage_api' => ['label' => 'Gérer l\'API', 'desc' => 'Gérer les tokens API, configurer les accès et les rate limits.'],
                    'manage_trash' => ['label' => 'Gérer la corbeille', 'desc' => 'Restaurer ou supprimer définitivement les éléments mis à la corbeille.'],
                    'manage_shortcodes' => ['label' => 'Gérer les shortcodes', 'desc' => 'Créer, modifier et supprimer les shortcodes disponibles.'],
                    'manage_email_templates' => ['label' => 'Gérer les emails', 'desc' => 'Personnaliser les templates d\'emails transactionnels.'],
                ],
            ],
            'systeme' => [
                'label' => 'Système',
                'icon' => 'server',
                'permissions' => [
                    'manage_system' => ['label' => 'Administration système', 'desc' => 'Maintenance, cache, scheduler, jobs échoués, rétention des données, infos système.'],
                    'manage_security' => ['label' => 'Sécurité', 'desc' => 'Tableau de bord sécurité, IPs bloquées, historique des connexions.'],
                    'manage_cookies' => ['label' => 'Gérer les cookies', 'desc' => 'Configurer les catégories de cookies pour la conformité RGPD.'],
                    'manage_onboarding' => ['label' => 'Gérer l\'onboarding', 'desc' => 'Configurer les étapes d\'accueil des nouveaux utilisateurs.'],
                ],
            ],
            'acces' => [
                'label' => 'Accès',
                'icon' => 'eye',
                'permissions' => [
                    'view_admin_panel' => ['label' => 'Accéder au backoffice', 'desc' => 'Permet l\'accès à l\'interface d\'administration.'],
                    'view_dashboard' => ['label' => 'Voir le tableau de bord', 'desc' => 'Afficher les statistiques et l\'activité récente sur le dashboard.'],
                    'view_health' => ['label' => 'Voir la santé système', 'desc' => 'Consulter l\'état des vérifications système (base de données, cache, etc.).'],
                    'view_logs' => ['label' => 'Voir les logs applicatifs', 'desc' => 'Consulter les fichiers de logs Laravel pour le debugging.'],
                    'view_horizon' => ['label' => 'Accéder à Horizon', 'desc' => 'Surveiller les files d\'attente et les jobs en temps réel.'],
                    'view_telescope' => ['label' => 'Accéder à Telescope', 'desc' => 'Inspecter les requêtes, exceptions et performances de l\'application.'],
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
                        'id'       => $permMeta['model']->id,
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
            'permMap'    => $this->buildPermMap($categories),
        ]);
    }

    public function store(StoreRoleRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($validated) {
            $role = Role::create([
                'name' => $validated['name'],
                'guard_name' => 'web',
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
            'role'       => $role,
            'categories' => $categories,
            'permMap'    => $this->buildPermMap($categories),
        ]);
    }

    public function update(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($validated, $role) {
            $role->update([
                'name' => $validated['name'],
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
