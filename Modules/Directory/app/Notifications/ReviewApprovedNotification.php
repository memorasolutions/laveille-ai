<?php

declare(strict_types=1);

namespace Modules\Directory\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Modules\Core\Notifications\TemplatedNotification;
use Modules\Directory\Models\ToolReview;

class ReviewApprovedNotification extends TemplatedNotification
{
    public function __construct(protected ToolReview $review) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    protected function getTemplateSlug(): string
    {
        return 'review_approved';
    }

    protected function getTemplateData(object $notifiable): array
    {
        return [
            'user' => ['name' => $notifiable->name, 'email' => $notifiable->email],
            'app' => ['name' => config('app.name'), 'url' => config('app.url')],
            'review' => [
                'tool_name' => $this->review->tool?->name ?? '',
                'title' => $this->review->title,
            ],
        ];
    }

    protected function getFallbackMail(object $notifiable): MailMessage
    {
        $toolName = $this->review->tool?->name ?? '';

        return (new MailMessage)
            ->subject(__('Votre avis a été approuvé'))
            ->greeting(__('Bonjour :name !', ['name' => $notifiable->name]))
            ->line(__('Votre avis sur « :tool » a été approuvé et publié.', ['tool' => $toolName]))
            ->action(__('Voir mes contributions'), route('user.contributions'))
            ->line(__('Merci pour votre contribution !'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'review_approved',
            'review_id' => $this->review->id,
            'tool_name' => $this->review->tool?->name ?? '',
            'message' => __('Votre avis sur « :tool » a été approuvé.', [
                'tool' => $this->review->tool?->name ?? '',
            ]),
        ];
    }
}
