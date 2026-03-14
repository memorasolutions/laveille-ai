<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

use App\Models\User;
use Illuminate\Support\Facades\Route;
use Modules\Blog\Models\Article;
use Modules\Blog\Models\Category;
use Modules\RolesPermissions\Database\Seeders\RolesAndPermissionsSeeder;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('super_admin');

    $this->category = Category::create([
        'name' => 'Test Category',
        'color' => '#FF0000',
        'is_active' => true,
    ]);
});

test('article create page loads with categories dropdown', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.blog.articles.create'))
        ->assertOk()
        ->assertSee('Test Category');
});

test('article create page includes Tom-Select CDN', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.blog.articles.create'))
        ->assertOk()
        ->assertSee('tom-select.bootstrap5.min.css', false)
        ->assertSee('tom-select.complete.min.js', false);
});

test('article edit page loads with categories dropdown and selected category', function () {
    $article = Article::factory()->create([
        'user_id' => $this->admin->id,
        'category_id' => $this->category->id,
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.blog.articles.edit', $article))
        ->assertOk()
        ->assertSee('Test Category')
        ->assertSee('selected', false);
});

test('article edit page includes Tom-Select CDN', function () {
    $article = Article::factory()->create([
        'user_id' => $this->admin->id,
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.blog.articles.edit', $article))
        ->assertOk()
        ->assertSee('tom-select.bootstrap5.min.css', false)
        ->assertSee('tom-select.complete.min.js', false);
});

test('store article with category_id saves correctly', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.blog.articles.store'), [
            'title' => 'Test Article',
            'content' => 'Test content',
            'category_id' => $this->category->id,
        ])
        ->assertRedirect();

    $article = Article::latest()->first();
    expect($article->category_id)->toBe($this->category->id);
});

test('store article with tags_input saves tags array', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.blog.articles.store'), [
            'title' => 'Test Article Tags',
            'content' => 'Test content',
            'tags_input' => 'laravel,php,testing',
        ])
        ->assertRedirect();

    $article = Article::latest()->first();
    expect($article->tags)->toBe(['laravel', 'php', 'testing']);
});

test('update article with new category_id', function () {
    $article = Article::factory()->create([
        'user_id' => $this->admin->id,
        'category_id' => null,
    ]);

    $newCategory = Category::create([
        'name' => 'New Category',
        'color' => '#00FF00',
        'is_active' => true,
    ]);

    $this->actingAs($this->admin)
        ->put(route('admin.blog.articles.update', $article), [
            'title' => 'Updated Article',
            'content' => 'Updated content',
            'category_id' => $newCategory->id,
        ])
        ->assertRedirect();

    expect($article->fresh()->category_id)->toBe($newCategory->id);
});

test('update article with tags_input updates tags', function () {
    $article = Article::factory()->create([
        'user_id' => $this->admin->id,
        'tags' => ['old', 'tags'],
    ]);

    $this->actingAs($this->admin)
        ->put(route('admin.blog.articles.update', $article), [
            'title' => 'Updated Article',
            'content' => 'Updated content',
            'tags_input' => 'new,updated,tags',
        ])
        ->assertRedirect();

    expect($article->fresh()->tags)->toBe(['new', 'updated', 'tags']);
});

test('quickCreate returns JSON with id and name', function () {
    $this->actingAs($this->admin)
        ->postJson(route('admin.blog.categories.quick-create'), [
            'name' => 'Quick Category',
        ])
        ->assertOk()
        ->assertJsonStructure(['id', 'name'])
        ->assertJsonFragment(['name' => 'Quick Category']);
});

test('quickCreate creates category with default color and active status', function () {
    $this->actingAs($this->admin)
        ->postJson(route('admin.blog.categories.quick-create'), [
            'name' => 'New Quick Cat',
        ])
        ->assertOk();

    $cat = Category::where('color', '#6366f1')->latest()->first();
    expect($cat)->not->toBeNull();
    expect($cat->is_active)->toBeTrue();
});

test('quickCreate requires name', function () {
    $this->actingAs($this->admin)
        ->postJson(route('admin.blog.categories.quick-create'), [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name']);
});

test('articles table shows category name via relationship', function () {
    $article = Article::factory()->create([
        'user_id' => $this->admin->id,
        'category_id' => $this->category->id,
    ]);

    expect($article->blogCategory)->toBeInstanceOf(Category::class);
    expect($article->blogCategory->name)->toBe('Test Category');
});

test('articles table filters by category_id', function () {
    $article1 = Article::factory()->create([
        'user_id' => $this->admin->id,
        'category_id' => $this->category->id,
    ]);

    $article2 = Article::factory()->create([
        'user_id' => $this->admin->id,
        'category_id' => null,
    ]);

    expect($article1->blogCategory->id)->toBe($this->category->id);
    expect($article2->blogCategory)->toBeNull();
});

test('route admin.blog.categories.quick-create exists', function () {
    expect(Route::has('admin.blog.categories.quick-create'))->toBeTrue();

    $route = Route::getRoutes()->getByName('admin.blog.categories.quick-create');
    expect($route)->not->toBeNull();
    expect($route->methods())->toContain('POST');
});

test('store article without category_id is valid', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.blog.articles.store'), [
            'title' => 'No Category Article',
            'content' => 'Content here',
        ])
        ->assertRedirect();

    $article = Article::latest()->first();
    expect($article->category_id)->toBeNull();
});
