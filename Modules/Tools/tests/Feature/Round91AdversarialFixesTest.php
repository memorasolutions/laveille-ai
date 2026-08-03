<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Tools\Models\Tool;

uses(Tests\TestCase::class, RefreshDatabase::class);

// Round 91 (2026-07-27) : passe adversariale fraîche après le lot round 90 (hasLocalData stale +
// dismissBtn PII 44px). 1 manque réel corrigé :
//
// Les boutons du sélecteur d'icône (.ct-emoji-grid) des cartes personnalisées passaient de 44×44px
// (base) à 40×40px sous 576px de largeur (media query mobile) - en échec WCAG 2.2 AAA SC 2.5.5,
// précisément sur le contexte tactile principal. Ces boutons n'ont aucune classe .ct-btn/.ct-btn-xs
// (donc aucune règle globale de charte.css ne compensait). Fixé : la réduction à 4 colonnes en
// media query suffit déjà à éviter le débordement horizontal signalé au round 16 (188px de large +
// padding, largement sous les ~320px du plus petit viewport mobile) - inutile de rétrécir aussi les
// boutons, qui restent à 44px sur tous les breakpoints.

it('keeps the emoji picker buttons at 44px on mobile (round 91)', function () {
    $blade = file_get_contents(base_path('Modules/Tools/resources/views/public/tools/constructeur-prompts.blade.php'));

    expect($blade)->not->toContain('width:40px;height:40px;');
    expect($blade)->toContain('.ct-emoji-grid{grid-template-columns:repeat(4,44px);}');
    // La règle de base (hors media query) doit toujours fixer les boutons à 44px.
    expect($blade)->toContain('.ct-emoji-grid button{width:44px;height:44px;');
});

it('renders the emoji picker CSS with 44px buttons on the real page (round 91)', function () {
    Tool::firstOrCreate(['slug' => 'constructeur-prompts'], [
        'name' => 'Constructeur de prompts',
        'description' => 'Test',
        'icon' => '✨',
        'is_active' => true,
        'is_under_construction' => false,
        'category' => 'productivite',
    ]);

    $user = User::factory()->create();

    $html = $this->actingAs($user)->get('/outils/constructeur-prompts')->assertOk()->getContent();

    expect($html)->not->toContain('.ct-emoji-grid button{width:40px;height:40px;}');
    expect($html)->toContain('.ct-emoji-grid{grid-template-columns:repeat(4,44px);}');
});
