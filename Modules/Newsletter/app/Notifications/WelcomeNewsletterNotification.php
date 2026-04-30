<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Newsletter\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Modules\Core\Notifications\TemplatedNotification;
use Modules\Newsletter\Models\Subscriber;
use Modules\Newsletter\Notifications\Concerns\HasOneClickUnsubscribe;

class WelcomeNewsletterNotification extends TemplatedNotification
{
    use HasOneClickUnsubscribe;

    public function __construct(private readonly Subscriber $subscriber) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return $this->applyOneClickUnsubscribe(parent::toMail($notifiable), $this->subscriber->token);
    }

    protected function getTemplateSlug(): string
    {
        return 'newsletter_welcome';
    }

    protected function getTemplateData(object $notifiable): array
    {
        return [
            'user' => ['name' => $this->subscriber->name ?? '', 'email' => $notifiable->email ?? ''],
            'app' => ['name' => config('app.name'), 'url' => config('app.url')],
            'subscriber' => [
                'token' => $this->subscriber->token,
                'name' => $this->subscriber->name ?? '',
            ],
        ];
    }

    protected function getFallbackMail(object $notifiable): MailMessage
    {
        $confirmUrl = route('newsletter.confirm', $this->subscriber->token);
        $unsubUrl = route('newsletter.unsubscribe', $this->subscriber->token);
        $dpoEmail = config('mail.privacy_dpo_email', 'stephane@memora.ca');

        return (new MailMessage)
            ->subject('Confirmez votre abonnement à la newsletter')
            ->greeting('Bonjour '.($this->subscriber->name ?? '').' !')
            ->line('Merci pour votre abonnement à notre newsletter hebdomadaire sur l’intelligence artificielle et la productivité, conçue spécialement pour le milieu québécois.')
            ->line('Nous utilisons votre adresse courriel uniquement pour vous envoyer cette newsletter.')
            ->action('Confirmer mon abonnement', $confirmUrl)
            ->line('Vos données sont conservées jusqu’à votre désabonnement.')
            ->line('Vous avez le droit d’accéder à vos données, de les rectifier ou de demander leur suppression en cliquant sur le lien de désabonnement ci-dessous ou en nous contactant à l’adresse suivante : '.$dpoEmail.'.')
            ->line('Ce traitement est encadré par la Loi 25 (Québec) et le Règlement général sur la protection des données (RGPD) de l’Union européenne.')
            ->line('Si vous ne souhaitez plus recevoir nos courriels, [cliquez ici pour vous désabonner]('.$unsubUrl.').')
            ->salutation('À bientôt !');
    }
}
