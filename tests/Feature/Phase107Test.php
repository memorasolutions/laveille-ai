<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Webhooks\Models\WebhookEndpoint;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
    $this->user = User::factory()->create();
});

it('la page webhooks retourne 200 pour un admin', function () {
    $this->actingAs($this->admin)->get('/admin/webhooks')->assertStatus(200);
});

it('les invites sont rediriges vers login', function () {
    $this->get('/admin/webhooks')->assertRedirect('/login');
});

it('les non admin obtiennent 403', function () {
    $this->actingAs($this->user)->get('/admin/webhooks')->assertStatus(403);
});

it('la page affiche le formulaire ajout', function () {
    $this->actingAs($this->admin)->get('/admin/webhooks')
        ->assertSee('Ajouter un endpoint')
        ->assertSee('Ajouter');
});

it('la page affiche aucun webhook quand vide', function () {
    WebhookEndpoint::query()->delete();

    $this->actingAs($this->admin)->get('/admin/webhooks')
        ->assertSee('Aucun webhook');
});

it('un webhook cree apparait dans la liste', function () {
    WebhookEndpoint::factory()->create(['name' => 'Slack notifs', 'url' => 'https://hooks.slack.com/test']);

    $this->actingAs($this->admin)->get('/admin/webhooks')
        ->assertSee('Slack notifs');
});

it('la page affiche le compteur endpoints configures', function () {
    WebhookEndpoint::factory()->create(['name' => 'Test webhook', 'url' => 'https://example.com/hook']);

    $this->actingAs($this->admin)->get('/admin/webhooks')
        ->assertSee('Endpoints configurés');
});

it('la page affiche le badge actif', function () {
    WebhookEndpoint::factory()->create(['name' => 'Actif hook', 'url' => 'https://example.com/hook', 'is_active' => true]);

    $this->actingAs($this->admin)->get('/admin/webhooks')
        ->assertSee('Actif');
});

it('un webhook avec url apparait dans la liste', function () {
    WebhookEndpoint::factory()->create(['name' => 'Discord', 'url' => 'https://discord.com/api/webhooks/test']);

    $this->actingAs($this->admin)->get('/admin/webhooks')
        ->assertSee('Discord');
});

it('les colonnes nom url statut date actions sont presentes', function () {
    WebhookEndpoint::factory()->create(['name' => 'Test cols', 'url' => 'https://example.com/hook']);

    $this->actingAs($this->admin)->get('/admin/webhooks')
        ->assertSee('Nom')
        ->assertSee('URL')
        ->assertSee('Statut')
        ->assertSee('Créé le')
        ->assertSee('Actions');
});
