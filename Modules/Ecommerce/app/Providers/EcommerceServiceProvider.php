<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Ecommerce\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Event;
use Modules\Core\Providers\BaseModuleServiceProvider;
use Modules\Ecommerce\Jobs\ProcessAbandonedCarts;
use Modules\Ecommerce\Contracts\TaxCalculatorInterface;
use Modules\Ecommerce\Events\LowStockDetected;
use Modules\Ecommerce\Listeners\NotifyAdminsLowStock;
use Modules\Ecommerce\Services\Tax\CanadaTaxCalculator;

class EcommerceServiceProvider extends BaseModuleServiceProvider
{
    protected string $name = 'Ecommerce';

    protected string $nameLower = 'ecommerce';

    public function boot(): void
    {
        $this->bootModule();

        Event::listen(LowStockDetected::class, NotifyAdminsLowStock::class);

        if (config('modules.ecommerce.abandoned_cart.enabled', false)) {
            $this->app->booted(function () {
                $schedule = $this->app->make(Schedule::class);
                $schedule->job(new ProcessAbandonedCarts)->hourly();
            });
        }
    }

    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);

        $this->app->bind(TaxCalculatorInterface::class, CanadaTaxCalculator::class);
    }

    /**
     * Override to register config under 'modules.ecommerce' prefix
     * for backward compatibility with all existing module code.
     */
    protected function registerConfig(): void
    {
        $path = module_path($this->name, 'config/config.php');
        $this->mergeConfigFrom($path, "modules.{$this->nameLower}");
    }
}
