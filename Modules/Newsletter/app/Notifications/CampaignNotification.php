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
use Modules\Newsletter\Models\Campaign;
use Modules\Newsletter\Models\Subscriber;

class CampaignNotification extends TemplatedNotification
{
    public function __construct(
        protected Campaign $campaign,
        protected Subscriber $subscriber
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    protected function getTemplateSlug(): string
    {
        return 'newsletter_campaign';
    }

    protected function getTemplateData(object $notifiable): array
    {
        return [
            'user' => ['name' => $notifiable->name ?? '', 'email' => $notifiable->email ?? ''],
            'app' => ['name' => config('app.name'), 'url' => config('app.url')],
            'campaign' => [
                'subject' => $this->campaign->subject,
                'content' => $this->campaign->content,
            ],
            'subscriber' => [
                'token' => $this->subscriber->token,
            ],
        ];
    }

    protected function getFallbackMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->campaign->subject)
            ->greeting('Bonjour !')
            ->line($this->campaign->content)
            ->action('Se desabonner', route('newsletter.unsubscribe', $this->subscriber->token));
    }
}
