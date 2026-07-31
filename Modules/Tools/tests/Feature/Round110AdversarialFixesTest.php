<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Tools\Models\Tool;

uses(Tests\TestCase::class, RefreshDatabase::class);

// Round 110 (2026-07-27) : passe adversariale fraîche après le lot round 109 (garde-fou anti-PII
// étendu de 1 à 5 champs). 1 manque réel corrigé, un angle mort déjà identifié une fois pour
// #cpTaskObject seul (round 100) et rouvert pour les 4 NOUVEAUX champs du round 109 :
//
// La restauration d'un prompt existant (?edit=ID, constructeur-prompts-core.js init()) assigne
// self.personaCustom / self.verbCustom / self.audienceCustom / self.constraintCustom directement
// en Alpine, sans jamais dispatcher d'événement 'input' natif sur les DOM #cpPersonaCustom/
// #cpVerbCustom/#cpAudienceCustom/#cpConstraintCustom correspondants - contrairement à
// p.taskObject (même bloc) qui bénéficie déjà du fix round 100. Le garde-fou anti-PII du
// round 109 n'écoute QUE les événements 'input'/'blur' DOM natifs (prompt-anon-panel.js) - une
// assignation Alpine programmatique ne les déclenche pas. Résultat : un prompt sauvegardé avec
// une PII dans un de ces 4 champs (avant le round 109, ou saisie rapide contournant le debounce
// 600ms) rouvrait en édition SANS jamais déclencher le bandeau d'avertissement.
//
// Fixé : même pattern que le fix round 100 (self.$nextTick + dispatchEvent('input', {bubbles})
// sur l'ID DOM correspondant) reproduit pour les 4 champs.

it('dispatches a native input event on restore (?edit=ID) for the 4 custom fields, mirroring the round 100 fix for taskObject (round 110)', function () {
    $js = file_get_contents(public_path('assets/tools/constructeur-prompts/constructeur-prompts-core.js'));

    expect(substr_count($js, "el.dispatchEvent(new Event('input', { bubbles: true }));"))->toBeGreaterThanOrEqual(5);
    expect($js)->toContain("document.getElementById('cpPersonaCustom')");
    expect($js)->toContain("document.getElementById('cpVerbCustom')");
    expect($js)->toContain("document.getElementById('cpAudienceCustom')");
    expect($js)->toContain("document.getElementById('cpConstraintCustom')");
});

it('renders the constructeur-prompts page correctly after the round 110 fix (real page, no regression)', function () {
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
