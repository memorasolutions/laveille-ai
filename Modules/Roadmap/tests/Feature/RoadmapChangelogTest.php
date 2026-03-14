<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Modules\Roadmap\Models\Idea;
use Modules\Roadmap\Notifications\IdeaStatusChanged;
use Modules\Roadmap\Services\IdeaService;
use Modules\RolesPermissions\Database\Seeders\RolesAndPermissionsSeeder;

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
});

test('status change creates changelog entry', function () {
    $idea = Idea::factory()->create(['status' => 'under_review']);

    $this->actingAs($this->admin);

    app(IdeaService::class)->updateStatus($idea, 'planned');

    $this->assertDatabaseHas('roadmap_changelogs', [
        'idea_id' => $idea->id,
        'field' => 'status',
        'old_value' => 'under_review',
        'new_value' => 'planned',
    ]);
});

test('status change sends notification to idea author', function () {
    Notification::fake();

    $author = User::factory()->create();
    $idea = Idea::factory()->create(['user_id' => $author->id, 'status' => 'under_review']);

    $this->actingAs($this->admin);

    app(IdeaService::class)->updateStatus($idea, 'planned');

    Notification::assertSentTo($author, IdeaStatusChanged::class);
});

test('no notification if idea has no author', function () {
    Notification::fake();

    $idea = Idea::factory()->create(['user_id' => null, 'status' => 'under_review']);

    $this->actingAs($this->admin);

    app(IdeaService::class)->updateStatus($idea, 'planned');

    Notification::assertNothingSent();
});

test('merge creates changelog entry', function () {
    $source = Idea::factory()->create();
    $target = Idea::factory()->create();

    $this->actingAs($this->admin);

    app(IdeaService::class)->merge($source, $target);

    $this->assertDatabaseHas('roadmap_changelogs', [
        'idea_id' => $source->id,
        'field' => 'merged',
        'new_value' => (string) $target->id,
    ]);
});

test('changelog relation works on idea', function () {
    $idea = Idea::factory()->create(['status' => 'under_review']);

    $this->actingAs($this->admin);

    app(IdeaService::class)->updateStatus($idea, 'planned');

    expect($idea->changelogs()->count())->toBe(1);
    expect($idea->changelogs->first()->field)->toBe('status');
});
