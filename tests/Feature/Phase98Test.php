<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Modules\Notifications\Notifications\SystemAlertNotification;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
    $this->user = User::factory()->create();
});

it('la page notifications retourne 200 pour un admin', function () {
    $this->actingAs($this->admin)->get('/admin/notifications')->assertStatus(200);
});

it('les invites sont rediriges vers login', function () {
    $this->get('/admin/notifications')->assertRedirect('/login');
});

it('les utilisateurs non admin obtiennent 403', function () {
    $this->actingAs($this->user)->get('/admin/notifications')->assertStatus(403);
});

it('la page affiche le formulaire de diffusion', function () {
    $this->actingAs($this->admin)->get('/admin/notifications')->assertSee('Diffuser une alerte');
});

it('la page affiche le select niveau', function () {
    $this->actingAs($this->admin)->get('/admin/notifications')->assertSee('name="level"', false);
});

it('la page affiche le textarea message', function () {
    $this->actingAs($this->admin)->get('/admin/notifications')->assertSee('name="message"', false);
});

it('broadcast envoie les notifications a tous les utilisateurs', function () {
    Notification::fake();
    $this->actingAs($this->admin)->post('/admin/notifications/broadcast', [
        'level' => 'info',
        'message' => 'Test alerte système',
    ]);
    Notification::assertSentTo($this->admin, SystemAlertNotification::class);
    Notification::assertSentTo($this->user, SystemAlertNotification::class);
});

it('broadcast redirige avec flash success', function () {
    Notification::fake();
    $this->actingAs($this->admin)->post('/admin/notifications/broadcast', [
        'level' => 'warning',
        'message' => 'Alerte de test',
    ])->assertRedirect()->assertSessionHas('success');
});

it('broadcast valide le message requis', function () {
    $this->actingAs($this->admin)->post('/admin/notifications/broadcast', [
        'level' => 'info',
    ])->assertSessionHasErrors('message');
});

it('broadcast valide le niveau valide', function () {
    $this->actingAs($this->admin)->post('/admin/notifications/broadcast', [
        'level' => 'invalide',
        'message' => 'Alerte test',
    ])->assertSessionHasErrors('level');
});
