<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Roadmap\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Roadmap\Enums\IdeaStatus;
use Modules\Roadmap\Models\Idea;

class IdeaStatusChanged extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected Idea $idea,
        protected string $oldStatus,
        protected string $newStatus,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $oldLabel = IdeaStatus::from($this->oldStatus)->label();
        $newLabel = IdeaStatus::from($this->newStatus)->label();

        return (new MailMessage)
            ->subject(__('Votre idée a changé de statut'))
            ->greeting(__('Bonjour !'))
            ->line(__('L\'idée ":title" a changé de statut.', ['title' => $this->idea->title]))
            ->line(__('Ancien statut : :status', ['status' => $oldLabel]))
            ->line(__('Nouveau statut : :status', ['status' => $newLabel]))
            ->action(__('Voir l\'idée'), route('roadmap.boards.show', $this->idea->board))
            ->line(__('Merci pour votre contribution !'));
    }
}
