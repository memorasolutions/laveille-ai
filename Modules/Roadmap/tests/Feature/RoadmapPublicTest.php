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
    $this->user = User::factory()->create();
    $this->user->assignRole('user');
});

test('guest is redirected from roadmap', function () {
    $this->get(route('roadmap.boards.index'))->assertRedirect();
});

test('user can view public boards', function () {
    $publicBoard = Board::factory()->create(['is_public' => true, 'name' => 'Public Board']);
    Board::factory()->create(['is_public' => false, 'name' => 'Private Board']);

    $this->actingAs($this->user)
        ->get(route('roadmap.boards.index'))
        ->assertOk()
        ->assertSee('Public Board')
        ->assertDontSee('Private Board');
});

test('user can view public board ideas', function () {
    $board = Board::factory()->create(['is_public' => true]);
    Idea::factory()->count(3)->create(['board_id' => $board->id]);

    $this->actingAs($this->user)
        ->get(route('roadmap.boards.show', $board))
        ->assertOk();
});

test('user cannot view private board', function () {
    $board = Board::factory()->create(['is_public' => false]);

    $this->actingAs($this->user)
        ->get(route('roadmap.boards.show', $board))
        ->assertNotFound();
});

test('user can submit idea', function () {
    $board = Board::factory()->create(['is_public' => true]);

    $this->actingAs($this->user)
        ->post(route('roadmap.ideas.store', $board), [
            'title' => 'My Idea',
            'description' => 'Details',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('ideas', ['title' => 'My Idea']);
});

test('user can vote on idea', function () {
    $idea = Idea::factory()->create(['vote_count' => 0]);

    $this->actingAs($this->user)
        ->postJson(route('roadmap.ideas.vote', $idea))
        ->assertOk()
        ->assertJson(['voted' => true, 'vote_count' => 1]);
});

test('user can toggle vote off', function () {
    $idea = Idea::factory()->create(['vote_count' => 0]);

    $this->actingAs($this->user)
        ->postJson(route('roadmap.ideas.vote', $idea));

    $this->actingAs($this->user)
        ->postJson(route('roadmap.ideas.vote', $idea))
        ->assertJson(['voted' => false, 'vote_count' => 0]);
});

test('user can comment on idea', function () {
    $idea = Idea::factory()->create();

    $this->actingAs($this->user)
        ->post(route('roadmap.ideas.comment', $idea), [
            'content' => 'Great idea!',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('idea_comments', [
        'content' => 'Great idea!',
        'is_official' => false,
    ]);
});

test('user can view kanban', function () {
    $board = Board::factory()->create(['is_public' => true]);

    $this->actingAs($this->user)
        ->get(route('roadmap.boards.kanban', $board))
        ->assertOk();
});
