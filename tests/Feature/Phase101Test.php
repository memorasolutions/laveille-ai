<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
    $this->user = User::factory()->create();
});

it('la page journaux activite retourne 200 pour un admin', function () {
    $this->actingAs($this->admin)->get('/admin/activity-logs')->assertStatus(200);
});

it('les invites sont rediriges vers login', function () {
    $this->get('/admin/activity-logs')->assertRedirect('/login');
});

it('les non admin obtiennent 403', function () {
    $this->actingAs($this->user)->get('/admin/activity-logs')->assertStatus(403);
});

it('la page affiche le titre journaux activite', function () {
    $this->actingAs($this->admin)->get('/admin/activity-logs')
        ->assertSee("Journaux d'activité", false);
});

it('la page affiche le bouton reinitialiser', function () {
    $this->actingAs($this->admin)->get('/admin/activity-logs')
        ->assertSee('Réinitialiser');
});

it('une activite creee apparait dans la liste', function () {
    activity('test')->log('message de test unique');

    $this->actingAs($this->admin)->get('/admin/activity-logs')
        ->assertSee('message de test unique');
});

it('le filtre search retourne la bonne activite', function () {
    activity('test')->log('rouge ceci');
    activity('test')->log('bleu cela');

    $this->actingAs($this->admin)->get('/admin/activity-logs?search=rouge')
        ->assertSee('rouge ceci')
        ->assertDontSee('bleu cela');
});

it('le filtre filterLogName fonctionne', function () {
    activity('custom_log')->log('description custom');

    $this->actingAs($this->admin)->get('/admin/activity-logs?filterLogName=custom_log')
        ->assertSee('custom_log');
});

it('la page affiche le total entrees', function () {
    activity('test')->log('test entry');

    $this->actingAs($this->admin)->get('/admin/activity-logs')
        ->assertSee('entrée');
});

it('la page affiche aucune activite quand vide', function () {
    Activity::query()->delete();

    $this->actingAs($this->admin)->get('/admin/activity-logs')
        ->assertSee('Aucune activité');
});
