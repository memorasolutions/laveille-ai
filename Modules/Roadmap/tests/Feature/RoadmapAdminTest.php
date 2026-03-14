<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use App\Models\User;
use Modules\Roadmap\Models\Board;
use Modules\Roadmap\Models\Idea;
use Modules\RolesPermissions\Database\Seeders\RolesAndPermissionsSeeder;

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
    $this->user = User::factory()->create();
    $this->user->assignRole('user');
});

// ── Boards ──────────────────────────────────────────────

test('guest cannot access boards index', function () {
    $this->get(route('admin.roadmap.boards.index'))->assertRedirect(route('login'));
});

test('user cannot access boards index', function () {
    $this->actingAs($this->user)
        ->get(route('admin.roadmap.boards.index'))
        ->assertForbidden();
});

test('admin can view boards index', function () {
    Board::factory()->count(3)->create();

    $this->actingAs($this->admin)
        ->get(route('admin.roadmap.boards.index'))
        ->assertOk()
        ->assertViewHas('boards');
});

test('admin can create board', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.roadmap.boards.store'), [
            'name' => 'Test Board',
            'description' => 'Desc',
            'is_public' => true,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('boards', ['name' => 'Test Board']);
});

test('admin can update board', function () {
    $board = Board::factory()->create();

    $this->actingAs($this->admin)
        ->put(route('admin.roadmap.boards.update', $board), [
            'name' => 'Updated',
            'description' => $board->description,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('boards', ['name' => 'Updated']);
});

test('admin can delete board', function () {
    $board = Board::factory()->create();

    $this->actingAs($this->admin)
        ->delete(route('admin.roadmap.boards.destroy', $board))
        ->assertRedirect();

    $this->assertDatabaseMissing('boards', ['id' => $board->id]);
});

// ── Ideas ───────────────────────────────────────────────

test('admin can view ideas index', function () {
    Idea::factory()->count(3)->create();

    $this->actingAs($this->admin)
        ->get(route('admin.roadmap.ideas.index'))
        ->assertOk()
        ->assertViewHas('ideas');
});

test('admin can view idea show', function () {
    $idea = Idea::factory()->create();

    $this->actingAs($this->admin)
        ->get(route('admin.roadmap.ideas.show', $idea))
        ->assertOk()
        ->assertViewHas('idea');
});

test('admin can update idea status', function () {
    $idea = Idea::factory()->create();

    $this->actingAs($this->admin)
        ->patch(route('admin.roadmap.ideas.update-status', $idea), [
            'status' => 'planned',
        ])
        ->assertRedirect();

    $idea->refresh();
    expect($idea->status->value)->toBe('planned');
});

test('admin can delete idea (soft delete)', function () {
    $idea = Idea::factory()->create();

    $this->actingAs($this->admin)
        ->delete(route('admin.roadmap.ideas.destroy', $idea))
        ->assertRedirect();

    $this->assertSoftDeleted('ideas', ['id' => $idea->id]);
});

test('admin can toggle vote via JSON', function () {
    $idea = Idea::factory()->create(['vote_count' => 0]);

    $this->actingAs($this->admin)
        ->postJson(route('admin.roadmap.ideas.vote', $idea))
        ->assertOk()
        ->assertJson(['voted' => true, 'vote_count' => 1]);
});

test('admin can add official comment', function () {
    $idea = Idea::factory()->create();

    $this->actingAs($this->admin)
        ->post(route('admin.roadmap.ideas.official-comment', $idea), [
            'content' => 'Official reply',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('idea_comments', [
        'content' => 'Official reply',
        'is_official' => true,
    ]);
});
