<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Newsletter\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Modules\Core\Providers\BaseModuleServiceProvider;
use Modules\Newsletter\Listeners\WorkflowTriggerListener;
use Modules\Newsletter\Mail\Transport\BrevoApiTransport;
use Modules\Newsletter\Services\BrevoService;

class NewsletterServiceProvider extends BaseModuleServiceProvider
{
    protected string $name = 'Newsletter';

    protected string $nameLower = 'newsletter';

    public function boot(): void
    {
        $this->bootModule();

        $this->commands([
            \Modules\Newsletter\Console\DigestCommand::class,
            \Modules\Newsletter\Console\ProcessWorkflowsCommand::class,
            \Modules\Newsletter\Console\UpdateDefiW18Command::class,
            \Modules\Newsletter\Console\RemindPendingCommand::class,
            \Modules\Newsletter\Console\PurgeUnconfirmedCommand::class,
        ]);

        Event::listen(Registered::class, [WorkflowTriggerListener::class, 'handleRegistered']);

        // #161 (2026-05-06) — bascule globale Mail Laravel via API HTTP Brevo,
        // contournement SMTP Gmail bloqué (cert mismatch cPanel autoconfig.server.memora.pro).
        // MAIL_MAILER=brevo dans .env active ce transport. Reuse BREVO_API_KEY existant.
        Mail::extend('brevo', fn () => $this->app->make(BrevoApiTransport::class));
    }

    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);

        $this->app->singleton(NewsletterMetricProvider::class);
        $this->app->tag([NewsletterMetricProvider::class], 'metric_providers');

        $this->app->singleton(BrevoService::class);
    }
}
