<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Blog\Models\Article;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
    $this->user = User::factory()->create();
});

it('la page articles retourne 200 pour un admin', function () {
    $this->actingAs($this->admin)->get('/admin/blog/articles')->assertStatus(200);
});

it('les invites sont rediriges vers login', function () {
    $this->get('/admin/blog/articles')->assertRedirect('/login');
});

it('les non admin obtiennent 403', function () {
    $this->actingAs($this->user)->get('/admin/blog/articles')->assertStatus(403);
});

it('la page affiche le bouton reinitialiser', function () {
    $this->actingAs($this->admin)->get('/admin/blog/articles')
        ->assertSee('Réinitialiser');
});

it('la page affiche le bouton nouvel article', function () {
    $this->actingAs($this->admin)->get('/admin/blog/articles')
        ->assertSee('Nouvel article');
});

it('un article cree apparait dans la liste', function () {
    Article::factory()->create(['title' => 'Mon super article']);

    $this->actingAs($this->admin)->get('/admin/blog/articles')
        ->assertSee('Mon super article');
});

it('le filtre search retourne le bon article', function () {
    Article::factory()->create(['title' => 'Article rouge']);
    Article::factory()->create(['title' => 'Article bleu']);

    $this->actingAs($this->admin)->get('/admin/blog/articles?search=rouge')
        ->assertSee('Article rouge')
        ->assertDontSee('Article bleu');
});

it('le filtre filterStatus fonctionne', function () {
    Article::factory()->create(['title' => 'Article publié', 'status' => 'published']);
    Article::factory()->create(['title' => 'Article brouillon', 'status' => 'draft']);

    $this->actingAs($this->admin)->get('/admin/blog/articles?filterStatus=published')
        ->assertSee('Article publié')
        ->assertDontSee('Article brouillon');
});

it('la page affiche aucun article quand vide', function () {
    Article::query()->delete();

    $this->actingAs($this->admin)->get('/admin/blog/articles')
        ->assertSee('Aucun article');
});

it('la page affiche le total entrees', function () {
    Article::factory()->create(['title' => 'Test entrée']);

    $this->actingAs($this->admin)->get('/admin/blog/articles')
        ->assertSee('entrée');
});
