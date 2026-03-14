<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
    $this->user = User::factory()->create();
});

it('admin sees import/users page', function () {
    $this->actingAs($this->admin)->get(route('admin.import.users'))->assertOk();
});

it('admin sees import/articles page', function () {
    $this->actingAs($this->admin)->get(route('admin.import.articles'))->assertOk();
});

it('admin sees import/categories page', function () {
    $this->actingAs($this->admin)->get(route('admin.import.categories'))->assertOk();
});

it('admin sees import/subscribers page', function () {
    $this->actingAs($this->admin)->get(route('admin.import.subscribers'))->assertOk();
});

it('guest is redirected from import/articles', function () {
    $this->get(route('admin.import.articles'))->assertRedirect();
});

it('non-admin receives 403 on import/articles', function () {
    $this->actingAs($this->user)->get(route('admin.import.articles'))->assertStatus(403);
});

it('importArticles via CSV creates an article in database', function () {
    $csv = "title,content,status,category_name\nMon Article,Contenu,draft,\n";
    $file = UploadedFile::fake()->createWithContent('articles.csv', $csv);
    $this->actingAs($this->admin)
        ->post(route('admin.import.articles.store'), ['file' => $file])
        ->assertRedirect();
    $this->assertDatabaseHas('articles', ['status' => 'draft']);
    expect(\Modules\Blog\Models\Article::where('title->'.app()->getLocale(), 'Mon Article')->exists())->toBeTrue();
});

it('importCategories via CSV creates a category in database', function () {
    $csv = "name,description,color\nMa Categorie,Desc,#ff0000\n";
    $file = UploadedFile::fake()->createWithContent('categories.csv', $csv);
    $this->actingAs($this->admin)
        ->post(route('admin.import.categories.store'), ['file' => $file])
        ->assertRedirect();
    expect(\Modules\Blog\Models\Category::where('name->'.app()->getLocale(), 'Ma Categorie')->exists())->toBeTrue();
});

it('importSubscribers via CSV creates a subscriber in database', function () {
    $csv = "email,name\ntest@example.com,Test User\n";
    $file = UploadedFile::fake()->createWithContent('subscribers.csv', $csv);
    $this->actingAs($this->admin)
        ->post(route('admin.import.subscribers.store'), ['file' => $file])
        ->assertRedirect();
    $this->assertDatabaseHas('newsletter_subscribers', ['email' => 'test@example.com']);
});

it('validation requires file on import/articles', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.import.articles.store'))
        ->assertSessionHasErrors('file');
});
