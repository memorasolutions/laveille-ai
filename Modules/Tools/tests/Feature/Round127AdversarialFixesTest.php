<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Tools\Models\Tool;

uses(Tests\TestCase::class, RefreshDatabase::class);

// Round 127 (2026-07-30) : référence pendante vers une carte de démarrage supprimée.
//
// Chaîne complète du défaut, vérifiée maillon par maillon :
//   1. Le badge est affiché sur `x-show="selectedTask"` - la seule présence d'une chaîne d'id,
//      jamais le fait qu'elle désigne encore quelque chose.
//   2. selectedTaskLabel parcourait taskCards puis customCards et renvoyait '' en dernier recours.
//   3. La restauration ?edit=ID recopie `p.selectedTask` tel quel ; le repli `|| 'autre'` ne joue
//      que sur une valeur VIDE, donc un id périmé mais non vide passe intact.
//   4. deleteCustomCard ne nettoie que `this.selectedTask` de la session en cours - rien ne relie
//      la suppression aux SavedPrompt déjà persistés qui référencent cette carte (vérifié :
//      aucun nettoyage en cascade dans SavedPromptController ni dans ToolPreferenceController).
//
// Résultat : « Objectif choisi : » suivi d'un <strong> vide, sans explication. Non bloquant
// (isValid ne dépend pas de selectedTask) mais c'est un artefact visible et reproductible.
//
// Le correctif porte sur le LIBELLÉ, pas sur la donnée : on ne réécrit pas selectedTask, car cela
// exigerait de valider un id contre une liste chargée en asynchrone. La garde customCardsLoaded
// (drapeau du round 118) est le coeur du correctif - sans elle, un id valide serait déclaré
// supprimé pendant le chargement réseau puis se corrigerait seul, soit une accusation clignotante
// pire que le vide d'origine.

it('names the deleted card instead of rendering an empty badge (round 127)', function () {
    $js = file_get_contents(public_path('assets/tools/constructeur-prompts/constructeur-prompts-core.js'));

    $pos = strpos($js, 'get selectedTaskLabel()');
    expect($pos)->not->toBeFalse();

    $body = substr($js, $pos, 2200);
    expect($body)->toContain("i18nLabel.taskCardDeleted || 'Objectif supprimé'");
});

it('stays silent while the custom cards are still loading (round 127, no false accusation)', function () {
    $js = file_get_contents(public_path('assets/tools/constructeur-prompts/constructeur-prompts-core.js'));

    $pos = strpos($js, 'get selectedTaskLabel()');
    $body = substr($js, $pos, 2200);

    // La garde doit PRÉCÉDER le message : sinon un identifiant valide serait déclaré supprimé
    // tant que la requête réseau n'a pas répondu.
    $posGuard = strpos($body, 'if (!this.customCardsLoaded) return');
    $posMsg = strpos($body, 'taskCardDeleted');

    expect($posGuard)->not->toBeFalse();
    expect($posMsg)->not->toBeFalse();
    expect($posGuard)->toBeLessThan($posMsg);
});

it('exposes the label as a translatable key (round 127)', function () {
    $blade = file_get_contents(base_path('Modules/Tools/resources/views/public/tools/constructeur-prompts.blade.php'));

    expect($blade)->toContain('taskCardDeleted: @json(');
});

it('keeps resolving real cards from both sources (round 127 non-regression)', function () {
    $js = file_get_contents(public_path('assets/tools/constructeur-prompts/constructeur-prompts-core.js'));

    $pos = strpos($js, 'get selectedTaskLabel()');
    $body = substr($js, $pos, 2200);

    // Les 2 boucles d'origine restent intactes : le correctif n'ajoute qu'un dernier recours.
    expect($body)->toContain('return this.taskCards[i].label;');
    expect($body)->toContain('return this.customCards[j].title;');
});

it('renders the wizard after the round 127 fix (real page)', function () {
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
