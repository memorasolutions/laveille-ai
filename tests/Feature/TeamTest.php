<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

// Author: MEMORA solutions, https://memora.solutions ; info@memora.ca

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Modules\RolesPermissions\Database\Seeders\RolesAndPermissionsSeeder;
use Modules\Team\Models\Team;
use Modules\Team\Models\TeamInvitation;
use Modules\Team\Services\TeamService;

uses(RefreshDatabase::class);

beforeEach(function () {
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    $this->seed(RolesAndPermissionsSeeder::class);
    Notification::fake();
});

// Helpers

function makeTeamAdmin(): User
{
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    return $user;
}

function makeTeamEditor(): User
{
    $user = User::factory()->create();
    $user->assignRole('editor');

    return $user;
}

function makeTeamUser(): User
{
    $user = User::factory()->create();
    $user->assignRole('user');

    return $user;
}

// ── SECTION 1 : TeamService ──────────────────────────────────────────────────

test('TeamService::create crée une équipe et ajoute le owner comme membre', function () {
    $owner = User::factory()->create();

    $team = TeamService::create($owner, ['name' => 'Équipe Alpha']);

    expect($team)->toBeInstanceOf(Team::class);
    expect($team->name)->toBe('Équipe Alpha');
    expect($team->owner_id)->toBe($owner->id);
    expect($team->hasMember($owner))->toBeTrue();
    expect($team->memberRole($owner))->toBe('owner');
});

test('TeamService::invite crée une invitation et ne permet pas doublon', function () {
    $owner = User::factory()->create();
    $team = TeamService::create($owner, ['name' => 'Équipe Beta']);

    $invitation = TeamService::invite($team, 'invite@example.com', 'member', $owner);

    expect($invitation)->toBeInstanceOf(TeamInvitation::class);
    expect($invitation->email)->toBe('invite@example.com');
    expect($invitation->token)->not->toBeEmpty();

    // Doublon : doit lever une exception
    expect(fn () => TeamService::invite($team, 'invite@example.com', 'member', $owner))
        ->toThrow(\InvalidArgumentException::class);
});

test('TeamService::acceptInvitation ajoute le user à l\'équipe', function () {
    $owner = User::factory()->create();
    $newMember = User::factory()->create(['email' => 'newmember@example.com']);
    $team = TeamService::create($owner, ['name' => 'Équipe Gamma']);

    $invitation = TeamService::invite($team, 'newmember@example.com', 'member', $owner);

    $result = TeamService::acceptInvitation($invitation->token);

    expect($result->id)->toBe($team->id);
    expect($team->fresh()->hasMember($newMember))->toBeTrue();
    expect($invitation->fresh()->isAccepted())->toBeTrue();
});

test('TeamService::acceptInvitation rejette les tokens expirés', function () {
    $owner = User::factory()->create();
    $newMember = User::factory()->create(['email' => 'expired@example.com']);
    $team = TeamService::create($owner, ['name' => 'Équipe Delta']);

    $invitation = TeamInvitation::create([
        'team_id' => $team->id,
        'email' => 'expired@example.com',
        'role' => 'member',
        'invited_by' => $owner->id,
        'expires_at' => now()->subDay(),
    ]);

    expect(fn () => TeamService::acceptInvitation($invitation->token))
        ->toThrow(\InvalidArgumentException::class);
});

test('TeamService::removeMember empêche de retirer le owner', function () {
    $owner = User::factory()->create();
    $team = TeamService::create($owner, ['name' => 'Équipe Epsilon']);

    expect(fn () => TeamService::removeMember($team, $owner))
        ->toThrow(\InvalidArgumentException::class);
});

test('TeamService::updateRole empêche de changer le rôle du owner', function () {
    $owner = User::factory()->create();
    $team = TeamService::create($owner, ['name' => 'Équipe Zeta']);

    expect(fn () => TeamService::updateRole($team, $owner, 'admin'))
        ->toThrow(\InvalidArgumentException::class);
});

test('TeamService::transferOwnership transfère la propriété', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $team = TeamService::create($owner, ['name' => 'Équipe Eta']);
    $team->members()->attach($member->id, ['role' => 'admin', 'accepted_at' => now()]);

    TeamService::transferOwnership($team, $member);

    $team->refresh();
    expect($team->owner_id)->toBe($member->id);
    expect($team->memberRole($member))->toBe('owner');
    expect($team->memberRole($owner))->toBe('admin');
});

// ── SECTION 2 : Routes admin (HTTP) ─────────────────────────────────────────

test('admin peut lister les équipes', function () {
    $admin = makeTeamAdmin();

    $this->actingAs($admin)
        ->get(route('admin.teams.index'))
        ->assertOk();
});

test('admin peut créer une équipe', function () {
    $admin = makeTeamAdmin();

    $this->actingAs($admin)
        ->post(route('admin.teams.store'), [
            'name' => 'Nouvelle Équipe',
            'description' => 'Description test',
        ])
        ->assertRedirect(route('admin.teams.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('teams', ['name' => 'Nouvelle Équipe']);
});

test('admin peut voir le détail d\'une équipe', function () {
    $admin = makeTeamAdmin();
    $team = TeamService::create($admin, ['name' => 'Équipe Show']);

    $this->actingAs($admin)
        ->get(route('admin.teams.show', $team))
        ->assertOk()
        ->assertSee($team->name);
});

test('admin peut modifier une équipe', function () {
    $admin = makeTeamAdmin();
    $team = TeamService::create($admin, ['name' => 'Équipe Originale']);

    $this->actingAs($admin)
        ->put(route('admin.teams.update', $team), [
            'name' => 'Équipe Modifiée',
            'description' => 'Nouvelle description',
        ])
        ->assertRedirect(route('admin.teams.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('teams', ['name' => 'Équipe Modifiée']);
});

test('admin peut supprimer une équipe qu\'il possède', function () {
    $admin = makeTeamAdmin();
    $team = TeamService::create($admin, ['name' => 'Équipe À Supprimer']);

    $this->actingAs($admin)
        ->delete(route('admin.teams.destroy', $team))
        ->assertRedirect(route('admin.teams.index'))
        ->assertSessionHas('success');

    $this->assertSoftDeleted('teams', ['id' => $team->id]);
});

test('admin peut inviter un membre', function () {
    $admin = makeTeamAdmin();
    $team = TeamService::create($admin, ['name' => 'Équipe Invite']);

    $this->actingAs($admin)
        ->post(route('admin.teams.invite', $team), [
            'email' => 'invite@example.com',
            'role' => 'member',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $this->assertDatabaseHas('team_invitations', [
        'team_id' => $team->id,
        'email' => 'invite@example.com',
    ]);
});

test('admin peut retirer un membre', function () {
    $admin = makeTeamAdmin();
    $member = User::factory()->create();
    $team = TeamService::create($admin, ['name' => 'Équipe Retrait']);
    $team->members()->attach($member->id, ['role' => 'member', 'accepted_at' => now()]);

    $this->actingAs($admin)
        ->delete(route('admin.teams.members.remove', [$team, $member]))
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($team->fresh()->hasMember($member))->toBeFalse();
});

test('admin peut changer le rôle d\'un membre', function () {
    $admin = makeTeamAdmin();
    $member = User::factory()->create();
    $team = TeamService::create($admin, ['name' => 'Équipe Rôle']);
    $team->members()->attach($member->id, ['role' => 'member', 'accepted_at' => now()]);

    $this->actingAs($admin)
        ->patch(route('admin.teams.members.role', [$team, $member]), [
            'role' => 'admin',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($team->memberRole($member))->toBe('admin');
});

// ── SECTION 3 : Permissions (403) ────────────────────────────────────────────

test('user sans permission manage_teams reçoit 403', function () {
    $user = makeTeamUser();

    $this->actingAs($user)
        ->get(route('admin.teams.index'))
        ->assertStatus(403);
});

test('editor sans manage_teams reçoit 403', function () {
    $editor = makeTeamEditor();

    $this->actingAs($editor)
        ->get(route('admin.teams.index'))
        ->assertStatus(403);
});

// ── SECTION 4 : Modèle Team ──────────────────────────────────────────────────

test('Team génère un slug automatiquement', function () {
    $owner = User::factory()->create();
    $team = TeamService::create($owner, ['name' => 'Mon Équipe Slug']);

    expect($team->slug)->toBe('mon-equipe-slug');
});

test('Team hasMember retourne true/false correctement', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $team = TeamService::create($owner, ['name' => 'Équipe HasMember']);

    expect($team->hasMember($owner))->toBeTrue();
    expect($team->hasMember($other))->toBeFalse();
});

test('User switchTeam change le current_team_id', function () {
    $user = User::factory()->create();
    $team = TeamService::create($user, ['name' => 'Équipe Switch']);

    $user->switchTeam($team);

    expect($user->fresh()->current_team_id)->toBe($team->id);
});

test('User belongsToTeam fonctionne', function () {
    $owner = User::factory()->create();
    $stranger = User::factory()->create();
    $team = TeamService::create($owner, ['name' => 'Équipe BelongsTo']);

    expect($owner->belongsToTeam($team))->toBeTrue();
    expect($stranger->belongsToTeam($team))->toBeFalse();
});

// ── SECTION 5 : Invitations ──────────────────────────────────────────────────

test('invitation expirée retourne isExpired true', function () {
    $owner = User::factory()->create();
    $team = TeamService::create($owner, ['name' => 'Équipe Expiry']);

    $invitation = TeamInvitation::create([
        'team_id' => $team->id,
        'email' => 'expired@example.com',
        'role' => 'member',
        'invited_by' => $owner->id,
        'expires_at' => now()->subDay(),
    ]);

    expect($invitation->isExpired())->toBeTrue();
    expect($invitation->isPending())->toBeFalse();
});

test('user peut accepter une invitation via la route', function () {
    $owner = User::factory()->create();
    $newMember = User::factory()->create(['email' => 'joignez@example.com']);
    $team = TeamService::create($owner, ['name' => 'Équipe Join']);

    $invitation = TeamService::invite($team, 'joignez@example.com', 'member', $owner);

    $this->actingAs($newMember)
        ->get(route('teams.invitations.accept', $invitation->token))
        ->assertRedirect(route('user.dashboard'))
        ->assertSessionHas('success');

    expect($team->fresh()->hasMember($newMember))->toBeTrue();
});
