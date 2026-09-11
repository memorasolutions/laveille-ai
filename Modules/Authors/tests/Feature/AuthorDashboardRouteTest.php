<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\Authors\Models\AuthorProfile;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('redirige un invité vers la connexion', function () {
    $this->get('/auteur/dashboard')->assertRedirect();
});

it('affiche le tableau de bord à un auteur connecté', function () {
    $user = User::factory()->create();

    AuthorProfile::create([
        'user_id' => $user->id,
        'slug' => 'dash-'.strtolower(Str::random(6)),
        'display_name' => 'Auteur Test',
        'tier' => 'free',
    ]);

    // Ce test échoue avec une erreur 500 si la vue authors::dashboard n'existe pas.
    $this->actingAs($user)
        ->get('/auteur/dashboard')
        ->assertOk()
        ->assertSee('Mon espace auteur', false);
});

it('affiche un message clair à un utilisateur sans profil auteur', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/auteur/dashboard')
        ->assertOk()
        ->assertSee("n'a pas encore de profil d'auteur", false);
});
