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
use Modules\Blog\Models\Category;
use Modules\Blog\Models\Comment;
use Modules\Newsletter\Models\Campaign;
use Modules\Pages\Models\StaticPage;
use Modules\SaaS\Models\Plan;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('super_admin');
});

test('admin can export articles CSV', function () {
    Article::factory()->count(2)->create();
    $response = $this->actingAs($this->admin)->get(route('admin.export.articles'));
    $response->assertStatus(200);
    $response->assertDownload('articles_export.csv');
});

test('admin can export categories CSV', function () {
    Category::factory()->count(2)->create();
    $response = $this->actingAs($this->admin)->get(route('admin.export.categories'));
    $response->assertStatus(200);
    $response->assertDownload('categories_export.csv');
});

test('admin can export plans CSV', function () {
    Plan::factory()->count(2)->create();
    $response = $this->actingAs($this->admin)->get(route('admin.export.plans'));
    $response->assertStatus(200);
    $response->assertDownload('plans_export.csv');
});

test('admin can export campaigns CSV', function () {
    Campaign::factory()->count(2)->create();
    $response = $this->actingAs($this->admin)->get(route('admin.export.campaigns'));
    $response->assertStatus(200);
    $response->assertDownload('campaigns_export.csv');
});

test('admin can export pages CSV', function () {
    StaticPage::factory()->count(2)->create();
    $response = $this->actingAs($this->admin)->get(route('admin.export.pages'));
    $response->assertStatus(200);
    $response->assertDownload('pages_export.csv');
});

test('admin can export comments CSV', function () {
    $article = Article::factory()->create();
    Comment::factory()->count(2)->create(['article_id' => $article->id]);
    $response = $this->actingAs($this->admin)->get(route('admin.export.comments'));
    $response->assertStatus(200);
    $response->assertDownload('comments_export.csv');
});

test('non-admin cannot export articles', function () {
    $user = User::factory()->create();
    $user->assignRole('user');
    $response = $this->actingAs($user)->get(route('admin.export.articles'));
    $response->assertStatus(403);
});

test('guest cannot export articles', function () {
    $response = $this->get(route('admin.export.articles'));
    $response->assertRedirect(route('login'));
});

test('non-admin cannot export plans', function () {
    $user = User::factory()->create();
    $user->assignRole('user');
    $response = $this->actingAs($user)->get(route('admin.export.plans'));
    $response->assertStatus(403);
});

test('empty articles export returns valid CSV', function () {
    $response = $this->actingAs($this->admin)->get(route('admin.export.articles'));
    $response->assertStatus(200);
    $response->assertDownload('articles_export.csv');
});

test('empty plans export returns valid CSV', function () {
    $response = $this->actingAs($this->admin)->get(route('admin.export.plans'));
    $response->assertStatus(200);
    $response->assertDownload('plans_export.csv');
});

test('all six export routes are registered', function () {
    $routes = collect(app('router')->getRoutes()->getRoutesByName());
    expect($routes->has('admin.export.articles'))->toBeTrue();
    expect($routes->has('admin.export.categories'))->toBeTrue();
    expect($routes->has('admin.export.plans'))->toBeTrue();
    expect($routes->has('admin.export.campaigns'))->toBeTrue();
    expect($routes->has('admin.export.pages'))->toBeTrue();
    expect($routes->has('admin.export.comments'))->toBeTrue();
});
