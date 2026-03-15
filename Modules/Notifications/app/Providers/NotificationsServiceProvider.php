<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Notifications\Providers;

use Modules\Core\Providers\BaseModuleServiceProvider;

class NotificationsServiceProvider extends BaseModuleServiceProvider
{
    protected string $name = 'Notifications';

    protected string $nameLower = 'notifications';

    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        $this->registerCommands();
        $this->registerCommandSchedules();
        $this->bootModule();
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->app->register(EventServiceProvider::class);
        $this->app->register(RouteServiceProvider::class);

        $this->app->singleton(\Modules\Notifications\Services\NotificationService::class);
        $this->app->singleton(\Modules\Notifications\Services\EmailTemplateService::class);

        $this->app->bind(
            \Modules\Notifications\Contracts\SmsDriverInterface::class,
            function () {
                $smsEnabled = \Modules\Settings\Facades\Settings::get('sms_enabled', false);
                if ($smsEnabled) {
                    return new \Modules\Notifications\Drivers\VoipMsService(
                        (string) \Modules\Settings\Facades\Settings::get('voipms_api_username', ''),
                        (string) \Modules\Settings\Facades\Settings::get('voipms_api_password', ''),
                        (string) \Modules\Settings\Facades\Settings::get('voipms_did_number', ''),
                    );
                }

                return new \Modules\Notifications\Drivers\NullSmsDriver;
            },
        );
    }

    /**
     * Register commands in the format of Command::class
     */
    protected function registerCommands(): void
    {
        $this->commands([
            \Modules\Notifications\Console\GenerateVapidKeysCommand::class,
            \Modules\Notifications\Console\SendNotificationDigest::class,
        ]);
    }

    /**
     * Register command Schedules.
     */
    protected function registerCommandSchedules(): void
    {
        // Scheduling in routes/console.php (Laravel standard)
    }
}
