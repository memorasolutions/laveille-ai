<?php

declare(strict_types=1);

namespace Modules\Directory\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Modules\Core\Notifications\TemplatedNotification;
use Modules\Directory\Models\ToolReview;

class ReviewRejectedNotification extends TemplatedNotification
{
    public function __construct(protected ToolReview $review) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    protected function getTemplateSlug(): string
    {
        return 'review_rejected';
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
            ->subject(__("Votre avis n'a pas été retenu"))
            ->greeting(__('Bonjour :name !', ['name' => $notifiable->name]))
            ->line(__("Votre avis sur « :tool » n'a pas été retenu par notre équipe.", ['tool' => $toolName]))
            ->line(__("N'hésitez pas à soumettre un nouvel avis plus détaillé."))
            ->action(__('Voir mes contributions'), route('user.contributions'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'review_rejected',
            'review_id' => $this->review->id,
            'tool_name' => $this->review->tool?->name ?? '',
            'message' => __("Votre avis sur « :tool » n'a pas été retenu.", [
                'tool' => $this->review->tool?->name ?? '',
            ]),
        ];
    }
}
