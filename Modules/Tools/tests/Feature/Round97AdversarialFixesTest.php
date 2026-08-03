<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Tools\Models\Tool;

uses(Tests\TestCase::class, RefreshDatabase::class);

// Round 97 (2026-07-27) : passe adversariale fraîche après le lot round 96 (édition inline des
// tags dans "Mes prompts"). 1 manque réel corrigé :
//
// public/assets/tools/constructeur-prompts/constructeur-prompts-core.js - importLocalCustomCards()
// (import des cartes personnalisées invité → compte) n'avait AUCUNE garde de ré-entrance,
// contrairement à importLocalStorage() (garde `this.importing`, round 36). Un double-clic sur le
// bouton "Importer mes X cartes locales" déclenchait 2 appels concurrents qui créaient de VRAIS
// doublons persistés en base (même contenu, id différent) - sanitizeCustomCards()
// (ToolPreferenceController.php) génère un nouvel id sur collision au lieu de rejeter/fusionner.
// Fixé : flag `importingCards` (même pattern que `importing`), testé/mis à true en tête de
// importLocalCustomCards(), remis à false à la résolution (succès ou échec) ; bouton blade doté
// de `:disabled="importingCards"`. Preuve comportementale complète (RED/GREEN prouvé) :
// tests/js/constructeur-prompts-doubleimportcards.test.cjs.

it('gives importLocalCustomCards() a re-entrance guard via importingCards, same pattern as importLocalStorage() (round 97)', function () {
    $js = file_get_contents(base_path('public/assets/tools/constructeur-prompts/constructeur-prompts-core.js'));

    expect($js)->toContain('importingCards: false,');
    expect($js)->toContain('if (this.importingCards || local.length === 0) return;');
    expect($js)->toContain('this.importingCards = true;');
});

it('resets importingCards to false on both success and failure resolution (round 97)', function () {
    $js = file_get_contents(base_path('public/assets/tools/constructeur-prompts/constructeur-prompts-core.js'));

    // Le flag apparaît 4 fois : déclaration initiale, garde+set à true, reset succès, reset échec.
    expect(substr_count($js, 'importingCards'))->toBeGreaterThanOrEqual(4);
});

it('binds the import-cards button to :disabled="importingCards" (round 97)', function () {
    $blade = file_get_contents(base_path('Modules/Tools/resources/views/public/tools/constructeur-prompts.blade.php'));

    expect($blade)->toContain('@click="importLocalCustomCards()" :disabled="importingCards"');
});

it('renders the constructeur-prompts page with the importingCards binding present (real page)', function () {
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

    expect($html)->toContain(':disabled="importingCards"');
});
