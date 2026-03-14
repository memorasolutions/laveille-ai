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
use Modules\Roadmap\Models\Vote;
use Modules\Roadmap\Services\RoadmapAiService;
use Modules\RolesPermissions\Database\Seeders\RolesAndPermissionsSeeder;

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
});

test('admin can access analytics page', function () {
    Board::factory()->create();
    Idea::factory()->count(5)->create();

    $this->actingAs($this->admin)
        ->get(route('admin.roadmap.analytics'))
        ->assertOk()
        ->assertSee('Statistiques Roadmap');
});

test('analytics shows correct total ideas', function () {
    Idea::factory()->count(7)->create();

    $this->actingAs($this->admin)
        ->get(route('admin.roadmap.analytics'))
        ->assertOk()
        ->assertSee('7');
});

test('analytics shows correct total votes', function () {
    $idea = Idea::factory()->create();
    Vote::factory()->count(4)->create(['idea_id' => $idea->id]);

    $this->actingAs($this->admin)
        ->get(route('admin.roadmap.analytics'))
        ->assertOk()
        ->assertSee('4');
});

test('RoadmapAiService categorize returns null without AI module', function () {
    $service = new RoadmapAiService;
    $result = $service->categorize('Fix login bug', 'Login page crashes');

    expect($result)->toBeNull();
});

test('RoadmapAiService detectDuplicates finds similar ideas', function () {
    $board = Board::factory()->create();
    Idea::factory()->create(['board_id' => $board->id, 'title' => 'Dark mode support']);
    Idea::factory()->create(['board_id' => $board->id, 'title' => 'Dark theme option']);

    $service = new RoadmapAiService;
    $results = $service->detectDuplicates('Dark mode toggle', $board->id);

    expect($results)->toHaveCount(2);
});

test('RoadmapAiService detectDuplicates returns empty for short words', function () {
    $board = Board::factory()->create();

    $service = new RoadmapAiService;
    $results = $service->detectDuplicates('Hi', $board->id);

    expect($results)->toBeEmpty();
});

test('guest cannot access analytics', function () {
    $this->get(route('admin.roadmap.analytics'))->assertRedirect();
});
