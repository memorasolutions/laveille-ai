<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 */

declare(strict_types=1);

namespace Modules\Core\Mail\Traits;

/**
 * Force un Mailable à passer par le mailer 'workspace' (SMTP Google Workspace).
 *
 * Brevo (mailer default) est réservé aux campagnes newsletter bulk (quota
 * limité). Tout mail transactionnel (auth, password reset, notifications,
 * alertes, confirmations) doit utiliser ce trait pour préserver le quota
 * Brevo. Décision user 2026-05-19 #255.
 *
 * Mécanisme : Laravel Mailable déclare `public $mailer;` (Illuminate\Mail\Mailable:176)
 * et l'utilise dans `send()` via `$mailer->mailer($this->mailer)` pour résoudre le
 * MailerInterface effectif. PHP interdit qu'un trait redéfinisse cette property
 * avec une valeur initiale (signature incompatible avec la parent class), donc on
 * passe par la méthode legacy `build()` que Laravel appelle automatiquement dans
 * `prepareMailableForDelivery()` (vendor/laravel/framework/.../Mailable.php:1691)
 * avant tout envoi (send + queue + render).
 *
 * Pour les Mailables qui définissent leur propre `build()` (ex: Booking* qui
 * utilisent l'API legacy markdown/subject chainée), appeler `$this->routeToWorkspaceMailer()`
 * en première ligne de leur build().
 *
 * Pour les Mailables modernes (envelope() + content()), le `build()` du trait
 * est utilisé automatiquement par Laravel.
 *
 * Usage : `use RoutesToWorkspaceMailer;` dans la classe Mailable. Pas besoin
 * de toucher au constructeur (sauf pour les 3 Booking* qui doivent appeler
 * `$this->routeToWorkspaceMailer()` au début de leur build()).
 *
 * Mailables routés via ce trait (transactionnels) :
 *   - Modules\Api\Mail\RegistrationAttemptMail
 *   - Modules\Blog\Mail\ArticleSubmissionNotification
 *   - Modules\Booking\Mail\BookingCancellation
 *   - Modules\Booking\Mail\BookingConfirmation
 *   - Modules\Booking\Mail\BookingReminder
 *   - Modules\Directory\Mail\HealthCheckReportMail
 *   - Modules\Notifications\Mail\DigestMail
 *   - Modules\Notifications\Mail\WelcomeMail
 *   - Modules\Shop\Mail\AbandonmentReminderMail
 *   - Modules\Tools\Mail\FiscalRatesReminderMail
 */
trait RoutesToWorkspaceMailer
{
    /**
     * Set le mailer effectif à 'workspace' (SMTP Google Workspace).
     *
     * Appelée automatiquement par Laravel via build() pour les Mailables sans
     * build() override (envelope/content API moderne). Appelée explicitement
     * dans les Mailables qui ont leur propre build() (Booking* API legacy).
     */
    public function routeToWorkspaceMailer(): static
    {
        $this->mailer = 'workspace';

        return $this;
    }

    /**
     * Hook Laravel `build()` — appelé par `prepareMailableForDelivery()` avant
     * tout envoi. Set le mailer 'workspace' de façon transparente pour les
     * Mailables qui ne définissent pas leur propre build() (API moderne
     * envelope/content).
     *
     * Les Mailables qui définissent leur propre build() shadow celui-ci par
     * règles PHP standard (classe > trait) ; ils doivent alors appeler
     * `$this->routeToWorkspaceMailer()` en première ligne de leur build().
     */
    public function build(): static
    {
        return $this->routeToWorkspaceMailer();
    }
}
