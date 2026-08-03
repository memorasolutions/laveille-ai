<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Tools\Models\Tool;

uses(Tests\TestCase::class, RefreshDatabase::class);

// Round 105 (2026-07-27) : passe adversariale fraîche après le lot round 104 (accord singulier/
// pluriel bouton import). 1 manque réel corrigé :
//
// public/assets/tools/constructeur-prompts/constructeur-prompts-core.js (commitCardTitle,
// cancelEditCardTitle, setCardIcon, cancelEditCardPanel) - ces 4 fonctions retirent du DOM
// (template x-if, pas x-show) l'élément qui a le focus clavier au moment de l'action : valider
// (Enter) ou annuler (Escape) le titre d'une carte perso, sélectionner une icône, fermer (Escape)
// le panneau d'édition du gabarit. Le focus tombait silencieusement sur <body> - échec WCAG
// 2.4.3 (Ordre du focus). Le projet a déjà établi le bon pattern ailleurs dans son propre code
// (Modules/Core/resources/views/components/action-menu.blade.php:101,
// $refs.trigger.focus() après Escape) mais ne l'avait jamais appliqué ici. Fixé : nouvelle méthode
// utilitaire _restoreFocusIfLost(elementId) appelée dans les 4 fonctions, qui ne restaure le focus
// vers le bouton déclencheur QUE si document.activeElement est bien tombé sur <body> (perte
// réelle) - ne casse jamais le cas @blur (commitCardTitle est aussi appelée au blur du champ
// titre : dans ce cas le nouveau focus a déjà été posé par le clic de l'utilisateur AVANT que ce
// code s'exécute, donc document.activeElement n'est jamais <body> et rien n'est écrasé). 3 boutons
// déclencheurs reçoivent un id dynamique pour être re-focalisables : cpCardIconBtn-{id},
// cpCardTitleBtn-{id}, cpCardPanelBtn-{id}.

it('gives the 3 trigger buttons stable ids for focus restoration (round 105)', function () {
    $blade = file_get_contents(base_path('Modules/Tools/resources/views/public/tools/constructeur-prompts.blade.php'));

    expect($blade)->toContain(":id=\"'cpCardIconBtn-' + c.id\"");
    expect($blade)->toContain(":id=\"'cpCardTitleBtn-' + c.id\"");
    expect($blade)->toContain(":id=\"'cpCardPanelBtn-' + c.id\"");
});

it('restores focus to the trigger button only when focus was actually lost to body (round 105)', function () {
    $js = file_get_contents(base_path('public/assets/tools/constructeur-prompts/constructeur-prompts-core.js'));

    expect($js)->toContain('_restoreFocusIfLost: function(elementId)');
    expect($js)->toContain('if (document.activeElement === document.body)');
    expect(substr_count($js, "this._restoreFocusIfLost('cpCardTitleBtn-' + card.id);"))->toBe(2);
    expect($js)->toContain("this._restoreFocusIfLost('cpCardIconBtn-' + card.id);");
    expect($js)->toContain("this._restoreFocusIfLost('cpCardPanelBtn-' + card.id);");
});

it('renders the constructeur-prompts page correctly after the round 105 fix (real page, no regression)', function () {
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
