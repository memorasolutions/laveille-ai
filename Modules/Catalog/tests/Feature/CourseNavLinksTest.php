<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 */

declare(strict_types=1);

/*
 * « Mariage » de la navigation entre la vitrine des formations (/cours) et l'académie Moodle
 * (D-20261015-01). Deux comportements verrouillés ici, parce qu'ils sont invisibles à un contrôle
 * qui ne regarde que le code :
 *
 *  1. Fil d'Ariane des sous-pages /cours/* : le niveau intermédiaire « Nos formations » doit être
 *     un LIEN cliquable vers /cours (et pas un simple texte). Un contrôle de destination seul ne
 *     verrait rien ; il faut regarder l'ANCRE rendue.
 *  2. Raccourci « Mes cours » vers le tableau de bord de l'académie : présent SEULEMENT pour un
 *     utilisateur connecté, jamais pour un visiteur anonyme (qui tomberait sinon sur une connexion
 *     brutale).
 */

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    // Les routes /cours et /cours/demande ne doivent pas être coupées par leurs drapeaux.
    config(['catalog.request_enabled' => true]);
    // Cible connue de « Mes cours » (le Moodle sous laveille.ai/formations).
    config(['catalog.moodle_base_url' => 'https://laveille.ai/formations']);
});

it('rend « Nos formations » comme lien cliquable vers /cours dans le fil d\'Ariane de /cours/demande', function () {
    // L'ancre exacte produite par le partial breadcrumb pour un item intermédiaire cliquable.
    $this->get('/cours/demande')
        ->assertOk()
        ->assertSee('href="'.route('catalog.cours').'">'.__('Nos formations').'</a>', false);
});

it('n\'affiche PAS le raccourci « Mes cours » à un visiteur anonyme sur /cours', function () {
    // On vise l'ATTRIBUT de l'élément (class="...") et l'URL cible, jamais la classe CSS
    // « .catalog-mycourses » qui, elle, est toujours dans la feuille de style poussée.
    $this->get('/cours')
        ->assertOk()
        ->assertDontSee('class="catalog-mycourses"', false)
        ->assertDontSee('/formations/my', false);
});

it('affiche le raccourci « Mes cours » vers le tableau de bord de l\'académie pour un utilisateur connecté', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/cours')
        ->assertOk()
        ->assertSee('class="catalog-mycourses"', false)
        ->assertSee('href="https://laveille.ai/formations/my"', false)
        ->assertSee(__('Mes cours'));
});
