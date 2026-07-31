<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Tools\Models\Tool;
use Tests\TestCase;

uses(Tests\TestCase::class, RefreshDatabase::class);

// Round 63 (2026-07-27) : passe adversariale fraîche sur constructeur-prompts, 1 fix réel.
//
// init() écrasait self.history sans garde dès que le GET initial /api/prompts résolvait - même
// classe de bug que les rounds 61 (persistCustomCards) et 62 (importLocalCustomCards), mais sur
// une TROISIÈME variable d'état (history), jamais protégée. Si l'utilisateur cliquait
// "Enregistrer" (addToHistory()) ou "Importer" (importLocalStorage()) AVANT que ce GET initial
// résolve (ex. ?edit=ID saute directement à l'étape 2, où Enregistrer est immédiatement
// cliquable), l'écho tardif du GET remplaçait intégralement self.history et effaçait
// silencieusement le prompt pourtant confirmé sauvegardé. Fix : flag historyLoaded (même pattern
// que customCardsLoaded, round 41) qui bloque addToHistory()/deletePrompt()/importLocalStorage()
// tant que le GET initial n'a pas résolu - côté JS (guard silencieux) ET côté blade (:disabled).

function makeRound63PromptTool(): void
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

it('disables Enregistrer and Importer until historyLoaded, to prevent the stale init() GET from erasing a freshly saved prompt (round 63)', function () {
    makeRound63PromptTool();
    $user = User::factory()->create();

    $html = $this->actingAs($user)->get('/outils/constructeur-prompts')->assertOk()->getContent();

    $saveBtnPos = strpos($html, '@click="addToHistory()"');
    expect($saveBtnPos)->not->toBeFalse();
    $saveBtnEnd = strpos($html, '>', $saveBtnPos);
    $saveBtnTag = substr($html, $saveBtnPos, $saveBtnEnd - $saveBtnPos);
    expect($saveBtnTag)->toContain(':disabled="!isValid || saving || !historyLoaded"');

    $importBtnPos = strpos($html, '@click="importLocalStorage()"');
    expect($importBtnPos)->not->toBeFalse();
    $importBtnEnd = strpos($html, '>', $importBtnPos);
    $importBtnTag = substr($html, $importBtnPos, $importBtnEnd - $importBtnPos);
    expect($importBtnTag)->toContain(':disabled="importing || !historyLoaded"');
});
