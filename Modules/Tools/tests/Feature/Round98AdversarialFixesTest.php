<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Tools\Models\Tool;

uses(Tests\TestCase::class, RefreshDatabase::class);

// Round 98 (2026-07-27) : passe adversariale fraîche après le lot round 97 (garde de ré-entrance
// importLocalCustomCards()). 1 manque réel corrigé :
//
// public/assets/tools/constructeur-prompts/constructeur-prompts-core.js - addToHistory() remettait
// self._editingId à null après une mise à jour (PUT) réussie d'un prompt existant. Le libellé du
// bouton redevient alors "Sauvegarder" (piloté par _editingId, blade ligne 37). Si l'utilisateur
// reste sur la même page et clique de nouveau sur le bouton (sans recharger, sans revenir à
// ?edit=ID), addToHistory() faisait cette fois un POST /api/prompts au lieu d'un PUT
// /api/prompts/{id} - un VRAI doublon du prompt était créé en base au lieu de mettre à jour
// l'enregistrement déjà édité. Fixé : _editingId reste sur l'écho serveur (pid) après un update
// réussi, au lieu d'être effacé - le mode "mise à jour" persiste tant que l'utilisateur reste sur
// cette session d'édition. Preuve comportementale complète (RED/GREEN prouvé) :
// tests/js/constructeur-prompts-doubleupdatesave.test.cjs.

it('keeps _editingId set to the server echo (pid) after a successful update, instead of resetting to null (round 98)', function () {
    $js = file_get_contents(base_path('public/assets/tools/constructeur-prompts/constructeur-prompts-core.js'));

    expect($js)->toContain('self._editingId = pid;');
    expect($js)->not->toContain('self._editingId = null;');
});

it('renders the constructeur-prompts page correctly after the round 98 fix (real page, no regression)', function () {
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
