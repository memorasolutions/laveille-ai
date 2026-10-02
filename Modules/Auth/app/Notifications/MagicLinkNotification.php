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
        return (new MailMessage)
            ->subject('Votre code de connexion')
            ->greeting('Bonjour !')
            ->line('Votre code de connexion est :')
            ->line('**'.$this->token.'**')
            ->line('Ce code expire dans 15 minutes.')
            ->line('💡 Astuce : ajoutez info@laveille.ai à vos contacts (Gmail) ou à vos expéditeurs fiables (Outlook/Microsoft) pour ne jamais manquer un code dans vos pourriels.')
            ->line('Si vous n\'avez pas demandé ce code, ignorez cet email.')
            ->salutation('L\'équipe');
    }
}
