<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Roadmap\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Modules\Core\Notifications\TemplatedNotification;
use Modules\Roadmap\Enums\IdeaStatus;
use Modules\Roadmap\Models\Idea;

class IdeaStatusChanged extends TemplatedNotification
{
    public function __construct(
        protected Idea $idea,
        protected string $oldStatus,
        protected string $newStatus,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    protected function getTemplateSlug(): string
    {
        return 'roadmap_idea_status';
    }

    protected function getTemplateData(object $notifiable): array
    {
        return [
            'user' => ['name' => $notifiable->name, 'email' => $notifiable->email],
            'app' => ['name' => config('app.name'), 'url' => config('app.url')],
            'idea' => [
                'title' => $this->idea->title,
                'old_status' => IdeaStatus::from($this->oldStatus)->label(),
                'new_status' => IdeaStatus::from($this->newStatus)->label(),
            ],
        ];
    }

    protected function getFallbackMail(object $notifiable): MailMessage
    {
        $oldLabel = IdeaStatus::from($this->oldStatus)->label();
        $newLabel = IdeaStatus::from($this->newStatus)->label();

        return (new MailMessage)
            ->subject(__('Votre idee a change de statut'))
            ->greeting(__('Bonjour !'))
            ->line(__('L\'idee ":title" a change de statut.', ['title' => $this->idea->title]))
            ->line(__('Ancien statut : :status', ['status' => $oldLabel]))
            ->line(__('Nouveau statut : :status', ['status' => $newLabel]))
            ->action(__('Voir l\'idee'), route('roadmap.boards.show', $this->idea->board))
            ->line(__('Merci pour votre contribution !'));
    }
}
