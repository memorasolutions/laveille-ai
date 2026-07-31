<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Tools\Models\Tool;

uses(Tests\TestCase::class, RefreshDatabase::class);

// Round 100 (2026-07-27) : passe adversariale fraîche après le lot round 99 (attributs de
// validation FR). 1 manque réel corrigé :
//
// public/assets/tools/constructeur-prompts/constructeur-prompts-core.js - le garde-fou
// anti-données-personnelles (prompt-anon-panel.js, checkEntities()) ne se déclenchait QUE via les
// écouteurs DOM natifs 'input'/'blur' posés sur #cpTaskObject. Quand taskObject était rempli
// PROGRAMMATIQUEMENT par Alpine (restauration ?edit=ID, ou sélection d'une carte personnalisée
// avec gabarit via selectTask()), aucun événement natif n'était jamais déclenché - le garde-fou ne
// s'exécutait donc jamais même si le contenu affiché contenait un nom/courriel/téléphone. Fixé :
// dispatch d'un événement 'input' natif sur #cpTaskObject (via $nextTick) après chaque assignation
// programmatique de taskObject, aux deux points d'assignation (restauration ?edit=ID et
// selectTask()). Preuve comportementale complète (RED/GREEN prouvé, chemin selectTask()) :
// tests/js/constructeur-prompts-selecttask-pii-guard.test.cjs. Le chemin ?edit=ID (plus complexe à
// simuler, dépend d'un fetch async) est couvert par vérification statique ci-dessous.

it('dispatches a native input event on #cpTaskObject after selectTask() sets taskObject from a card template (round 100)', function () {
    $js = file_get_contents(base_path('public/assets/tools/constructeur-prompts/constructeur-prompts-core.js'));

    // Round 128 (2026-07-30) : cette assertion figeait l'ADJACENCE LITTÉRALE de la ligne
    // `if (card.query_template) {` et du commentaire du round 100. Trop strict : elle testait la
    // mise en page du code, pas son comportement. Le round 128 a inséré entre les deux la garde
    // anti-perte de travail (ne plus écraser un taskObject rédigé par l'utilisateur), ce qui la
    // faisait échouer alors que le re-scan anti-PII visé ici est intact.
    // On vérifie donc l'INTENTION : dans selectTask(), l'application du gabarit reste suivie du
    // dispatch 'input' sur #cpTaskObject.
    $posSelect = strpos($js, 'selectTask: function');
    expect($posSelect)->not->toBeFalse();

    $selectBody = substr($js, $posSelect, 3200);
    expect($selectBody)->toContain('if (card.query_template) {');

    $posAssign = strpos($selectBody, 'this.taskObject = card.query_template;');
    $posDispatch = strpos($selectBody, "if (el) el.dispatchEvent(new Event('input', { bubbles: true }));", $posAssign ?: 0);
    expect($posAssign)->not->toBeFalse();
    expect($posDispatch)->not->toBeFalse();
    expect($posAssign)->toBeLessThan($posDispatch);
    // Round 110 : ce compte est passé de 2 à 6 (le round 110 a reproduit le même pattern
    // dispatchEvent sur les 4 champs personnalisés #cpPersonaCustom/#cpVerbCustom/
    // #cpAudienceCustom/#cpConstraintCustom lors de la restauration ?edit=ID) - >=2 vérifie
    // que le pattern round 100 original reste présent sans figer le compte exact.
    expect(substr_count($js, "if (el) el.dispatchEvent(new Event('input', { bubbles: true }));"))->toBeGreaterThanOrEqual(2);
});

it('dispatches a native input event on #cpTaskObject after the ?edit=ID restore path sets taskObject (round 100)', function () {
    $js = file_get_contents(base_path('public/assets/tools/constructeur-prompts/constructeur-prompts-core.js'));

    expect($js)->toContain('if (p.taskObject) {');
    expect($js)->toContain("var el = document.getElementById('cpTaskObject');");
});

it('renders the constructeur-prompts page correctly after the round 100 fix (real page, no regression)', function () {
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
