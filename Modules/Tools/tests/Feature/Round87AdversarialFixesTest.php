<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Tools\Models\Tool;

uses(Tests\TestCase::class, RefreshDatabase::class);

// Round 87 (2026-07-27) : passe adversariale fraîche après le lot round 86 (~14 boutons
// .ct-btn-sm/.ct-btn-xs corrigés). 1 manque réel corrigé (2 règles CSS) :
//
// Cibles tactiles < 44×44px (WCAG 2.2 AAA SC 2.5.5) sur les 2 boutons d'une carte personnalisée
// (constructeur-prompts.blade.php) qui n'utilisent PAS les classes .ct-btn-* (donc pas touchés
// par le fix round 86) : .ct-custom-card__title-btn (bouton "Modifier le titre", min-height:28px)
// et .ct-custom-card__select (bouton "Utiliser cette carte →", l'action principale de la carte,
// min-height:32px). Confirmé : aucun style inline ni règle CSS ailleurs ne compensait ce déficit.
// Fixé : les 2 règles CSS portées à min-height:44px + display:flex/align-items:center pour centrer
// le contenu verticalement dans la cible agrandie.

it('has WCAG AAA 44px touch targets on the custom card title/select buttons (round 87)', function () {
    $blade = file_get_contents(base_path('Modules/Tools/resources/views/public/tools/constructeur-prompts.blade.php'));

    expect($blade)->toContain('.ct-custom-card__title-btn{background:none;border:none;padding:0;margin:0;text-align:left;font-weight:700;font-size:0.95rem;color:inherit;cursor:text;min-height:44px;');
    expect($blade)->toContain('.ct-custom-card__select{display:flex;align-items:center;width:100%;text-align:left;background:none;border:none;padding:0;margin-top:2px;font-size:0.78rem;color:var(--c-text-muted);cursor:pointer;min-height:44px;');
});

it('renders the custom card title/select CSS rules with 44px min-height on the real page (round 87)', function () {
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

    expect($html)->toContain('.ct-custom-card__title-btn{');
    expect($html)->toContain('.ct-custom-card__select{');
    expect($html)->not->toContain('cursor:text;min-height:28px;');
    expect($html)->not->toContain('cursor:pointer;min-height:32px;');
});
