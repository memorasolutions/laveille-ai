<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Tools\Models\SavedPrompt;

uses(Tests\TestCase::class, RefreshDatabase::class);

// Round 121 (2026-07-30) : passe adversariale fraîche après le round 120. 1 manque réel corrigé,
// symétrique d'un correctif déjà livré - donc invisible sans regarder les DEUX sens du trajet.
//
// L'item de menu « Modifier les tags » exécutait `open = false; editingTags = true`. Or ce bouton
// est rendu par le composant action-menu À L'INTÉRIEUR de son `<div x-ref="menu" x-show="open">`.
// Mettre `open` à false masque donc (display:none) le conteneur du bouton qui est en train de
// recevoir l'activation clavier. Le comportement natif des navigateurs dans ce cas est de renvoyer
// le focus à <body>, sans aucune annonce pour un lecteur d'écran.
//
// Conséquence réelle pour un utilisateur clavier : il Tab jusqu'au ⋮, ouvre le menu, active
// « Modifier les tags », et se retrouve avec le focus au tout début de la page. Le champ de saisie
// des tags qu'il vient précisément d'ouvrir n'est jamais atteint - il doit re-tabuler toute la page.
//
// Le round 107 avait corrigé le trajet INVERSE (fermeture du panneau via restoreFocusIfLost sur
// les boutons Enregistrer/Annuler) et son test fige `substr_count('restoreFocusIfLost($el)') === 2`,
// ce qui confirme que le sens OUVERTURE n'avait jamais été couvert.
//
// Correctif : openTagsEditor(el), méthode symétrique dans le même x-data de carte, qui ouvre le
// panneau puis déplace le focus dans le champ au $nextTick (le panneau est en x-show, donc l'input
// est déjà dans le DOM - il suffit d'attendre que Alpine le rende visible).

it('moves focus into the tags field when opening the editor (round 121)', function () {
    $blade = file_get_contents(base_path('Modules/Tools/resources/views/user/prompts/index.blade.php'));

    expect($blade)->toContain('openTagsEditor(el) { this.editingTags = true;');
    expect($blade)->toContain("querySelector('input[id^=tags-input-]')");
    expect($blade)->toContain("'alpineClick' => 'open = false; openTagsEditor(\$el)'");
    // L'ancien code laissait le focus tomber sur <body> sans rien faire.
    expect($blade)->not->toContain("'alpineClick' => 'open = false; editingTags = true'");
});

it('keeps the round 107 close-path fix untouched (round 121 non-regression)', function () {
    $blade = file_get_contents(base_path('Modules/Tools/resources/views/user/prompts/index.blade.php'));

    // Les 2 appels de FERMETURE (Enregistrer / Annuler) doivent rester exactement tels quels :
    // le round 121 ajoute le sens ouverture, il ne remplace pas le sens fermeture.
    expect($blade)->toContain('restoreFocusIfLost(el)');
    expect(substr_count($blade, 'restoreFocusIfLost($el)'))->toBe(2);
});

it('confirms the menu container really hides the clicked button (round 121 root cause)', function () {
    $component = file_get_contents(base_path('Modules/Core/resources/views/components/action-menu.blade.php'));

    // C'est CE x-show="open" sur le conteneur des items qui provoque la perte de focus quand
    // l'action met `open` à false. Si ce contrat change, le correctif doit être réévalué.
    expect($component)->toContain('<div x-ref="menu" x-show="open"');
});

it('renders /user/prompts correctly after the round 121 fix (real page, no regression)', function () {
    $user = User::factory()->create();

    SavedPrompt::create([
        'user_id' => $user->id,
        'name' => 'Prompt test round 121',
        'prompt_text' => 'Contenu de test',
        'tags' => ['test-round-121'],
    ]);

    $this->actingAs($user)->get('/user/prompts')->assertOk();
});
