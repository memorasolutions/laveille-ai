<?php

namespace Modules\Academy\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event handler mappings for the application.
     *
     * @var array<string, array<int, string>>
     */
    protected $listen = [
        // M5 — Paiement Stripe : inscription automatique sur checkout.session.completed
        \Laravel\Cashier\Events\WebhookHandled::class => [
            \Modules\Academy\Listeners\StripeWebhookListener::class,
        ],

        // M7 — Déclencheurs Newsletter (défensifs — no-op si module Newsletter absent)
        \Modules\Academy\Events\AcademyEnrollmentCreated::class => [
            \Modules\Academy\Listeners\AcademyNewsletterTriggerListener::class . '@handleEnrollment',
        ],
        \Modules\Academy\Events\AcademyItemCompleted::class => [
            \Modules\Academy\Listeners\AcademyNewsletterTriggerListener::class . '@handleCompletion',
        ],
        \Modules\Academy\Events\AcademyCertificateIssued::class => [
            \Modules\Academy\Listeners\AcademyNewsletterTriggerListener::class . '@handleCertificate',
        ],
    ];

    /**
     * Indicates if events should be discovered.
     *
     * @var bool
     */
    protected static $shouldDiscoverEvents = true;

    /**
     * Configure the proper event listeners for email verification.
     */
    protected function configureEmailVerification(): void {}
}
