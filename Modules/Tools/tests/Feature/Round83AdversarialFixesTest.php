<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Tools\Models\Tool;

uses(Tests\TestCase::class, RefreshDatabase::class);

// Round 83 (2026-07-27) : passe adversariale fraîche après le lot round 82 (serverMessage 422,
// ToolPreferenceController i18n, contraste #9CA3AF). 1 manque réel corrigé :
//
// 1. 4 groupes de boutons radio (audienceType ~ligne 275, personaType ~ligne 331, verbType
//    ~ligne 360, formatMode ~ligne 508 - 8 <label> au total) avaient une cible tactile réelle
//    (le <label> englobant, qui active le radio par association native label→input) très
//    en-dessous du seuil WCAG 2.2 AAA SC 2.5.5 (≥44x44 CSS px) : soit aucun min-height/padding
//    (verbType, formatMode → ~24px de haut, bornée par le radio de 24px), soit min-height:24px
//    (audienceType, personaType → ~32px avec le padding). Ce même fichier applique déjà et
//    documente explicitement cette norme ailleurs (.ct-pill min-height:44px, .ct-step-circle,
//    .ct-custom-card__icon-action) - ces 4 groupes avaient été oubliés de cette passe. Fixé :
//    min-height:44px ajouté aux 8 <label>, cohérent avec .ct-pill déjà en place.

it('the 4 radio-group labels (audienceType/personaType/verbType/formatMode) have WCAG AAA 44px touch targets (round 83)', function () {
    $blade = file_get_contents(base_path('Modules/Tools/resources/views/public/tools/constructeur-prompts.blade.php'));

    // 8 labels au total (2 par groupe x 4 groupes), tous avec min-height: 44px.
    expect(substr_count($blade, 'min-height: 44px; padding: 4px 6px'))->toBeGreaterThanOrEqual(8);
});

it('renders the radio-group labels with 44px min-height on the real page (round 83)', function () {
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

    // Les 4 name="..." radios doivent tous être précédés d'un label avec min-height: 44px
    // dans les 400 caractères qui le précèdent (association label -> input directe).
    foreach (['audienceType', 'personaType', 'verbType', 'formatMode'] as $groupName) {
        $pos = strpos($html, 'name="' . $groupName . '"');
        expect($pos)->not->toBeFalse();
        $before = substr($html, max(0, $pos - 400), 400);
        expect($before)->toContain('min-height: 44px');
    }
});
