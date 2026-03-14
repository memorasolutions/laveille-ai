<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

namespace Modules\Team\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Team\Models\TeamInvitation;

class InviteMemberNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected TeamInvitation $invitation
    ) {}

    public function via(mixed $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        $team = $this->invitation->team;
        $inviter = $this->invitation->inviter;

        return (new MailMessage)
            ->subject(__('Invitation à rejoindre :team', ['team' => $team->name]))
            ->greeting(__('Bonjour !'))
            ->line(__(':name vous a invité à rejoindre l\'équipe ":team".', [
                'name' => $inviter->name,
                'team' => $team->name,
            ]))
            ->line(__('Rôle proposé : :role', ['role' => ucfirst($this->invitation->role)]))
            ->action(__('Accepter l\'invitation'), route('teams.invitations.accept', $this->invitation->token))
            ->line(__('Cette invitation expire dans 7 jours.'))
            ->line(__('Si vous ne souhaitez pas rejoindre cette équipe, aucune action n\'est requise.'));
    }

    public function toArray(mixed $notifiable): array
    {
        return [
            'team_id' => $this->invitation->team_id,
            'team_name' => $this->invitation->team->name,
            'role' => $this->invitation->role,
        ];
    }
}
