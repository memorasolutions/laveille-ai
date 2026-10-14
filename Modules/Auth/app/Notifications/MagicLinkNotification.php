<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Auth\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Modules\Core\Notifications\TemplatedNotification;

class MagicLinkNotification extends TemplatedNotification
{
    public function __construct(private readonly string $token, private readonly string $mailerName = 'postmark')
    {
        // Forcer l'envoi synchrone — le code OTP doit arriver immédiatement
        $this->onConnection('sync');
    }

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        // Le code de connexion (OTP) part par Postmark : service transactionnel payé
        // spécifiquement pour les OTP (décision du fondateur, 2026-10-02). Le mailer est
        // paramétrable pour permettre un repli vers 'workspace' si Postmark est indisponible
        // (voir MagicLinkController), afin que la connexion ne tombe jamais en panne.
        return parent::toMail($notifiable)->mailer($this->mailerName);
    }

    protected function getTemplateSlug(): string
    {
        return 'magic_link';
    }

    protected function getTemplateData(object $notifiable): array
    {
        return [
            'user' => ['name' => $notifiable->name, 'email' => $notifiable->email],
            'app' => ['name' => config('app.name'), 'url' => config('app.url')],
            'token' => $this->token,
            'expire_minutes' => '15',
        ];
    }

    protected function getFallbackMail(object $notifiable): MailMessage
    {
        // Vue Markdown dédiée (pas l'API greeting()/line()) pour que la partie
        // TEXTE BRUT du courriel soit propre : pas de titre « # », pas de « ** »
        // autour du code, pas de commentaire HTML visible. Voir la vue pour le
        // détail des espaces insécables.
        return (new MailMessage)
            ->subject('Votre code de connexion')
            ->markdown('auth::emails.magic-link', [
                'appName' => config('app.name'),
                'token' => $this->token,
                'expire' => '15',
            ]);
    }
}
