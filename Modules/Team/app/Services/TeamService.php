<?php

// Author: MEMORA solutions, https://memora.solutions ; info@memora.ca

namespace Modules\Team\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Modules\Team\Models\Team;
use Modules\Team\Models\TeamInvitation;
use Modules\Team\Notifications\InviteMemberNotification;

class TeamService
{
    public static function create(User $owner, array $data): Team
    {
        return DB::transaction(function () use ($owner, $data) {
            $team = Team::create([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'logo' => $data['logo'] ?? null,
                'owner_id' => $owner->id,
                'settings' => $data['settings'] ?? null,
            ]);

            $team->members()->attach($owner->id, [
                'role' => 'owner',
                'accepted_at' => now(),
            ]);

            return $team;
        });
    }

    public static function invite(Team $team, string $email, string $role, User $invitedBy): TeamInvitation
    {
        $existingUser = User::where('email', $email)->first();
        if ($existingUser && $team->hasMember($existingUser)) {
            throw new \InvalidArgumentException(__('Cet utilisateur est déjà membre de l\'équipe.'));
        }

        $existing = $team->pendingInvitations()->where('email', $email)->first();
        if ($existing) {
            throw new \InvalidArgumentException(__('Une invitation est déjà en attente pour cette adresse.'));
        }

        $invitation = TeamInvitation::create([
            'team_id' => $team->id,
            'email' => $email,
            'role' => $role,
            'invited_by' => $invitedBy->id,
            'expires_at' => now()->addDays(7),
        ]);

        Notification::route('mail', $email)
            ->notify(new InviteMemberNotification($invitation));

        return $invitation;
    }

    public static function acceptInvitation(string $token): Team
    {
        return DB::transaction(function () use ($token): Team {
            $invitation = TeamInvitation::where('token', $token)->firstOrFail();

            if ($invitation->isExpired()) {
                throw new \InvalidArgumentException(__('Cette invitation a expiré.'));
            }

            if ($invitation->isAccepted()) {
                throw new \InvalidArgumentException(__('Cette invitation a déjà été acceptée.'));
            }

            $user = User::where('email', $invitation->email)->firstOrFail();

            /** @var Team $team */
            $team = $invitation->team;
            $team->members()->attach($user->id, [
                'role' => $invitation->role,
                'invited_at' => $invitation->created_at,
                'accepted_at' => now(),
            ]);

            $invitation->update(['accepted_at' => now()]);

            if (! $user->current_team_id) {
                $user->update(['current_team_id' => $invitation->team_id]);
            }

            return $team;
        });
    }

    public static function declineInvitation(string $token): void
    {
        $invitation = TeamInvitation::where('token', $token)->firstOrFail();
        $invitation->delete();
    }

    public static function removeMember(Team $team, User $user): void
    {
        if ($team->isOwner($user)) {
            throw new \InvalidArgumentException(__('Le propriétaire ne peut pas être retiré de l\'équipe.'));
        }

        $team->members()->detach($user->id);

        if ($user->current_team_id === $team->id) {
            $user->update(['current_team_id' => null]);
        }
    }

    public static function updateRole(Team $team, User $user, string $role): void
    {
        if ($team->isOwner($user)) {
            throw new \InvalidArgumentException(__('Le rôle du propriétaire ne peut pas être modifié.'));
        }

        $team->members()->updateExistingPivot($user->id, ['role' => $role]);
    }

    public static function transferOwnership(Team $team, User $newOwner): void
    {
        if (! $team->hasMember($newOwner)) {
            throw new \InvalidArgumentException(__('Le nouveau propriétaire doit être membre de l\'équipe.'));
        }

        DB::transaction(function () use ($team, $newOwner) {
            $oldOwner = $team->owner;
            $team->update(['owner_id' => $newOwner->id]);
            $team->members()->updateExistingPivot($oldOwner->id, ['role' => 'admin']);
            $team->members()->updateExistingPivot($newOwner->id, ['role' => 'owner']);
        });
    }
}
