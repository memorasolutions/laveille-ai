<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Pennant\Feature;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Helpers globaux Memora — chargés ici pour ne pas dépendre de
        // composer dump-autoload en prod (PHP-FPM peut ne pas avoir composer accessible).
        // require_once est idempotent + opcache → coût nul après 1er hit.
        foreach ([
            base_path('app/Helpers/version.php'),
            base_path('app/Helpers/dictionary.php'),
            base_path('app/Helpers/typo.php'),
            base_path('app/Helpers/domain.php'),
            base_path('app/Helpers/jsonld.php'),
        ] as $helper) {
            if (is_file($helper)) {
                require_once $helper;
            }
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (app()->isProduction()) {
            URL::forceScheme('https');
        }

        // Force Carbon + setLocale FR (dates en français globalement)
        \Carbon\Carbon::setLocale('fr');
        setlocale(LC_TIME, 'fr_CA.UTF-8', 'fr_FR.UTF-8', 'fr_CA', 'fr_FR', 'fr');

        // Typographie française — directive Blade @typo + macro Str::typoFr
        // (helper lv_typo_fr déjà chargé via files[] composer.json + register())
        Blade::directive('typo', static fn (string $expression): string => "<?php echo lv_typo_fr({$expression}); ?>");
        Str::macro('typoFr', fn (?string $t): string => lv_typo_fr($t));

        Model::automaticallyEagerLoadRelationships();
        Model::preventLazyLoading(! app()->isProduction());

        Paginator::useBootstrapFive();

        // Modules avancés (désactivés par défaut)
        Feature::define('module-saas', false);
        Feature::define('module-tenancy', false);
        Feature::define('module-ai', false);
        Feature::define('module-team', false);
        Feature::define('module-abtest', false);
        Feature::define('module-import', false);
        Feature::define('module-sms', false);

        // Modules business (activés par défaut)
        Feature::define('module-blog', true);
        Feature::define('module-newsletter', true);
        Feature::define('module-faq', true);
        Feature::define('module-testimonials', true);
        Feature::define('module-widget', true);
        Feature::define('module-formbuilder', true);
        Feature::define('module-customfields', true);

        // Fonctionnalités optionnelles (désactivées par défaut)
        Feature::define('social-login', false);
        Feature::define('realtime-notifications', false);
        Feature::define('locale-es', false);
        Feature::define('usage-billing', false);
        Feature::define('referral-program', false);
        Feature::define('email-preview', false);
        Feature::define('status-page', false);
        Feature::define('storage-admin', false);
        Feature::define('dark-mode-frontend', false);
        Feature::define('user-documentation', false);

        // Modules infrastructure (activés par défaut)
        Feature::define('module-translation', true);
        Feature::define('module-search', true);
        Feature::define('module-export', true);
        Feature::define('module-webhooks', true);
        Feature::define('module-media', true);
        Feature::define('module-backup', true);
        Feature::define('module-cloudflare-cache', true);

        // Kill switches automatisations critiques (activés par défaut — désactivables via Feature::deactivate() pour pause instantanée sans redeploy)
        Feature::define('cron.newsletter-send', true);
        Feature::define('cron.newsletter-preview', true);
        Feature::define('cron.ai-enrich', true);
        Feature::define('cron.gelato-sync', true);

        // Kill switches supplémentaires (session 16e — étendus aux modules News/Directory)
        Feature::define('cron.news-fetch', true);
        Feature::define('cron.directory-discovery', true);
        Feature::define('cron.directory-tutorials', true);
        Feature::define('cron.directory-pricing', true);
        Feature::define('cron.directory-formations', true);

        // Kill switch Shop cart abandonment recovery (session 16g)
        Feature::define('cron.cart-abandonment', true);

        // ACTION : définir les 6 drapeaux que des commandes vérifiaient sans qu'ils existent.
        // MCP: SELF (6 lignes déclaratives)
        // RAISON: Pennant renvoie « inactif » pour un drapeau JAMAIS DÉFINI. Six commandes
        // appelaient shouldSkipForKillSwitch() sur un nom absent de cette liste et étaient donc
        // bloquées en permanence, y compris TROIS QUI SONT PLANIFIÉES et échouaient donc en
        // silence chaque jour : `tools:enrich-rich-fields --batch=20` et les deux
        // `tools:dispatch-enrichment`. Mesuré le 2026-08-23 : 258 des 524 fiches publiées
        // (49 %) n'ont ni core_features, ni use_cases, ni pros/cons, ni faq, ni how_to_use -
        // exactement les champs que enrich-rich-fields remplit.
        // Ce n'était PAS une coupure délibérée : on ne planifie pas une commande pour la
        // désactiver ensuite en supprimant la définition de son drapeau, et
        // `cron.directory-tutorials-sonar` ne diffère que d'un suffixe de
        // `cron.directory-tutorials`, lui bien défini ligne 121. C'est une dérive de nommage.
        Feature::define('cron.ai-enrich-rich-fields', true);   // planifiée, remplit les champs du standard
        Feature::define('cron.ai-enrich-dispatch', true);      // planifiée (2 fois : pending + metadata)
        Feature::define('cron.ai-enrich-metadata', true);      // manuelle : launch_year + target_audience
        Feature::define('cron.fix-hn', true);                  // manuelle, crée bien une redirection 301 en renommant
        Feature::define('cron.import-youtube', true);          // manuelle, idempotente (dédoublonne par video_id)

        // DÉLIBÉRÉMENT à false, et non plus « inactif par accident » : cette voie ajoute des
        // tutoriels automatiquement, or l'attribution d'un tutoriel au BON outil est justement
        // le point fragile (des noms comme « Avec », « Donely » ou « Creativly » ont des
        // homonymes ; un tutoriel sur un homonyme induit le lecteur en erreur plus sûrement que
        // pas de tutoriel du tout). À rouvrir sur décision explicite, pas par dérive.
        Feature::define('cron.directory-tutorials-sonar', false);

        $this->configureRateLimiting();
        $this->configureQueueFailureHandling();
    }

    protected function configureQueueFailureHandling(): void
    {
        Queue::failing(function (JobFailed $event) {
            Log::error('Queue job failed', [
                'job' => $event->job->resolveName(),
                'connection' => $event->connectionName,
                'exception' => $event->exception->getMessage(),
                'trace' => $event->exception->getTraceAsString(),
            ]);

            // ACTION: rendre AUDITABLES les deux sorties silencieuses de ce bloc (2026-08-29).
            // RAISON: la nuit du 25 au 26 août, trois jobs ont échoué entre 21h38 et 06h10 Québec
            // (01:38 à 10:10 UTC) sans qu'aucun courriel ne parte, et RIEN n'a permis de savoir
            // pourquoi. Le Log::error ci-dessus avait bien écrit ; c'est donc APRÈS lui que
            // l'information disparaissait. Deux portes muettes, pas une :
            //   - le catch, qui avalait toute exception levée par le service ;
            //   - le class_exists() en faux, qui saute l'appel sans un mot : si le module
            //     Notifications est désactivé dans modules_statuses.json, PLUS AUCUNE alerte ne
            //     part et personne ne s'en aperçoit.
            // Canal dédié obligatoire : LOG_LEVEL=error en production rendrait un info invisible
            // sur le canal par défaut - c'est précisément le piège corrigé dans le service.
            // L'intention d'origine est conservée intacte : on journalise, on ne relance JAMAIS
            // depuis un gestionnaire d'échec, sous peine d'aggraver l'incident qu'il signale.
            // MCP: SELF (<5 lignes utiles, le reste est du commentaire)
            try {
                if (class_exists(\Modules\Notifications\Services\AutomationAlertService::class)) {
                    \Modules\Notifications\Services\AutomationAlertService::fire(
                        'queue',
                        $event->job->resolveName(),
                        $event->exception->getMessage(),
                        [
                            'connection' => $event->connectionName,
                            'trace' => $event->exception->getTraceAsString(),
                        ]
                    );
                } else {
                    Log::channel('automation_alerts')->warning(
                        'Aucune alerte envoyée : le service AutomationAlertService est introuvable (module Notifications désactivé ?).',
                        ['job' => $event->job->resolveName()]
                    );
                }
            } catch (\Throwable $e) {
                Log::channel('automation_alerts')->error(
                    'Aucune alerte envoyée : le service a levé une exception, avalée ici pour ne pas aggraver l\'échec du job.',
                    [
                        'job' => $event->job->resolveName(),
                        'erreur' => $e->getMessage(),
                    ]
                );
            }
        });
    }

    protected function configureRateLimiting(): void
    {
        RateLimiter::for('api', function (Request $request) {
            if (! $request->user()) {
                return Limit::perMinute(30)->by($request->ip());
            }

            $limit = (int) config('saas.rate_limits.default', 120);

            if (\Nwidart\Modules\Facades\Module::isEnabled('SaaS') && $request->user()->subscribed('default')) {
                $subscription = $request->user()->subscription('default');
                $price = $subscription?->stripe_price;

                $planLimits = config('saas.rate_limits.plans', []);
                $limit = $planLimits[$price] ?? (int) config('saas.rate_limits.subscribed', 300);
            }

            return Limit::perMinute($limit)->by($request->user()->id);
        });

        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        RateLimiter::for('sensitive', function (Request $request) {
            return Limit::perMinute(10)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('export', function (Request $request) {
            return Limit::perMinute(10)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('import', function (Request $request) {
            return Limit::perMinute(5)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('search', function (Request $request) {
            return Limit::perMinute(30)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('newsletter', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });
    }
}
