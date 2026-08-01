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
            ScheduleCheck::new()->heartbeatMaxAgeInMinutes(2),
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

        Health::checks($checks);
    }
}
