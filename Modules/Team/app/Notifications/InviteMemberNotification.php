<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Team\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Modules\Core\Notifications\TemplatedNotification;
use Modules\Team\Models\TeamInvitation;

class InviteMemberNotification extends TemplatedNotification
{
    public function __construct(
        protected TeamInvitation $invitation
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    protected function getTemplateSlug(): string
    {
        return 'team_invite_member';
    }

    protected function getTemplateData(object $notifiable): array
    {
        $team = $this->invitation->team;
        $inviter = $this->invitation->inviter;

        return [
            'user' => ['name' => $notifiable->name ?? '', 'email' => $notifiable->email ?? ''],
            'app' => ['name' => config('app.name'), 'url' => config('app.url')],
            'invitation' => [
                'team_name' => $team->name,
                'inviter_name' => $inviter->name,
                'role' => $this->invitation->role,
                'token' => $this->invitation->token,
            ],
        ];
    }

    protected function getFallbackMail(object $notifiable): MailMessage
    {
        $team = $this->invitation->team;
        $inviter = $this->invitation->inviter;

        return (new MailMessage)
            ->subject(__('Invitation a rejoindre :team', ['team' => $team->name]))
            ->greeting(__('Bonjour !'))
            ->line(__(':name vous a invite a rejoindre l\'equipe ":team".', [
                'name' => $inviter->name,
                'team' => $team->name,
            ]))
            ->line(__('Role propose : :role', ['role' => ucfirst($this->invitation->role)]))
            ->action(__('Accepter l\'invitation'), route('teams.invitations.accept', $this->invitation->token))
            ->line(__('Cette invitation expire dans 7 jours.'))
            ->line(__('Si vous ne souhaitez pas rejoindre cette equipe, aucune action n\'est requise.'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'team_id' => $this->invitation->team_id,
            'team_name' => $this->invitation->team->name,
            'role' => $this->invitation->role,
        ];
    }
}
