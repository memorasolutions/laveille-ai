<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Booking\Providers;

use Livewire\Livewire;
use Modules\Core\Providers\BaseModuleServiceProvider;

class BookingServiceProvider extends BaseModuleServiceProvider
{
    protected string $name = 'Booking';

    protected string $nameLower = 'booking';

    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        $this->registerCommands();
        $this->registerCommandSchedules();
        $this->bootModule();

        Livewire::component('booking-wizard', \Modules\Booking\Livewire\BookingWizard::class);
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->app->register(EventServiceProvider::class);
        $this->app->register(RouteServiceProvider::class);

        $this->app->singleton(BookingMetricProvider::class);
        $this->app->tag([BookingMetricProvider::class], 'metric_providers');
    }

    /**
     * Register commands in the format of Command::class
     */
    protected function registerCommands(): void
    {
        $this->commands([
            \Modules\Booking\Console\SendBookingReminders::class,
        ]);
    }

    /**
     * Register command Schedules.
     */
    protected function registerCommandSchedules(): void
    {
        // $this->app->booted(function () {
        //     $schedule = $this->app->make(Schedule::class);
        //     $schedule->command('inspire')->hourly();
        // });
    }
}
