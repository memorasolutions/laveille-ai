<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Tools\Models\Tool;

uses(Tests\TestCase::class, RefreshDatabase::class);

// Round 111 (2026-07-27) : passe adversariale fraîche après le lot round 110 (dispatch input sur
// ?edit=ID pour les 4 champs personnalisés). 1 manque réel corrigé, un 6e chemin d'assignation
// programmatique déjà distinct de ?edit=ID/selectTask() :
//
// L'auto-remplissage "Mon profil" (constructeur-prompts-core.js, init(), branche
// /api/tool-preferences/constructeur-prompts) assigne self.personaCustom (depuis profile_role) et
// self.constraintCustom (depuis profile_style/profile_constraints) SANS jamais dispatcher
// l'événement 'input' natif - le garde-fou anti-PII (rounds 109/110) ne se déclenchait donc jamais
// sur ce contenu, même si "Mon profil" (/user/prompts, champs libres non validés pour du contenu
// personnel, ex. "toujours mentionner mon numéro 514-555-1234") contient une vraie PII. Ce chemin
// est plus insidieux que les précédents : il s'exécute AUTOMATIQUEMENT à chaque ouverture d'un
// NOUVEAU prompt, sans action explicite de l'utilisateur.
//
// Fixé : même pattern (self.$nextTick + dispatchEvent('input', {bubbles})) reproduit sur les 2
// assignations, sans toucher à la logique de garde existante (personaType==='preset' &&
// personaPreset==='' ; constraintCustom==='').

it('dispatches a native input event when "Mon profil" autofills personaCustom/constraintCustom, mirroring the round 100/110 fixes (round 111)', function () {
    $js = file_get_contents(public_path('assets/tools/constructeur-prompts/constructeur-prompts-core.js'));

    expect($js)->toContain('// Round 111 (2026-07-27, passe adversariale)');
    // Le compte total passe de 6 (round 110) à 8 (round 111 ajoute 2 nouveaux dispatch).
    expect(substr_count($js, "if (el) el.dispatchEvent(new Event('input', { bubbles: true }));"))->toBeGreaterThanOrEqual(8);
    // La logique de garde d'origine reste intacte (ne jamais écraser un choix explicite).
    expect($js)->toContain("if (profile.profile_role && self.personaCustom === '' && self.personaType === 'preset' && self.personaPreset === '') {");
    expect($js)->toContain("if (extra.length > 0 && self.constraintCustom === '') {");
});

it('renders the constructeur-prompts page correctly after the round 111 fix (real page, no regression)', function () {
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
