<?php

declare(strict_types=1);

use App\Models\User;
use Modules\Core\Models\Announcement;

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

// ── Model tests ──

test('Announcement model can be created', function () {
    $a = Announcement::create([
        'title' => 'New feature',
        'body' => 'We added dark mode.',
        'type' => 'feature',
        'version' => '1.0.0',
        'is_published' => true,
        'published_at' => now(),
    ]);

    expect($a)->toBeInstanceOf(Announcement::class)
        ->and($a->title)->toBe('New feature')
        ->and($a->type)->toBe('feature')
        ->and($a->is_published)->toBeTrue();
});

test('typeLabel returns correct French labels', function () {
    $feature = new Announcement(['type' => 'feature']);
    $fix = new Announcement(['type' => 'fix']);
    $improvement = new Announcement(['type' => 'improvement']);
    $announcement = new Announcement(['type' => 'announcement']);

    expect($feature->typeLabel())->toBe('Nouveaute')
        ->and($fix->typeLabel())->toBe('Correctif')
        ->and($improvement->typeLabel())->toBe('Amelioration')
        ->and($announcement->typeLabel())->toBe('Annonce');
});

test('typeBadgeClass returns correct CSS classes', function () {
    $feature = new Announcement(['type' => 'feature']);
    $fix = new Announcement(['type' => 'fix']);

    expect($feature->typeBadgeClass())->toBe('bg-success')
        ->and($fix->typeBadgeClass())->toBe('bg-warning text-dark');
});

test('scopePublished filters correctly', function () {
    Announcement::create(['title' => 'Published', 'body' => 'b', 'type' => 'feature', 'is_published' => true, 'published_at' => now()->subDay()]);
    Announcement::create(['title' => 'Draft', 'body' => 'b', 'type' => 'fix', 'is_published' => false]);
    Announcement::create(['title' => 'Future', 'body' => 'b', 'type' => 'fix', 'is_published' => true, 'published_at' => now()->addDay()]);

    $published = Announcement::published()->get();

    expect($published)->toHaveCount(1)
        ->and($published->first()->title)->toBe('Published');
});

test('scopeByType filters by type', function () {
    Announcement::create(['title' => 'A', 'body' => 'b', 'type' => 'feature', 'is_published' => true]);
    Announcement::create(['title' => 'B', 'body' => 'b', 'type' => 'fix', 'is_published' => true]);

    expect(Announcement::byType('feature')->count())->toBe(1)
        ->and(Announcement::byType('fix')->count())->toBe(1);
});

test('safeBody strips XSS', function () {
    $a = new Announcement(['body' => '<p>Hello</p><script>alert("xss")</script>']);

    expect($a->safeBody())->not->toContain('<script>');
});

// ── Admin CRUD tests ──

function adminUser(): User
{
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    return $user;
}

test('admin can list announcements', function () {
    Announcement::create(['title' => 'Test Annonce', 'body' => 'Body', 'type' => 'feature', 'is_published' => true]);

    $this->actingAs(adminUser())
        ->get(route('admin.announcements.index'))
        ->assertOk()
        ->assertSee('Test Annonce');
});

test('admin can create announcement', function () {
    $this->actingAs(adminUser())
        ->post(route('admin.announcements.store'), [
            'title' => 'New Announcement',
            'body' => 'Content here',
            'type' => 'feature',
            'version' => '2.0.0',
            'is_published' => true,
        ])
        ->assertRedirect(route('admin.announcements.index'));

    $this->assertDatabaseHas('announcements', [
        'title' => 'New Announcement',
        'type' => 'feature',
        'version' => '2.0.0',
        'is_published' => true,
    ]);
});

test('admin can update announcement', function () {
    $a = Announcement::create(['title' => 'Old', 'body' => 'b', 'type' => 'fix', 'is_published' => false]);

    $this->actingAs(adminUser())
        ->put(route('admin.announcements.update', $a), [
            'title' => 'Updated',
            'body' => 'new body',
            'type' => 'improvement',
            'is_published' => true,
        ])
        ->assertRedirect(route('admin.announcements.index'));

    expect($a->fresh()->title)->toBe('Updated')
        ->and($a->fresh()->type)->toBe('improvement')
        ->and($a->fresh()->is_published)->toBeTrue()
        ->and($a->fresh()->published_at)->not->toBeNull();
});

test('admin can delete announcement', function () {
    $a = Announcement::create(['title' => 'To Delete', 'body' => 'b', 'type' => 'fix', 'is_published' => false]);

    $this->actingAs(adminUser())
        ->delete(route('admin.announcements.destroy', $a))
        ->assertRedirect(route('admin.announcements.index'));

    $this->assertDatabaseMissing('announcements', ['id' => $a->id]);
});

test('store validates required fields', function () {
    $this->actingAs(adminUser())
        ->post(route('admin.announcements.store'), [])
        ->assertSessionHasErrors(['title', 'body', 'type']);
});

test('unpublishing clears published_at', function () {
    $a = Announcement::create(['title' => 'Pub', 'body' => 'b', 'type' => 'fix', 'is_published' => true, 'published_at' => now()]);

    $this->actingAs(adminUser())
        ->put(route('admin.announcements.update', $a), [
            'title' => 'Pub',
            'body' => 'b',
            'type' => 'fix',
            'is_published' => false,
        ])
        ->assertRedirect();

    expect($a->fresh()->is_published)->toBeFalse()
        ->and($a->fresh()->published_at)->toBeNull();
});

// ── Public page test ──

test('public changelog route resolves correctly', function () {
    Announcement::create(['title' => 'Visible', 'body' => 'Content', 'type' => 'feature', 'is_published' => true, 'published_at' => now()->subDay()]);
    Announcement::create(['title' => 'Draft Hidden', 'body' => 'Content', 'type' => 'fix', 'is_published' => false]);

    $published = Announcement::published()->get();

    expect($published)->toHaveCount(1)
        ->and($published->first()->title)->toBe('Visible')
        ->and(route('announcements.index'))->toEndWith('/changelog');
});

test('user without permission cannot access admin announcements', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.announcements.index'))
        ->assertForbidden();
});
