<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Pages\Models\StaticPage;

uses(RefreshDatabase::class);

// --- Page hierarchy ---

it('static page can have parent', function () {
    $parent = StaticPage::factory()->create(['title' => 'Parent', 'status' => 'published']);
    $child = StaticPage::factory()->create(['title' => 'Enfant', 'status' => 'published', 'parent_id' => $parent->id]);

    expect($child->parent->id)->toBe($parent->id);
});

it('static page children ordered by sort_order', function () {
    $parent = StaticPage::factory()->create(['title' => 'Parent', 'status' => 'published']);

    StaticPage::factory()->create(['title' => 'C3', 'status' => 'published', 'parent_id' => $parent->id, 'sort_order' => 3]);
    StaticPage::factory()->create(['title' => 'C1', 'status' => 'published', 'parent_id' => $parent->id, 'sort_order' => 1]);
    StaticPage::factory()->create(['title' => 'C2', 'status' => 'published', 'parent_id' => $parent->id, 'sort_order' => 2]);

    expect($parent->fresh()->children->pluck('sort_order')->all())->toBe([1, 2, 3]);
});

it('static page depth returns correct level', function () {
    $root = StaticPage::factory()->create(['title' => 'Racine', 'status' => 'published']);
    $child = StaticPage::factory()->create(['title' => 'Enfant', 'status' => 'published', 'parent_id' => $root->id]);
    $grandchild = StaticPage::factory()->create(['title' => 'Petit-enfant', 'status' => 'published', 'parent_id' => $child->id]);

    expect($root->depth())->toBe(0)
        ->and($child->depth())->toBe(1)
        ->and($grandchild->depth())->toBe(2);
});

it('static page breadcrumb returns ancestors plus self', function () {
    $root = StaticPage::factory()->create(['title' => 'Racine', 'status' => 'published']);
    $child = StaticPage::factory()->create(['title' => 'Enfant', 'status' => 'published', 'parent_id' => $root->id]);
    $grandchild = StaticPage::factory()->create(['title' => 'Petit-enfant', 'status' => 'published', 'parent_id' => $child->id]);

    $breadcrumb = $grandchild->breadcrumb();

    expect($breadcrumb)->toHaveCount(3)
        ->and($breadcrumb[0]->id)->toBe($root->id);
});

it('static page scopeRoots returns only root pages', function () {
    $root = StaticPage::factory()->create(['title' => 'Racine', 'status' => 'published']);
    StaticPage::factory()->create(['title' => 'Enfant', 'status' => 'published', 'parent_id' => $root->id]);

    expect(StaticPage::roots()->count())->toBe(1);
});

// --- User social links ---

it('user social_links cast to array', function () {
    $user = User::factory()->create(['social_links' => ['twitter' => 'https://x.com/test']]);

    expect($user->social_links)->toBeArray()
        ->and($user->social_links)->toHaveKey('twitter');
});

it('profile update saves social links', function () {
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    $this->actingAs($user)
        ->put(route('admin.profile.update'), [
            '_section' => 'social',
            'social_links' => [
                'twitter' => 'https://x.com/test',
                'linkedin' => 'https://linkedin.com/in/test',
            ],
        ])
        ->assertRedirect();

    $user->refresh();
    expect($user->social_links)->toHaveKey('twitter')
        ->and($user->social_links['twitter'])->toBe('https://x.com/test');
});

// --- Permalink settings ---

it('permalink settings exist in database', function () {
    $this->assertDatabaseHas('settings', ['key' => 'permalinks.blog_prefix']);
    $this->assertDatabaseHas('settings', ['key' => 'permalinks.trailing_slash']);
});
