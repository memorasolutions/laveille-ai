<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Newsletter\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Newsletter\Models\Campaign;
use Modules\Newsletter\Models\Subscriber;

class CampaignNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected Campaign $campaign,
        protected Subscriber $subscriber
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->campaign->subject)
            ->greeting('Bonjour !')
            ->line($this->campaign->content)
            ->action('Se désabonner', route('newsletter.unsubscribe', $this->subscriber->token));
    }
}
