<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Sso\Providers;

use Modules\Core\Providers\BaseModuleServiceProvider;

/**
 * SSO entreprise (SAML 2.0) + provisioning SCIM 2.0 pour l'Académie (LMS).
 *
 * Interrupteur MAÎTRE : config('sso.enabled') — défaut FALSE. Tant qu'il
 * n'est pas activé, TOUTES les routes /sso/saml/* et /scim/v2/* répondent
 * 404 (abort_if en tête de chaque contrôleur), aucune migration ne modifie
 * le comportement d'autres modules (tables préfixées sso_*, aucune colonne
 * ajoutée à `users`). Module désactivé par défaut dans modules_statuses.json.
 */
class SsoServiceProvider extends BaseModuleServiceProvider
{
    protected string $name = 'Sso';

    protected string $nameLower = 'sso';

    public function boot(): void
    {
        $this->bootModule();
    }

    public function register(): void
    {
        $this->app->register(EventServiceProvider::class);
        $this->app->register(RouteServiceProvider::class);
    }
}
