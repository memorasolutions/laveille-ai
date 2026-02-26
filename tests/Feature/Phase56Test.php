<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Modules\Blog\Models\Category;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
});

function makeAdmin56(): User
{
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    return $admin;
}

it('table blog_categories existe', function () {
    expect(Schema::hasTable('blog_categories'))->toBeTrue();
});

it('model Category se crée et auto-slug', function () {
    $category = Category::create([
        'name' => 'Tech Avancée',
        'color' => '#6366f1',
        'is_active' => true,
    ]);

    expect($category->name)->toBe('Tech Avancée')
        ->and($category->slug)->toBe('tech-avancee');
});

it('admin categories index retourne 200', function () {
    $admin = makeAdmin56();

    $this->actingAs($admin)
        ->get('/admin/blog/categories')
        ->assertOk();
});

it('admin peut créer une catégorie', function () {
    $admin = makeAdmin56();

    $this->actingAs($admin)
        ->post('/admin/blog/categories', [
            'name' => 'Nouvelle Catégorie',
            'color' => '#6366f1',
            'is_active' => '1',
        ])
        ->assertRedirect();

    expect(Category::where('name->'.app()->getLocale(), 'Nouvelle Catégorie')->exists())->toBeTrue();
});

it('admin peut modifier une catégorie', function () {
    $admin = makeAdmin56();
    $category = Category::factory()->create();

    $this->actingAs($admin)
        ->put("/admin/blog/categories/{$category->slug}", [
            'name' => 'Nom Modifié',
            'color' => '#10b981',
        ])
        ->assertRedirect();

    expect(Category::where('id', $category->id)->where('name->'.app()->getLocale(), 'Nom Modifié')->exists())->toBeTrue();
});

it('admin peut supprimer une catégorie', function () {
    $admin = makeAdmin56();
    $category = Category::factory()->create();

    $this->actingAs($admin)
        ->delete("/admin/blog/categories/{$category->slug}")
        ->assertRedirect();

    $this->assertSoftDeleted('blog_categories', ['id' => $category->id]);
});

it('scope active filtre les catégories inactives', function () {
    Category::factory()->create(['is_active' => true]);
    Category::factory()->create(['is_active' => false]);

    expect(Category::active()->count())->toBe(1);
});
