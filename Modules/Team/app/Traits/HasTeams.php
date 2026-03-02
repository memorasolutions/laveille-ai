<?php

// Author: MEMORA solutions, https://memora.solutions ; info@memora.ca

namespace Modules\Team\Traits;

use Modules\Team\Models\Team;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait HasTeams
{
    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'team_user')
            ->withPivot('role', 'invited_at', 'accepted_at')
            ->withTimestamps();
    }

    public function ownedTeams(): HasMany
    {
        return $this->hasMany(Team::class, 'owner_id');
    }

    public function currentTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'current_team_id');
    }

    public function switchTeam(Team $team): void
    {
        if (! $this->belongsToTeam($team)) {
            throw new \InvalidArgumentException('User does not belong to this team.');
        }

        $this->current_team_id = $team->id;
        $this->save();
    }

    public function isTeamOwner(Team $team): bool
    {
        return $team->owner_id === $this->id;
    }

    public function teamRole(Team $team): ?string
    {
        $membership = $this->teams()->where('teams.id', $team->id)->first();

        return $membership?->pivot->role;
    }

    public function belongsToTeam(Team $team): bool
    {
        return $this->teams()->where('teams.id', $team->id)->exists();
    }
}
