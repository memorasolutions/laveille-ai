<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Health\Providers;

use Modules\Core\Providers\BaseModuleServiceProvider;
use Modules\Health\Checks\OpcacheCheck;
use Modules\Health\Checks\OpenRouterCreditCheck;
use Spatie\Health\Checks\Checks\CacheCheck;
use Spatie\Health\Checks\Checks\DatabaseCheck;
use Spatie\Health\Checks\Checks\DebugModeCheck;
use Spatie\Health\Checks\Checks\EnvironmentCheck;
use Spatie\Health\Checks\Checks\ScheduleCheck;
use Spatie\Health\Checks\Checks\UsedDiskSpaceCheck;
use Spatie\Health\Facades\Health;

class HealthCheckServiceProvider extends BaseModuleServiceProvider
{
    protected string $name = 'Health';

    protected string $nameLower = 'health';

    public function boot(): void
    {
        $this->bootModule();

        $checks = [
            DatabaseCheck::new(),
            UsedDiskSpaceCheck::new()->warnWhenUsedSpaceIsAbovePercentage(70)->failWhenUsedSpaceIsAbovePercentage(90),
            DebugModeCheck::new(),
            EnvironmentCheck::new(),
            CacheCheck::new(),
            // heartbeatMaxAgeInMinutes(5), pas 2 (defaut Spatie) : incident reel du 2026-08-02
            // 10h41-10h42 UTC, auto-resolu des le passage suivant. Donnee de production (30 jours,
            // 43 631 passages) : 290 echecs isoles (0,66 %), tous des blips de 1-2 minutes qui
            // se resolvent seuls - la meme surcharge ponctuelle du pool PHP-FPM partage par des
            // dizaines de crons d'autres sites que celle deja identifiee pour OPcache (v1.139.6).
            // 5 minutes reste largement suffisant pour detecter un VRAI arret du scheduler.
            ScheduleCheck::new()->heartbeatMaxAgeInMinutes(5),
        ];

        // OptimizedAppCheck est volontairement RETIRE. Il exige que la configuration soit mise
        // en cache, or `config:cache` est INTERDIT sur ce projet : des env() sont lus au moment
        // de l'execution et la mise en cache ferme /academie (decision du 2026-06-30). Ce
        // controle serait donc rouge en PERMANENCE, par conception, et enverrait une alerte
        // pour une condition volontaire. Un controle qui ne peut jamais passer n'alerte de
        // rien : il apprend seulement a ignorer le tableau de bord. A remettre le jour ou la
        // dette des env() au runtime sera resorbee.

        if ((bool) config('health.opcache.enabled', false)) {
            $checks[] = OpcacheCheck::new();
        }

        // Actif par defaut (cf. le commentaire du bloc `openrouter` dans config/health.php) :
        // l'epuisement du credit arrete l'enrichissement de l'annuaire SANS aucune erreur
        // visible, et c'est precisement cette classe de panne muette qu'on cherche a eteindre.
        if ((bool) config('health.openrouter.enabled', true)) {
            $checks[] = OpenRouterCreditCheck::new();
        }

        Health::checks($checks);
    }
}
