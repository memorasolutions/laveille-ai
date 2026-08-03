<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Tools\Models\SavedPrompt;

uses(Tests\TestCase::class, RefreshDatabase::class);

// Round 125 (2026-07-30) : 6e et dernier champ libre du wizard laissé hors du garde-fou anti-fuite.
//
// Le textarea « Exemples (2-3 recommandés) » de la technique few-shot n'avait AUCUN attribut id.
// Or le garde-fou (prompt-anon-panel.js) résout ses champs par getElementById : sans id, il était
// STRUCTURELLEMENT impossible de le surveiller - exactement le défaut corrigé au round 119 pour le
// gabarit des cartes personnalisées, mais sur un champ encore plus exposé.
//
// Pourquoi c'est le plus à risque des six : son propre libellé invite à coller de vrais échanges
// (« Exemple 1 : Entrée : ... / Sortie : ... »). L'utilisateur qui veut montrer le ton attendu colle
// naturellement un vrai courriel, avec un vrai nom et de vraies coordonnées. Ce contenu part ensuite
// verbatim dans le prompt final (core.js:328) ET est persisté en base (wizardParams, core.js:387),
// puis ré-affiché à chaque réouverture via ?edit=ID.
//
// L'incohérence était visible à l'oeil nu : coller le même texte dans « Tâche » ou « Rôle
// personnalisé », juste à côté dans la même étape, déclenchait l'avertissement ; dans « Exemples »,
// jamais rien.
//
// Correctif en 3 volets, calqué sur le patron déjà établi pour les 5 autres champs :
//   1. id="cpExamples" sur le textarea (rend la surveillance possible) ;
//   2. examplesField ajouté à watchedFields + libellé i18n dans fieldLabels ;
//   3. dispatch 'input' à la ré-hydratation, sans quoi un prompt déjà contaminé rouvert via
//      ?edit=ID afficherait le contenu sans jamais le re-scanner (le trou du round 110, appliqué
//      aux 5 autres champs mais jamais à celui-ci).

it('gives the few-shot examples field an id so the guard can see it (round 125)', function () {
    $blade = file_get_contents(base_path('Modules/Tools/resources/views/public/tools/constructeur-prompts.blade.php'));

    expect($blade)->toContain('<textarea id="cpExamples" class="form-control form-control-sm" rows="4" x-model="examples"');
});

it('watches the examples field alongside the five original ones (round 125)', function () {
    $js = file_get_contents(public_path('assets/tools/constructeur-prompts/prompt-anon-panel.js'));

    expect($js)->toContain("const examplesField = document.getElementById('cpExamples');");
    expect($js)->toContain('const watchedFields = [taskField, personaCustomField, audienceCustomField, verbCustomField, constraintCustomField, examplesField].filter(Boolean);');
});

it('names the examples field in the warning instead of showing a raw id (round 125)', function () {
    $js = file_get_contents(public_path('assets/tools/constructeur-prompts/prompt-anon-panel.js'));
    $blade = file_get_contents(base_path('Modules/Tools/resources/views/public/tools/constructeur-prompts.blade.php'));

    expect($js)->toContain('cpExamples: i18n.anonFieldExamples');
    expect($blade)->toContain('anonFieldExamples: @json(');
});

it('rescans the examples field when an existing prompt is reopened (round 125)', function () {
    $js = file_get_contents(public_path('assets/tools/constructeur-prompts/constructeur-prompts-core.js'));

    // La restauration doit déclencher 'input' comme les 5 autres champs protégés, sinon une fuite
    // déjà enregistrée resterait invisible à la réouverture.
    $pos = strpos($js, 'if (p.examples) {');
    expect($pos)->not->toBeFalse();

    // Fenêtre en OCTETS, pas en caractères : les commentaires du bloc sont accentués, donc chaque
    // accent compte double. 700 était trop court et faisait échouer l'assertion sur du code correct.
    $block = substr($js, $pos, 1400);
    expect($block)->toContain("document.getElementById('cpExamples')");
    expect($block)->toContain("dispatchEvent(new Event('input', { bubbles: true }))");
});

it('keeps the five previously protected fields untouched (round 125 non-regression)', function () {
    $js = file_get_contents(public_path('assets/tools/constructeur-prompts/prompt-anon-panel.js'));

    foreach (['cpTaskObject', 'cpPersonaCustom', 'cpAudienceCustom', 'cpVerbCustom', 'cpConstraintCustom'] as $id) {
        expect($js)->toContain("document.getElementById('".$id."')");
    }

    // Le garde-fou du round 119 (gabarit des cartes) doit rester en place.
    expect($js)->toContain("CARD_TEMPLATE_PREFIX = 'cpCardTemplate-'");
});

it('renders the wizard and the library after the round 125 fix (real pages)', function () {
    $user = User::factory()->create();

    SavedPrompt::create([
        'user_id' => $user->id,
        'name' => 'Prompt test round 125',
        'prompt_text' => 'Contenu de test',
        'tags' => ['test-round-125'],
    ]);

    $this->actingAs($user)->get('/user/prompts')->assertOk();
});
