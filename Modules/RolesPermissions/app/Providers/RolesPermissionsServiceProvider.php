<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\RolesPermissions\Providers;

use Illuminate\Support\Facades\Gate;
use Modules\Core\Providers\BaseModuleServiceProvider;

class RolesPermissionsServiceProvider extends BaseModuleServiceProvider
{
    protected string $name = 'RolesPermissions';

    protected string $nameLower = 'rolespermissions';

    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        $this->registerCommands();
        $this->bootModule();

        // super_admin bypasses all permission/policy checks.
        // Le super-admin configuré (app.superadmin_email) passe TOUJOURS, même si la
        // résolution du rôle spatie est transitoirement indisponible au 1er hit post-login
        // (corrige « menus admin manquants au login, présents au refresh »). Portée stricte :
        // ne peut accorder l'accès qu'au courriel super-admin configuré, jamais à un tiers.
        Gate::before(function ($user, string $ability, array $arguments = []) {
            // Exception : le journal personnel reste un espace strictement privé, y compris
            // pour le superadmin. Éditer silencieusement le contenu d'autrui (par opposition à
            // le supprimer/modérer/suspendre le compte) est juridiquement problématique
            // (Loi 25/PIPEDA/RGPD : intégrité du contenu personnel, dépasse le consentement
            // donné) - trouvé par la simulation E2E du 2026-07-11, confirmé par veille légale.
            // La suppression/modération reste bypassée normalement (pouvoir de modération admin
            // intact), seule la mutation update() du contenu est exclue.
            if ($ability === 'update' && ($arguments[0] ?? null) instanceof \Modules\Journal\Models\Journal) {
                return null;
            }

            $superEmail = config('app.superadmin_email');

            if ($superEmail && $user->email === $superEmail) {
                return true;
            }

            return $user->hasRole('super_admin') ? true : null;
        });
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);
    }

    /**
     * Register commands in the format of Command::class
     */
    protected function registerCommands(): void
    {
        $this->commands([
            \Modules\RolesPermissions\Console\SyncPermissionsCommand::class,
        ]);
    }
}
