<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Settings\Providers;

use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\Facades\Gate;
use Modules\Core\Contracts\SettingsReaderInterface;
use Modules\Core\Providers\BaseModuleServiceProvider;
use Modules\Settings\Facades\Settings;
use Modules\Settings\Models\Setting;
use Modules\Settings\Observers\SettingObserver;
use Modules\Settings\Policies\SettingPolicy;
use Modules\Settings\Services\SettingsService;

class SettingsServiceProvider extends BaseModuleServiceProvider
{
    protected string $name = 'Settings';

    protected string $nameLower = 'settings';

    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        $this->bootModule();
        Gate::policy(Setting::class, SettingPolicy::class);
        Setting::observe(SettingObserver::class);

        $this->applyDynamicMailConfig();
    }

    /**
     * Apply SMTP settings from database to Laravel mail config.
     */
    protected function applyDynamicMailConfig(): void
    {
        if (! $this->app->runningInConsole() && \Illuminate\Support\Facades\Schema::hasTable('settings')) {
            try {
                $mailSettings = Setting::where('group', 'mail')->pluck('value', 'key');

                if ($mailSettings->get('mail_host')) {
                    config([
                        'mail.mailers.smtp.host' => $mailSettings->get('mail_host'),
                        'mail.mailers.smtp.port' => (int) $mailSettings->get('mail_port', 587),
                        'mail.mailers.smtp.username' => $mailSettings->get('mail_username'),
                        'mail.mailers.smtp.password' => $mailSettings->get('mail_password'),
                        'mail.mailers.smtp.encryption' => $mailSettings->get('mail_encryption', 'tls'),
                    ]);
                }

                if ($mailSettings->get('mail_from_address')) {
                    config([
                        'mail.from.address' => $mailSettings->get('mail_from_address'),
                        'mail.from.name' => $mailSettings->get('mail_from_name', config('app.name')),
                    ]);
                }
            } catch (\Throwable) {
                // Table doesn't exist yet (fresh install) - silently skip
            }
        }
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);
        $this->app->singleton(SettingsService::class);
        $this->app->bind(SettingsReaderInterface::class, SettingsService::class);
        AliasLoader::getInstance()->alias('Settings', Settings::class);
    }
}
