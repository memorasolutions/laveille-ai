<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Tools\Models\Tool;

uses(Tests\TestCase::class, RefreshDatabase::class);

// Round 106 (2026-07-27) : passe adversariale fraîche après le lot round 105 (perte de focus WCAG
// 2.4.3, 4 fonctions protégées via _restoreFocusIfLost). 1 manque réel corrigé :
//
// Modules/Tools/resources/views/public/tools/constructeur-prompts.blade.php:19 - le sélecteur
// d'icône (.ct-emoji-grid, template x-if) pouvait aussi se fermer via un 5e chemin jamais couvert
// par le round 105 : le listener @keydown.escape.window="iconPickerOpenId = null" posé sur le
// conteneur racine - une assignation Alpine inline directe qui contournait entièrement
// _restoreFocusIfLost() (elle ne passe pas par setCardIcon()). Un utilisateur clavier qui ouvre le
// sélecteur, tabule jusqu'à un bouton emoji, puis presse Échap sans choisir voyait son focus
// tomber silencieusement sur <body> - même symptôme que les 4 cas déjà corrigés au round 105, sur
// un point d'entrée non protégé. Fixé : nouvelle méthode closeIconPicker() qui réutilise
// _restoreFocusIfLost() déjà établie (DRY), appelée par le handler global à la place de
// l'assignation inline.

it('routes the global Escape handler through closeIconPicker() instead of an inline assignment (round 106)', function () {
    $blade = file_get_contents(base_path('Modules/Tools/resources/views/public/tools/constructeur-prompts.blade.php'));

    expect($blade)->toContain('@keydown.escape.window="closeIconPicker()"');
    expect($blade)->not->toContain('@keydown.escape.window="iconPickerOpenId = null"');
});

it('closeIconPicker() reuses _restoreFocusIfLost() (round 106)', function () {
    $js = file_get_contents(base_path('public/assets/tools/constructeur-prompts/constructeur-prompts-core.js'));

    expect($js)->toContain('closeIconPicker: function()');
    expect($js)->toContain("this._restoreFocusIfLost('cpCardIconBtn-' + id);");
});

it('renders the constructeur-prompts page correctly after the round 106 fix (real page, no regression)', function () {
    Tool::firstOrCreate(['slug' => 'constructeur-prompts'], [
        'name' => 'Constructeur de prompts',
        'description' => 'Test',
        'icon' => '✨',
        'is_active' => true,
        'is_under_construction' => false,
        'category' => 'productivite',
    ]);

    $user = User::factory()->create();

    $this->actingAs($user)->get('/outils/constructeur-prompts')->assertOk();
});
