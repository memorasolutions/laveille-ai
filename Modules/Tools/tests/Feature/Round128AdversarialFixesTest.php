<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Tools\Models\Tool;

uses(Tests\TestCase::class, RefreshDatabase::class);

// Round 128 (2026-07-30) : perte de travail utilisateur, silencieuse et irréversible.
//
// selectTask() faisait `this.taskObject = card.query_template;` - une affectation DIRECTE, sans
// aucune garde. Or rien n'empêche de revenir à l'étape 1 : « Changer d'objectif » appelle
// goToStep(1) sans confirmation ni instantané, et chaque carte reste cliquable.
//
// Scénario : l'utilisateur choisit une carte personnalisée, le gabarit remplit le champ, puis il
// rédige ou colle son propre texte à l'étape 2 (parfois des centaines de mots). Il revient à
// l'étape 1 pour comparer une autre carte - ou reclique la MÊME par erreur - et tout son texte est
// remplacé par le gabarit brut. Aucun avertissement, aucune annulation.
//
// Aucun mécanisme compensatoire n'existait : ni confirmation, ni instantané, ni détection d'état
// non enregistré dans goToStep/canGoToStep. Et le test JS dédié partait TOUJOURS d'un taskObject
// vide, donc le cas « champ déjà rempli » n'était jamais exercé.
//
// Correctif : on ne remplace que si le champ ne contient AUCUN travail - vide, ou strictement égal
// au gabarit d'une carte connue (donc un gabarit jamais retouché, dont le remplacement ne détruit
// rien). Sinon on conserve le texte et on le DIT, via une notice neutre : sans ce retour visible,
// l'utilisateur croirait la carte cassée.
//
// Piège évité : la première version du correctif supprimait le garde extérieur
// `if (card.query_template)`. Les cartes SYSTÈME n'ont pas de gabarit - le champ aurait été écrasé
// avec undefined. Le garde est conservé et verrouillé par un test ci-dessous.

it('never overwrites text the user actually wrote (round 128)', function () {
    $js = file_get_contents(public_path('assets/tools/constructeur-prompts/constructeur-prompts-core.js'));

    $pos = strpos($js, 'selectTask: function');
    expect($pos)->not->toBeFalse();

    $body = substr($js, $pos, 3200);
    expect($body)->toContain("var current = (this.taskObject || '').trim();");
    expect($body)->toContain('if (isUntouchedTemplate) {');
    expect($body)->toContain('this._showTaskNotice();');
});

it('still applies the template when the field holds no work (round 128)', function () {
    $js = file_get_contents(public_path('assets/tools/constructeur-prompts/constructeur-prompts-core.js'));

    $pos = strpos($js, 'selectTask: function');
    $body = substr($js, $pos, 3200);

    // Champ vide OU gabarit non retouché d'une carte connue : le remplacement ne détruit rien.
    expect($body)->toContain("var isUntouchedTemplate = current === '';");
    expect($body)->toContain('this.customCards.concat(this.taskCards)');
    expect($body)->toContain('this.taskObject = card.query_template;');
});

it('keeps the outer guard so system cards never blank the field (round 128)', function () {
    $js = file_get_contents(public_path('assets/tools/constructeur-prompts/constructeur-prompts-core.js'));

    $pos = strpos($js, 'selectTask: function');
    $body = substr($js, $pos, 3200);

    // Les cartes système n'ont pas de query_template : sans ce garde, taskObject deviendrait
    // undefined. C'est le défaut qu'a introduit la première version du correctif.
    $posGuard = strpos($body, 'if (card.query_template) {');
    $posAssign = strpos($body, 'this.taskObject = card.query_template;');

    expect($posGuard)->not->toBeFalse();
    expect($posAssign)->not->toBeFalse();
    expect($posGuard)->toBeLessThan($posAssign);
});

it('announces the preserved text without dressing it as an error (round 128)', function () {
    $js = file_get_contents(public_path('assets/tools/constructeur-prompts/constructeur-prompts-core.js'));
    $blade = file_get_contents(base_path('Modules/Tools/resources/views/public/tools/constructeur-prompts.blade.php'));

    expect($js)->toContain('_showTaskNotice: function(msg) {');
    expect($js)->toContain('i18n.taskTextKept');

    // Notice NEUTRE : role=status/aria-live=polite, jamais l'alerte rouge assertive de saveError.
    expect($blade)->toContain('x-text="taskNotice" role="status" aria-live="polite"');
    expect($blade)->toContain('taskTextKept: @json(');
    // La garde du round 19 sur saveError reste intacte.
    expect($blade)->toContain('x-text="saveError" role="alert" aria-live="assertive"');
});

it('keeps the round 100 PII rescan on the applied template (round 128 non-regression)', function () {
    $js = file_get_contents(public_path('assets/tools/constructeur-prompts/constructeur-prompts-core.js'));

    $pos = strpos($js, 'selectTask: function');
    $body = substr($js, $pos, 3200);

    // Le gabarit appliqué doit toujours être scanné par le garde-fou anti-PII.
    expect($body)->toContain("document.getElementById('cpTaskObject')");
    expect($body)->toContain("dispatchEvent(new Event('input', { bubbles: true }))");
});

it('renders the wizard after the round 128 fix (real page)', function () {
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
