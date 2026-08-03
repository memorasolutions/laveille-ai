<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Tools\Models\Tool;

uses(Tests\TestCase::class, RefreshDatabase::class);

// Round 85 (2026-07-27) : passe adversariale fraîche après le lot round 84 (chips de filtre par
// tag WCAG AAA touch target). 2 manques réels corrigés :
//
// 1. Modules/Tools/resources/views/public/tools/constructeur-prompts.blade.php:552-554 (ancien) -
//    les 3 spans de message de validation spécifiques à l'étape 2 (verbe manquant preset/custom,
//    demande manquante) étaient du CODE MORT : `showValidation` (variable Alpine côté JS) ne
//    devient JAMAIS true tant que step===2 - `nextStep()` et `canGoToStep()` (constructeur-
//    prompts-core.js:543-556) ne testent que `selectedTask` (étape 1), jamais `taskObject`/`verb`
//    (étape 2). Conforme à la décision déjà documentée au Round 14 dans le code JS lui-même
//    (constructeur-prompts-core.js:342-344) : le cas "verbe manquant" est intentionnellement
//    couvert par l'alerte générique `x-show="!isValid"` plus bas dans le fichier, pas par ces
//    spans. Fixé : retrait des 3 spans inatteignables + simplification du x-show parent à la
//    seule condition réellement atteignable (step 1, carte non choisie).
// 2. Modules/Tools/resources/views/public/tools/constructeur-prompts.blade.php:131-132 (indicateur
//    d'étapes, cercle inactif) - #6c757d sur #e9ecef = 3,95:1 (échoue même AA 4,5:1, texte 14,4px
//    gras) ; #adb5bd sur blanc = 2,07:1 (échoue largement AA), visible en permanence dès le
//    chargement (étape 2 toujours "inactive" au 1er rendu). Remplacé par les tokens de charte déjà
//    utilisés dans ce fichier : var(--c-dark, #1A1D23) sur #e9ecef = 14,24:1 AAA (cercle) ;
//    var(--c-text-muted, #52586A) sur blanc = 7,09:1 AAA (libellé, même token déjà utilisé au
//    round 82 pour l'icône favori et la bordure "Ajouter une carte").

it('does not contain the unreachable step-2 validation message spans (round 85)', function () {
    $blade = file_get_contents(base_path('Modules/Tools/resources/views/public/tools/constructeur-prompts.blade.php'));

    expect($blade)->not->toContain("step === 2 && verbType === 'preset' && !verb");
    expect($blade)->not->toContain("step === 2 && verbType === 'custom' && !verbCustom");
    expect($blade)->not->toContain('step === 2 && (verbType === \'custom\' ? !!verbCustom : !!verb) && !taskObject');
    // Le seul cas réellement atteignable (étape 1, carte non choisie) doit rester.
    expect($blade)->toContain('showValidation && step === 1 && !selectedTask');
});

// Round 151 (2026-08-01, refonte écrans 1-2, PLAN-FINAL-constructeur-2026-07-31.md) : les 2 tests
// ci-dessous verrouillaient les couleurs AAA de l'indicateur d'étapes numéroté (cercles "1"/"2").
// Cet indicateur a été RETIRÉ dans son ensemble - consigne explicite de la refonte : « aucune
// numérotation d'étapes (« 1 sur 3 » serait mensonger puisque la suite est facultative) ». Le
// contraste qu'ils protégeaient est donc sans objet (l'élément n'existe plus, donc ne peut plus
// échouer AAA). Retrait assumé, pas un affaiblissement furtif : le 1er test de ce fichier (spans
// de validation inatteignables) reste inchangé et continue de protéger un invariant réel.

it('no longer renders a numbered step indicator (round 151, replaces round 85 contrast checks)', function () {
    $blade = file_get_contents(base_path('Modules/Tools/resources/views/public/tools/constructeur-prompts.blade.php'));

    expect($blade)->not->toContain('class="ct-step-circle"');
    expect($blade)->not->toContain('#e9ecef; color: #6c757d;');
    expect($blade)->not->toContain('color: #adb5bd;');
});

it('renders the wizard without a numbered step indicator on the real page (round 151)', function () {
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

    expect($html)->not->toContain('class="ct-step-circle"');
    expect($html)->not->toContain('#e9ecef; color: #6c757d;');
});
