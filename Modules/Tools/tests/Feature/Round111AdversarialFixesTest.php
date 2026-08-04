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

// Test du dispatch d'événement 'input' (garde-fou anti-PII) retiré le 2026-08-04 : le panneau
// d'anonymisation intégré au constructeur de prompts a été retiré (demande explicite de
// l'utilisateur, séparation des deux outils), avec lui le seul écouteur qui consommait cet
// événement synthétique. La logique de garde elle-même (ne jamais écraser un choix explicite de
// persona/contraintes) reste intacte et inchangée dans constructeur-prompts-core.js.
it('keeps the "Mon profil" autofill guard intact (never overwrites an explicit choice)', function () {
    $js = file_get_contents(public_path('assets/tools/constructeur-prompts/constructeur-prompts-core.js'));

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
