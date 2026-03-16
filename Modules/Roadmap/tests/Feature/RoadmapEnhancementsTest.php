<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use App\Models\User;
use Modules\Roadmap\Enums\IdeaStatus;
use Modules\Roadmap\Models\Board;
use Modules\Roadmap\Models\Idea;
use Modules\Roadmap\Models\IdeaComment;
use Modules\Roadmap\Models\RoadmapCategory;
use Modules\RolesPermissions\Database\Seeders\RolesAndPermissionsSeeder;

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
});

test('RoadmapCategory factory creates with auto slug', function () {
    $category = RoadmapCategory::factory()->create(['name' => 'New Feature', 'slug' => '']);
    expect($category->slug)->toBe('new-feature');
});

test('Idea can belong to RoadmapCategory', function () {
    $category = RoadmapCategory::factory()->create();
    $idea = Idea::factory()->create(['category_id' => $category->id]);
    expect($idea->roadmapCategory->id)->toBe($category->id);
});

test('Board hide_votes_before_voting casts to boolean', function () {
    $board = Board::factory()->create(['hide_votes_before_voting' => true]);
    expect($board->hide_votes_before_voting)->toBeTrue();

    $board->update(['hide_votes_before_voting' => false]);
    expect($board->fresh()->hide_votes_before_voting)->toBeFalse();
});

test('IdeaComment scopePublic excludes internal comments', function () {
    $idea = Idea::factory()->create();
    IdeaComment::factory()->create(['idea_id' => $idea->id, 'is_internal' => false]);
    IdeaComment::factory()->create(['idea_id' => $idea->id, 'is_internal' => true]);

    expect(IdeaComment::query()->public()->count())->toBe(1);
});

test('admin can add internal note', function () {
    $idea = Idea::factory()->create();

    $this->actingAs($this->admin)
        ->post(route('admin.roadmap.ideas.official-comment', $idea), [
            'content' => 'Internal admin note',
            'is_internal' => 1,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('idea_comments', [
        'idea_id' => $idea->id,
        'content' => 'Internal admin note',
        'is_internal' => true,
        'is_official' => true,
    ]);
});

test('internal comment does not increment comment_count', function () {
    $idea = Idea::factory()->create(['comment_count' => 5]);

    $this->actingAs($this->admin)
        ->post(route('admin.roadmap.ideas.official-comment', $idea), [
            'content' => 'Secret note',
            'is_internal' => 1,
        ]);

    expect($idea->fresh()->comment_count)->toBe(5);
});

test('IdeaStatus column method returns valid board column', function () {
    expect(IdeaStatus::Planned->column())->toBe('next');
    expect(IdeaStatus::InProgress->column())->toBe('now');
    expect(IdeaStatus::Completed->column())->toBe('later');
});

test('RoadmapCategory ordered scope sorts by sort_order', function () {
    $cat3 = RoadmapCategory::factory()->create(['sort_order' => 3]);
    $cat1 = RoadmapCategory::factory()->create(['sort_order' => 1]);
    $cat2 = RoadmapCategory::factory()->create(['sort_order' => 2]);

    $ordered = RoadmapCategory::ordered()->pluck('id')->all();
    expect($ordered)->toBe([$cat1->id, $cat2->id, $cat3->id]);
});
