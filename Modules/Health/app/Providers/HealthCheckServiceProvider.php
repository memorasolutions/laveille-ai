<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Health\Providers;

use Modules\Core\Providers\BaseModuleServiceProvider;
use Spatie\Health\Checks\Checks\CacheCheck;
use Spatie\Health\Checks\Checks\DatabaseCheck;
use Spatie\Health\Checks\Checks\DebugModeCheck;
use Spatie\Health\Checks\Checks\EnvironmentCheck;
use Spatie\Health\Checks\Checks\OptimizedAppCheck;
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

        Health::checks([
            DatabaseCheck::new(),
            UsedDiskSpaceCheck::new()->warnWhenUsedSpaceIsAbovePercentage(70)->failWhenUsedSpaceIsAbovePercentage(90),
            DebugModeCheck::new(),
            EnvironmentCheck::new(),
            CacheCheck::new(),
            OptimizedAppCheck::new(),
            ScheduleCheck::new()->heartbeatMaxAgeInMinutes(2),
        ]);
    }
}
