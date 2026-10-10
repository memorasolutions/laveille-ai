<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 */

declare(strict_types=1);

namespace Modules\Idp\Providers;

use Illuminate\Support\Facades\Route;
use Laravel\Passport\Passport;
use Modules\Core\Providers\BaseModuleServiceProvider;

/**
 * laveille.ai comme IdP OAuth2 pour Moodle. Éteint par défaut : si IDP_ENABLED est faux,
 * aucune route n'est enregistrée (ni Passport, ni /api/oauth/userinfo).
 */
class IdpServiceProvider extends BaseModuleServiceProvider
{
    protected string $name = 'Idp';

    protected string $nameLower = 'idp';

    public function register(): void
    {
        // (chemin relatif : module_path() exige le cache, pas encore enregistré à ce stade)
        // Config du module lisible dès register() : config('idp.enabled') décide du chargement de Passport.
        $this->mergeConfigFrom(__DIR__.'/../../config/config.php', $this->nameLower);

        // Passport n'est plus auto-découvert (composer.json, dont-discover) : son provider ne se charge
        // QUE si l'IdP est activé. Éteint = aucun provider, aucun driver, aucun écouteur Passport.
        if (! config('idp.enabled')) {
            return;
        }

        $this->app->register(\Laravel\Passport\PassportServiceProvider::class);
        // Avant le boot() de Passport : il lit ce drapeau pour ne pas enregistrer ses propres routes.
        Passport::ignoreRoutes();

        // Client maison : saute l'écran de consentement pour un client FIRST-PARTY (le Moodle de
        // formations.laveille.ai), car AUCUNE vue d'autorisation n'est définie dans ce projet - sans
        // ce modèle, le flux /oauth/authorize casserait au consentement. Voir Modules\Idp\Models\OAuthClient.
        Passport::useClientModel(\Modules\Idp\Models\OAuthClient::class);
    }

    public function boot(): void
    {
        $this->bootModule();
        $this->registerIdpScopes();
        $this->registerIdpMigrations();
        $this->registerIdpRoutes();
    }

    /** Scopes OIDC. Définis UNIQUEMENT quand l'IdP est activé (éteint = aucune trace Passport). */
    public function registerIdpScopes(): void
    {
        if (! config('idp.enabled')) {
            return;
        }

        Passport::tokensCan([
            'openid' => 'Vérifier votre identité',
            'email' => 'Lire votre adresse courriel',
            'profile' => 'Lire votre nom',
        ]);
    }

    /**
     * Passport 13 ne charge PAS ses migrations (il les publie seulement). On les charge ici,
     * à la condition que l'IdP soit activé : éteint, `migrate` ne crée aucune table oauth_*.
     */
    public function registerIdpMigrations(): void
    {
        if (! config('idp.enabled')) {
            return;
        }

        $this->loadMigrationsFrom(base_path('vendor/laravel/passport/database/migrations'));
    }

    /** N'enregistre AUCUNE route tant que IDP_ENABLED est faux (réversibilité). */
    public function registerIdpRoutes(): void
    {
        if (! config('idp.enabled')) {
            return;
        }

        // Endpoints OAuth2 de Passport (authorize, token), sous /oauth, uniquement si activé.
        Route::group([
            'as' => 'passport.',
            'prefix' => config('passport.path', 'oauth'),
            'namespace' => 'Laravel\Passport\Http\Controllers',
            'middleware' => config('passport.middleware', []),
        ], function (): void {
            $this->loadRoutesFrom(base_path('vendor/laravel/passport/routes/web.php'));
        });

        Route::middleware('api')->prefix('api')->group(module_path($this->name, 'routes/api.php'));
    }
}
