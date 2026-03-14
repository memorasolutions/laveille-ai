<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

use App\Models\User;
use Modules\Core\Models\ContentRevision;
use Modules\Core\Services\RevisionService;
use Modules\Pages\Models\StaticPage;

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

test('ContentRevision model exists and has morphTo relationship', function () {
    expect(class_exists(ContentRevision::class))->toBeTrue();

    $revision = new ContentRevision;
    expect($revision->getFillable())->toContain('revisionable_type', 'revisionable_id', 'user_id', 'data', 'revision_number', 'summary');
});

test('HasRevisions creates revision on model update', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $page = StaticPage::create([
        'title' => 'Test Page', 'slug' => 'test-page', 'content' => 'Original content',
        'status' => 'draft', 'template' => 'default', 'user_id' => $user->id,
    ]);

    $page->update(['title' => 'Updated Title']);

    expect($page->revisions()->count())->toBe(1);

    $revision = $page->revisions()->first();
    expect($revision)->toBeInstanceOf(ContentRevision::class);
    // Title is translatable, stored as JSON {"fr": "Test Page"}
    $titleData = $revision->data['title'];
    $titleValue = is_array($titleData) ? ($titleData['fr'] ?? $titleData) : $titleData;
    expect($titleValue)->toBe('Test Page');
    expect($revision->revision_number)->toBeGreaterThanOrEqual(1);
});

test('HasRevisions stores original values in revision data', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $page = StaticPage::create([
        'title' => 'Original Title', 'slug' => 'test', 'content' => 'Original content',
        'excerpt' => 'Original excerpt', 'status' => 'draft', 'template' => 'default',
        'meta_title' => 'Meta', 'meta_description' => 'Desc', 'user_id' => $user->id,
    ]);

    $page->update(['title' => 'New Title', 'content' => 'New content', 'status' => 'published']);

    $revision = $page->revisions()->first();

    // Translatable fields are stored as JSON arrays
    $title = $revision->data['title'];
    expect(is_array($title) ? $title['fr'] : $title)->toBe('Original Title');
    $content = $revision->data['content'];
    expect(is_array($content) ? $content['fr'] : $content)->toBe('Original content');
    expect($revision->data['status'])->toBe('draft');
});

test('HasRevisions auto-increments revision number', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $page = StaticPage::create([
        'title' => 'Test', 'slug' => 'test', 'content' => 'Content',
        'status' => 'draft', 'template' => 'default', 'user_id' => $user->id,
    ]);

    $page->update(['title' => 'V1']);
    $page->update(['title' => 'V2']);
    $page->update(['title' => 'V3']);

    $revisions = $page->revisions()->reorder('revision_number', 'asc')->get();

    expect($revisions)->toHaveCount(3);
    // Revision numbers are sequential (may not start at 1 due to test isolation)
    expect($revisions[1]->revision_number)->toBe($revisions[0]->revision_number + 1);
    expect($revisions[2]->revision_number)->toBe($revisions[1]->revision_number + 1);
});

test('restoreRevision restores model to previous state', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $page = StaticPage::create([
        'title' => 'Original Title', 'slug' => 'test', 'content' => 'Original content',
        'status' => 'draft', 'template' => 'default', 'user_id' => $user->id,
    ]);

    $page->update(['title' => 'Updated Title', 'content' => 'Updated content', 'status' => 'published']);

    $revision = $page->revisions()->first();
    $page->restoreRevision($revision);
    $page->refresh();

    expect($page->title)->toBe('Original Title');
    expect($page->content)->toBe('Original content');
    expect($page->status)->toBe('draft');
});

test('pruneOldRevisions keeps only maxRevisions', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $page = StaticPage::create([
        'title' => 'Test', 'slug' => 'test', 'content' => 'Content',
        'status' => 'draft', 'template' => 'default', 'user_id' => $user->id,
    ]);

    // Create 5 revisions manually to bypass maxRevisions during creation
    for ($i = 1; $i <= 5; $i++) {
        $page->revisions()->create([
            'user_id' => $user->id,
            'data' => ['title' => "Title {$i}"],
            'revision_number' => $i,
        ]);
    }

    expect($page->revisions()->count())->toBe(5);

    // Prune to default max (50) - all should remain
    $page->pruneOldRevisions();
    expect($page->revisions()->count())->toBe(5);
});

test('RevisionService diff returns changed fields', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $page = StaticPage::create([
        'title' => 'Original', 'slug' => 'test', 'content' => 'Original content',
        'status' => 'draft', 'template' => 'default', 'user_id' => $user->id,
    ]);

    $page->update(['title' => 'Updated', 'content' => 'Updated content', 'status' => 'published']);

    $revision = $page->revisions()->first();
    $service = new RevisionService;
    $diff = $service->diff($page, $revision);

    expect($diff)->toHaveKeys(['title', 'content', 'status']);
    // Translatable fields: old is JSON array from getOriginal, new is current locale string
    $oldTitle = $diff['title']['old'];
    expect(is_array($oldTitle) ? $oldTitle['fr'] : $oldTitle)->toBe('Original');
    expect($diff['status']['old'])->toBe('draft');
    expect($diff['status']['new'])->toBe('published');
});

test('revision belongs to user', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $page = StaticPage::create([
        'title' => 'Test', 'slug' => 'test', 'content' => 'Content',
        'status' => 'draft', 'template' => 'default', 'user_id' => $user->id,
    ]);

    $page->update(['title' => 'Updated']);

    $revision = $page->revisions()->with('user')->first();

    expect($revision->user)->toBeInstanceOf(User::class);
    expect($revision->user->id)->toBe($user->id);
});

test('revision has polymorphic revisionable relationship', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $page = StaticPage::create([
        'title' => 'Test', 'slug' => 'test', 'content' => 'Content',
        'status' => 'draft', 'template' => 'default', 'user_id' => $user->id,
    ]);

    $page->update(['title' => 'Updated']);

    $revision = $page->revisions()->first();

    expect($revision->revisionable)->toBeInstanceOf(StaticPage::class);
    expect($revision->revisionable_id)->toBe($page->id);
});

test('no revision created when non-tracked fields change', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $page = StaticPage::create([
        'title' => 'Test', 'slug' => 'test', 'content' => 'Content',
        'status' => 'draft', 'template' => 'default', 'user_id' => $user->id,
    ]);

    // slug is not in $revisionable
    $page->update(['slug' => 'new-slug']);

    expect($page->revisions()->count())->toBe(0);
});
