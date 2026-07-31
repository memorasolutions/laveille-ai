<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Tools\Models\SavedPrompt;

uses(Tests\TestCase::class, RefreshDatabase::class);

// Round 116 (2026-07-27) : passe adversariale fraîche après le lot round 115. 1 manque réel
// corrigé, sur un axe jamais couvert par les rounds 106-115 (SEO) :
//
// /user/prompts (« Mes prompts ») est une bibliothèque PRIVÉE derrière auth, mais n'avait AUCUN
// mécanisme noindex - ni @section('page_noindex'), ni <meta name="robots">, ni X-Robots-Tag.
// C'était la seule page de gestion privée du projet dans ce cas : « Mes journaux »
// (Modules/Journal, index/create/edit) pose page_noindex sur ses 3 vues, et avatar/editor +
// quest/index posent un <meta robots> en dur. robots.txt contient bien « Disallow: /user », mais
// ce n'est pas équivalent : un Disallow empêche le CRAWL (donc la balise meta n'est jamais lue)
// sans empêcher l'URL d'apparaître dans l'index sans extrait si elle est découverte via un lien
// externe (« indexed though blocked by robots.txt »). Les 2 couches sont complémentaires.
//
// Fixé : @section('page_noindex', true), comme la convention établie. Le mécanisme est prouvé
// fonctionnel via cette chaîne exacte de layouts (auth::layouts.user-frontend -> master
// FrontTheme), puisque Modules/Journal/index.blade.php étend le MÊME layout et l'utilise déjà.

it('actually renders the robots noindex meta tag on /user/prompts (round 116)', function () {
    $user = User::factory()->create();

    SavedPrompt::create([
        'user_id' => $user->id,
        'name' => 'Prompt test round 116',
        'prompt_text' => 'Contenu de test',
        'tags' => ['test-round-116'],
    ]);

    // Vérification de l'EFFET RÉEL (la balise dans le HTML rendu), pas seulement de la présence
    // de la directive Blade - une @section sans layout compatible ne produirait rien.
    $this->actingAs($user)
        ->get('/user/prompts')
        ->assertOk()
        ->assertSee('name="robots"', false)
        ->assertSee('noindex', false);
});

it('declares page_noindex like the established convention for private user pages (round 116)', function () {
    $blade = file_get_contents(base_path('Modules/Tools/resources/views/user/prompts/index.blade.php'));
    $journal = file_get_contents(base_path('Modules/Journal/resources/views/index.blade.php'));

    expect($blade)->toContain("@section('page_noindex', true)");
    // Même convention que « Mes journaux », page privée équivalente (référence du projet).
    expect($journal)->toContain("@section('page_noindex', true)");
});

it('keeps the public constructeur-prompts page indexable (round 116, no over-correction)', function () {
    $blade = file_get_contents(base_path('Modules/Tools/resources/views/public/tools/constructeur-prompts.blade.php'));

    // La page PUBLIQUE de l'outil ne doit surtout PAS hériter d'un noindex : seule la
    // bibliothèque privée est concernée.
    expect($blade)->not->toContain("@section('page_noindex'");
});
