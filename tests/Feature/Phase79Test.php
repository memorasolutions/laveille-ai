<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Blog\Models\Article;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Modules\RolesPermissions\Database\Seeders\RolesAndPermissionsSeeder::class);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('super_admin');
    $this->actingAs($this->admin);
});

it('article create page loads', function () {
    $this->get(route('admin.blog.articles.create'))->assertStatus(200);
});

it('stores article with tags from tags_input', function () {
    $this->post(route('admin.blog.articles.store'), [
        'title' => 'Article avec tags',
        'tags_input' => 'laravel, php, vue',
        'status' => 'draft',
    ])->assertRedirect(route('admin.blog.articles.index'));

    $article = Article::where('title->'.app()->getLocale(), 'Article avec tags')->first();
    expect($article)->not->toBeNull();
    expect($article->tags)->toBe(['laravel', 'php', 'vue']);
});

it('stores article with empty tags_input saves empty array', function () {
    $this->post(route('admin.blog.articles.store'), [
        'title' => 'Article sans tags',
        'tags_input' => '',
        'status' => 'draft',
    ])->assertRedirect(route('admin.blog.articles.index'));

    $article = Article::where('title->'.app()->getLocale(), 'Article sans tags')->first();
    expect($article->tags)->toBe([]);
});

it('updates article with new tags', function () {
    $article = Article::factory()->create(['user_id' => $this->admin->id, 'tags' => ['old']]);

    $this->put(route('admin.blog.articles.update', $article), [
        'title' => $article->title,
        'tags_input' => 'new-tag, another',
        'status' => $article->status,
    ])->assertRedirect(route('admin.blog.articles.index'));

    expect($article->fresh()->tags)->toBe(['new-tag', 'another']);
});

it('article edit page loads with existing tags', function () {
    $article = Article::factory()->create([
        'user_id' => $this->admin->id,
        'tags' => ['laravel', 'php'],
    ]);

    $this->get(route('admin.blog.articles.edit', $article))
        ->assertStatus(200)
        ->assertSee('laravel');
});

it('tags_input trims whitespace from tags', function () {
    $this->post(route('admin.blog.articles.store'), [
        'title' => 'Trim test',
        'tags_input' => '  laravel  ,  php  ,  ',
        'status' => 'draft',
    ]);

    $article = Article::where('title->'.app()->getLocale(), 'Trim test')->first();
    expect($article->tags)->toBe(['laravel', 'php']);
});
