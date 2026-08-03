<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Tools\Models\Tool;
use Tests\TestCase;

uses(Tests\TestCase::class, RefreshDatabase::class);

// Round 65 (2026-07-27) : passe adversariale fraîche sur constructeur-prompts, 4 manques réels.
//
// 3 sites JS appelaient localStorage.getItem/setItem/removeItem SANS try/catch (voir tests JS
// tests/js/constructeur-prompts-storageerrors.test.cjs pour la couverture comportementale) : une
// exception (mode privé Safari, storage désactivé) pouvait écraser l'historique serveur ou
// bloquer en permanence le bouton "Importer". Ce test-ci couvre le 4e manque : les boutons
// désactivés par editLoading/historyLoaded/importing n'avaient aucune annonce ARIA - un
// utilisateur de lecteur d'écran ne savait pas pourquoi le bouton était désactivé pendant la
// brève fenêtre de chargement.

function makeRound65PromptTool(): void
{
    Tool::firstOrCreate(['slug' => 'constructeur-prompts'], [
        'name' => 'Constructeur de prompts',
        'description' => 'Test',
        'icon' => '✨',
        'is_active' => true,
        'is_under_construction' => false,
        'category' => 'productivite',
    ]);
}

it('exposes ARIA busy state and explanatory title on buttons disabled by editLoading/historyLoaded/importing (round 65)', function () {
    makeRound65PromptTool();
    $user = User::factory()->create();

    $html = $this->actingAs($user)->get('/outils/constructeur-prompts')->assertOk()->getContent();

    // Bouton "Sauvegarder"/"Mettre à jour" : aria-busy + title pilotés par historyLoaded.
    $saveBtnPos = strpos($html, '@click="addToHistory()"');
    expect($saveBtnPos)->not->toBeFalse();
    $saveBtnEnd = strpos($html, '>', $saveBtnPos);
    $saveBtnTag = substr($html, $saveBtnPos, $saveBtnEnd - $saveBtnPos);
    expect($saveBtnTag)->toContain(':aria-busy="saving || !historyLoaded');
    expect($saveBtnTag)->toContain(':title="!historyLoaded');

    // Bouton "Importer" (historique) : aria-busy + title pilotés par importing/historyLoaded.
    $importBtnPos = strpos($html, '@click="importLocalStorage()"');
    expect($importBtnPos)->not->toBeFalse();
    $importBtnEnd = strpos($html, '>', $importBtnPos);
    $importBtnTag = substr($html, $importBtnPos, $importBtnEnd - $importBtnPos);
    expect($importBtnTag)->toContain(':aria-busy="importing || !historyLoaded');
    expect($importBtnTag)->toContain(':title="!historyLoaded');

    // Groupe des cartes d'objectif (étape 1) : aria-busy sur le role="group" piloté par editLoading.
    $groupPos = strpos($html, 'aria-label="Choisir un objectif"');
    expect($groupPos)->not->toBeFalse();
    $groupTagStart = strrpos(substr($html, 0, $groupPos), '<div');
    $groupTagEnd = strpos($html, '>', $groupPos);
    $groupTag = substr($html, $groupTagStart, $groupTagEnd - $groupTagStart);
    expect($groupTag)->toContain(':aria-busy="editLoading');

    // Bouton de carte système : title explicatif piloté par editLoading.
    $cardBtnPos = strpos($html, '@click="selectTask(c)" :aria-pressed="selectedTask === c.id"');
    expect($cardBtnPos)->not->toBeFalse();
    $cardBtnTagStart = strrpos(substr($html, 0, $cardBtnPos), '<button');
    $cardBtnTag = substr($html, $cardBtnTagStart, $cardBtnPos - $cardBtnTagStart);
    expect($cardBtnTag)->toContain(':title="editLoading');

    // Textes d'annonce ARIA (status) présents en visually-hidden, référencés par leur contenu x-text.
    expect($html)->toContain('Chargement de votre historique de prompts en cours');
    expect($html)->toContain('Chargement du prompt en édition en cours');
});
