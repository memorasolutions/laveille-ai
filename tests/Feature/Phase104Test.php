<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Blog\Models\Category;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
    $this->user = User::factory()->create();
});

it('la page categories retourne 200 pour un admin', function () {
    $this->actingAs($this->admin)->get('/admin/blog/categories')->assertStatus(200);
});

it('les invites sont rediriges vers login', function () {
    $this->get('/admin/blog/categories')->assertRedirect('/login');
});

it('les non admin obtiennent 403', function () {
    $this->actingAs($this->user)->get('/admin/blog/categories')->assertStatus(403);
});

it('la page affiche le bouton reinitialiser', function () {
    $this->actingAs($this->admin)->get('/admin/blog/categories')
        ->assertSee('Réinitialiser');
});

it('la page affiche le bouton nouvelle categorie', function () {
    $this->actingAs($this->admin)->get('/admin/blog/categories')
        ->assertSee('Nouvelle catégorie');
});

it('la page affiche aucune categorie quand vide', function () {
    Category::query()->delete();

    $this->actingAs($this->admin)->get('/admin/blog/categories')
        ->assertSee('Aucune catégorie');
});

it('une categorie creee apparait dans la liste', function () {
    Category::factory()->create(['name' => 'Technologie']);

    $this->actingAs($this->admin)->get('/admin/blog/categories')
        ->assertSee('Technologie');
});

it('le filtre search retourne la bonne categorie', function () {
    Category::factory()->create(['name' => 'Marketing digital']);
    Category::factory()->create(['name' => 'Technologie avancee']);

    $this->actingAs($this->admin)->get('/admin/blog/categories?search=Marketing')
        ->assertSee('Marketing digital')
        ->assertDontSee('Technologie avancee');
});

it('le filtre filterActive fonctionne', function () {
    Category::factory()->create(['name' => 'Categorie active', 'is_active' => true]);
    Category::factory()->create(['name' => 'Categorie inactive', 'is_active' => false]);

    $this->actingAs($this->admin)->get('/admin/blog/categories?filterActive=1')
        ->assertSee('Categorie active')
        ->assertDontSee('Categorie inactive');
});

it('la page affiche le total categories', function () {
    Category::factory()->create(['name' => 'Test total']);

    $this->actingAs($this->admin)->get('/admin/blog/categories')
        ->assertSee('catégorie');
});
