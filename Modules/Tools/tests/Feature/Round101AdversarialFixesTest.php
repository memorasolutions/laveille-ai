<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Tools\Models\Tool;

uses(Tests\TestCase::class, RefreshDatabase::class);

// Round 101 (2026-07-27) : passe adversariale fraîche après le lot round 100 (garde-fou PII sur
// taskObject rempli programmatiquement). 1 manque réel corrigé :
//
// public/assets/tools/constructeur-prompts/constructeur-prompts-core.js - selectedTask (id de la
// carte d'objectif choisie à l'étape 1) n'était jamais inclus dans wizardParams(), donc jamais
// persisté en base ni restauré à la réouverture d'un prompt sauvegardé (?edit=ID). Résultat : pour
// TOUT prompt existant rouvert en édition, le badge « Objectif choisi » et la mise en surbrillance
// de la carte à l'étape 1 affichaient systématiquement « Autre chose », quelle que soit la carte
// réellement utilisée à la création. Fixé : selectedTask ajouté à wizardParams() (sauvegarde) et
// restauré depuis p.selectedTask AVANT le repli 'autre' dans le bloc ?edit=ID de init() - le repli
// 'autre' reste actif pour les prompts sauvegardés AVANT ce fix (aucun selectedTask en base).
// Preuve comportementale complète (RED/GREEN prouvé) :
// tests/js/constructeur-prompts-selectedtask-roundtrip.test.cjs.

it('includes selectedTask in wizardParams() so it gets persisted with the prompt (round 101)', function () {
    $js = file_get_contents(base_path('public/assets/tools/constructeur-prompts/constructeur-prompts-core.js'));

    expect($js)->toContain('return { selectedTask: this.selectedTask,');
});

it('restores selectedTask from p.selectedTask before the \'autre\' fallback in the ?edit=ID restore path (round 101)', function () {
    $js = file_get_contents(base_path('public/assets/tools/constructeur-prompts/constructeur-prompts-core.js'));

    expect($js)->toContain('if (p.selectedTask) self.selectedTask = p.selectedTask;');
    expect($js)->toContain("self.selectedTask = self.selectedTask || 'autre';");
});

it('renders the constructeur-prompts page correctly after the round 101 fix (real page, no regression)', function () {
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
