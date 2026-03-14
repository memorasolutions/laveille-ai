<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Modules\Team\Models\Team;
use Modules\Team\Models\TeamInvitation;
use Modules\Team\Services\TeamService;

uses(Tests\TestCase::class, RefreshDatabase::class);

// ---------------------------------------------------------------------------
// Helper
// ---------------------------------------------------------------------------

function makeOwner(): User
{
    return User::factory()->create();
}

// ---------------------------------------------------------------------------
// Team creation
// ---------------------------------------------------------------------------

it('creates a team with owner', function () {
    $owner = makeOwner();

    $team = TeamService::create($owner, ['name' => 'Avengers']);

    expect($team->exists)->toBeTrue()
        ->and($team->name)->toBe('Avengers')
        ->and($team->owner_id)->toBe($owner->id);

    $this->assertDatabaseHas('teams', ['name' => 'Avengers', 'owner_id' => $owner->id]);
});

it('auto-generates unique slug from team name', function () {
    $owner = makeOwner();

    $team = TeamService::create($owner, ['name' => 'My Cool Team']);

    expect($team->slug)->not->toBeEmpty()
        ->and($team->slug)->toBe('my-cool-team');
});

it('generates a suffixed slug when the base slug already exists', function () {
    $owner = makeOwner();

    $first = TeamService::create($owner, ['name' => 'Duplicate Team']);
    $second = TeamService::create($owner, ['name' => 'Duplicate Team']);

    expect($first->slug)->toBe('duplicate-team')
        ->and($second->slug)->not->toBe('duplicate-team')
        ->and($second->slug)->toStartWith('duplicate-team');
});

it('owner is added as member with owner role', function () {
    $owner = makeOwner();

    $team = TeamService::create($owner, ['name' => 'Rebels']);

    expect($team->hasMember($owner))->toBeTrue()
        ->and($team->memberRole($owner))->toBe('owner');
});

// ---------------------------------------------------------------------------
// Invitations
// ---------------------------------------------------------------------------

it('invites a user by email', function () {
    Notification::fake();

    $owner = makeOwner();
    $team = TeamService::create($owner, ['name' => 'Crew']);

    $invitation = TeamService::invite($team, 'recruit@example.com', 'member', $owner);

    expect($invitation->exists)->toBeTrue()
        ->and($invitation->email)->toBe('recruit@example.com')
        ->and($invitation->role)->toBe('member')
        ->and($invitation->token)->not->toBeEmpty();

    $this->assertDatabaseHas('team_invitations', [
        'team_id' => $team->id,
        'email' => 'recruit@example.com',
    ]);
});

it('prevents duplicate pending invitations for the same email', function () {
    Notification::fake();

    $owner = makeOwner();
    $team = TeamService::create($owner, ['name' => 'Squad']);

    TeamService::invite($team, 'dupe@example.com', 'member', $owner);

    expect(fn () => TeamService::invite($team, 'dupe@example.com', 'member', $owner))
        ->toThrow(InvalidArgumentException::class);
});

it('prevents inviting an existing member', function () {
    Notification::fake();

    $owner = makeOwner();
    $member = User::factory()->create(['email' => 'existing@example.com']);
    $team = TeamService::create($owner, ['name' => 'Guild']);

    // Add member directly.
    $team->members()->attach($member->id, ['role' => 'member', 'accepted_at' => now()]);

    expect(fn () => TeamService::invite($team, 'existing@example.com', 'member', $owner))
        ->toThrow(InvalidArgumentException::class);
});

// ---------------------------------------------------------------------------
// Accepting invitations
// ---------------------------------------------------------------------------

it('accepts a valid invitation', function () {
    Notification::fake();

    $owner = makeOwner();
    $team = TeamService::create($owner, ['name' => 'Fellowship']);
    $newUser = User::factory()->create(['email' => 'frodo@example.com']);

    $invitation = TeamService::invite($team, 'frodo@example.com', 'member', $owner);

    $result = TeamService::acceptInvitation($invitation->token);

    expect($result->id)->toBe($team->id);
    expect($team->hasMember($newUser))->toBeTrue();
    expect($team->memberRole($newUser))->toBe('member');

    $this->assertDatabaseHas('team_invitations', [
        'id' => $invitation->id,
        'accepted_at' => now()->toDateTimeString(),
    ]);
});

it('rejects an expired invitation', function () {
    $invitation = TeamInvitation::factory()->expired()->create();

    expect(fn () => TeamService::acceptInvitation($invitation->token))
        ->toThrow(InvalidArgumentException::class);
});

it('rejects an already-accepted invitation', function () {
    $owner = makeOwner();
    $team = TeamService::create($owner, ['name' => 'Order']);
    $user = User::factory()->create(['email' => 'yoda@example.com']);

    Notification::fake();
    $invitation = TeamService::invite($team, 'yoda@example.com', 'member', $owner);

    // Accept once.
    TeamService::acceptInvitation($invitation->token);

    // Trying to accept again must fail.
    expect(fn () => TeamService::acceptInvitation($invitation->token))
        ->toThrow(InvalidArgumentException::class);
});

// ---------------------------------------------------------------------------
// Member management
// ---------------------------------------------------------------------------

it('removes a member from a team', function () {
    $owner = makeOwner();
    $member = User::factory()->create();
    $team = TeamService::create($owner, ['name' => 'Pirates']);

    $team->members()->attach($member->id, ['role' => 'member', 'accepted_at' => now()]);
    expect($team->hasMember($member))->toBeTrue();

    TeamService::removeMember($team, $member);

    $team->load('members');
    expect($team->hasMember($member))->toBeFalse();
});

it('prevents removing the owner from a team', function () {
    $owner = makeOwner();
    $team = TeamService::create($owner, ['name' => 'Crusaders']);

    expect(fn () => TeamService::removeMember($team, $owner))
        ->toThrow(InvalidArgumentException::class);
});

// ---------------------------------------------------------------------------
// Role management
// ---------------------------------------------------------------------------

it('updates a member role', function () {
    $owner = makeOwner();
    $member = User::factory()->create();
    $team = TeamService::create($owner, ['name' => 'Rangers']);

    $team->members()->attach($member->id, ['role' => 'member', 'accepted_at' => now()]);

    TeamService::updateRole($team, $member, 'admin');

    expect($team->memberRole($member))->toBe('admin');
});

it('prevents changing the owner role via updateRole', function () {
    $owner = makeOwner();
    $team = TeamService::create($owner, ['name' => 'Spartans']);

    expect(fn () => TeamService::updateRole($team, $owner, 'member'))
        ->toThrow(InvalidArgumentException::class);
});

// ---------------------------------------------------------------------------
// Ownership transfer
// ---------------------------------------------------------------------------

it('transfers ownership to an existing member', function () {
    $owner = makeOwner();
    $newOwner = User::factory()->create();
    $team = TeamService::create($owner, ['name' => 'Stargate']);

    $team->members()->attach($newOwner->id, ['role' => 'member', 'accepted_at' => now()]);

    TeamService::transferOwnership($team, $newOwner);

    $team->refresh();

    expect($team->owner_id)->toBe($newOwner->id)
        ->and($team->memberRole($newOwner))->toBe('owner')
        ->and($team->memberRole($owner))->toBe('admin');
});

it('prevents transferring ownership to a non-member', function () {
    $owner = makeOwner();
    $stranger = User::factory()->create();
    $team = TeamService::create($owner, ['name' => 'Alliance']);

    expect(fn () => TeamService::transferOwnership($team, $stranger))
        ->toThrow(InvalidArgumentException::class);
});
