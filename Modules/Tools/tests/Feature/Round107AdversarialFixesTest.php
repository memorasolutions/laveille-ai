<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Tools\Models\SavedPrompt;

uses(Tests\TestCase::class, RefreshDatabase::class);

// Round 107 (2026-07-27) : passe adversariale fraîche après le lot round 106 (5e chemin de perte
// de focus sur le sélecteur d'icône, closeIconPicker()). 1 manque réel corrigé, sur une PAGE
// DIFFÉRENTE (les rounds 105/106 étaient scopés au sélecteur de cartes personnalisées dans
// constructeur-prompts-core.js/constructeur-prompts.blade.php) :
//
// Modules/Tools/resources/views/user/prompts/index.blade.php:191-206 - même classe de bug WCAG
// 2.4.3 (Ordre du focus) sur le panneau d'édition inline des tags ("Mes prompts") : les boutons
// "Enregistrer" et "Annuler" sont eux-mêmes à l'intérieur du <div x-show="editingTags"> qu'ils
// masquent en cliquant dessus. Le focus du bouton cliqué tombait silencieusement sur <body>
// quand son conteneur passait à display:none - exactement le symptôme corrigé aux rounds 105-106
// via _restoreFocusIfLost(), mais totalement absent ici (page distincte, composant Alpine
// indépendant). Fixé : nouvelle méthode restoreFocusIfLost(el) définie dans le x-data de chaque
// carte de prompt (même principe : ne restaure le focus vers le bouton ⋮ de la carte QUE si
// document.activeElement est réellement tombé sur <body>), appelée par les 2 boutons après
// fermeture du panneau.

it('adds restoreFocusIfLost() to the per-card Alpine scope and calls it from both tag-panel buttons (round 107)', function () {
    $blade = file_get_contents(base_path('Modules/Tools/resources/views/user/prompts/index.blade.php'));

    expect($blade)->toContain('restoreFocusIfLost(el)');
    expect($blade)->toContain("if (document.activeElement === document.body)");
    expect(substr_count($blade, 'restoreFocusIfLost($el)'))->toBe(2);
});

it('renders /user/prompts (Mes prompts) correctly after the round 107 fix (real page, no regression)', function () {
    $user = User::factory()->create();

    SavedPrompt::create([
        'user_id' => $user->id,
        'name' => 'Prompt test round 107',
        'prompt_text' => 'Contenu de test',
        'tags' => ['test-round-107'],
    ]);

    $this->actingAs($user)->get('/user/prompts')->assertOk();
});
