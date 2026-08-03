<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Tools\Models\Tool;

uses(Tests\TestCase::class, RefreshDatabase::class);

// Round 95 (2026-07-27) : passe adversariale fraîche après le lot round 94 (copy()/openIn()
// délèguent à window.copyToClipboard()). 1 manque réel corrigé :
//
// public/assets/tools/constructeur-prompts/constructeur-prompts-core.js - le panneau d'édition du
// gabarit d'une carte personnalisée (round 46) reste OUVERT après un blur du textarea
// (editingCardPanelId n'est remis à null que par toggleCardPanel()/cancelEditCardPanel()). Un clic
// sur un AUTRE bouton de la MÊME carte (↑/↓/👁️/🗑️) pendant l'édition déclenche un blur
// intermédiaire qui persistait la valeur courante côté serveur SANS jamais rafraîchir
// editingCardPanelSnapshot (capturé UNE SEULE FOIS à l'ouverture du panneau). Un Échap ultérieur
// restaurait alors ce snapshot périmé - plus ancien que ce qui était réellement persisté -
// laissant le client et le serveur désynchronisés silencieusement. Fixé :
// commitCardPanelBlur(card), appelé par @blur du textarea (blade), rafraîchit désormais le
// snapshot à CHAQUE blur, pas seulement à l'ouverture. Preuve comportementale complète
// (RED/GREEN prouvé) : tests/js/constructeur-prompts-cardpanel-staleblur.test.cjs.

it('gives the card-template textarea a commitCardPanelBlur() handler that refreshes the cancel snapshot (round 95)', function () {
    $js = file_get_contents(base_path('public/assets/tools/constructeur-prompts/constructeur-prompts-core.js'));

    expect($js)->toContain('commitCardPanelBlur: function(card) {');
    expect($js)->toContain('this.editingCardPanelSnapshot = card.query_template;');
});

it('binds the card-template textarea to commitCardPanelBlur() instead of a bare persistCustomCards() call (round 95)', function () {
    $blade = file_get_contents(base_path('Modules/Tools/resources/views/public/tools/constructeur-prompts.blade.php'));

    expect($blade)->toContain('@blur="commitCardPanelBlur(c)"');
    expect($blade)->not->toContain('@blur="persistCustomCards()"');
});

it('renders the constructeur-prompts page with the commitCardPanelBlur() binding present (real page)', function () {
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

    expect($html)->toContain('@blur="commitCardPanelBlur(c)"');
});
