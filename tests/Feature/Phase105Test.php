<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Newsletter\Models\Subscriber;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
    $this->user = User::factory()->create();
});

it('la page newsletter retourne 200 pour un admin', function () {
    $this->actingAs($this->admin)->get('/admin/newsletter')->assertStatus(200);
});

it('les invites sont rediriges vers login', function () {
    $this->get('/admin/newsletter')->assertRedirect('/login');
});

it('les non admin obtiennent 403', function () {
    $this->actingAs($this->user)->get('/admin/newsletter')->assertStatus(403);
});

it('la page affiche le bouton reinitialiser', function () {
    $this->actingAs($this->admin)->get('/admin/newsletter')
        ->assertSee('Réinitialiser');
});

it('la page affiche les stats total inscrits', function () {
    $this->actingAs($this->admin)->get('/admin/newsletter')
        ->assertSee('Total inscrits');
});

it('la page affiche aucun abonne quand vide', function () {
    Subscriber::query()->delete();

    $this->actingAs($this->admin)->get('/admin/newsletter')
        ->assertSee('Aucun abonné');
});

it('un abonne cree apparait dans la liste', function () {
    Subscriber::factory()->create(['email' => 'test@example.com']);

    $this->actingAs($this->admin)->get('/admin/newsletter')
        ->assertSee('test@example.com');
});

it('le filtre search retourne le bon abonne', function () {
    Subscriber::factory()->create(['email' => 'rouge@example.com']);
    Subscriber::factory()->create(['email' => 'bleu@example.com']);

    $this->actingAs($this->admin)->get('/admin/newsletter?search=rouge')
        ->assertSee('rouge@example.com')
        ->assertDontSee('bleu@example.com');
});

it('le filtre filterStatus active fonctionne', function () {
    Subscriber::factory()->create(['email' => 'actif@example.com', 'confirmed_at' => now()]);
    Subscriber::factory()->create(['email' => 'attente@example.com', 'confirmed_at' => null]);

    $this->actingAs($this->admin)->get('/admin/newsletter?filterStatus=active')
        ->assertSee('actif@example.com')
        ->assertDontSee('attente@example.com');
});

it('la page affiche le total abonnes', function () {
    Subscriber::factory()->create(['email' => 'count@example.com']);

    $this->actingAs($this->admin)->get('/admin/newsletter')
        ->assertSee('abonné');
});
