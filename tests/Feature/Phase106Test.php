<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Newsletter\Models\Campaign;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
    $this->user = User::factory()->create();
});

it('la page campagnes retourne 200 pour un admin', function () {
    $this->actingAs($this->admin)->get('/admin/newsletter/campaigns')->assertStatus(200);
});

it('les invites sont rediriges vers login', function () {
    $this->get('/admin/newsletter/campaigns')->assertRedirect('/login');
});

it('les non admin obtiennent 403', function () {
    $this->actingAs($this->user)->get('/admin/newsletter/campaigns')->assertStatus(403);
});

it('la page affiche le bouton reinitialiser', function () {
    $this->actingAs($this->admin)->get('/admin/newsletter/campaigns')
        ->assertSee('Réinitialiser');
});

it('la page affiche le bouton nouvelle campagne', function () {
    $this->actingAs($this->admin)->get('/admin/newsletter/campaigns')
        ->assertSee('Nouvelle campagne');
});

it('la page affiche aucune campagne quand vide', function () {
    Campaign::query()->delete();

    $this->actingAs($this->admin)->get('/admin/newsletter/campaigns')
        ->assertSee('Aucune campagne');
});

it('une campagne creee apparait dans la liste', function () {
    Campaign::factory()->create(['subject' => 'Campagne de bienvenue']);

    $this->actingAs($this->admin)->get('/admin/newsletter/campaigns')
        ->assertSee('Campagne de bienvenue');
});

it('le filtre search retourne la bonne campagne', function () {
    Campaign::factory()->create(['subject' => 'Campagne printemps']);
    Campaign::factory()->create(['subject' => 'Campagne automne']);

    $this->actingAs($this->admin)->get('/admin/newsletter/campaigns?search=printemps')
        ->assertSee('Campagne printemps')
        ->assertDontSee('Campagne automne');
});

it('le filtre filterStatus fonctionne', function () {
    Campaign::factory()->create(['subject' => 'Brouillon actuel', 'status' => 'draft']);
    Campaign::factory()->create(['subject' => 'Envoyee precedente', 'status' => 'sent', 'sent_at' => now()]);

    $this->actingAs($this->admin)->get('/admin/newsletter/campaigns?filterStatus=draft')
        ->assertSee('Brouillon actuel')
        ->assertDontSee('Envoyee precedente');
});

it('la page affiche le total campagnes', function () {
    Campaign::factory()->create(['subject' => 'Test total']);

    $this->actingAs($this->admin)->get('/admin/newsletter/campaigns')
        ->assertSee('campagne');
});
