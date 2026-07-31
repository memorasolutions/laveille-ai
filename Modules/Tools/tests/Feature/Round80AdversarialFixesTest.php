<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Tools\Models\SavedPrompt;

uses(Tests\TestCase::class, RefreshDatabase::class);

// Round 80 (2026-07-27) : passe adversariale fraîche après le lot round 79 (promptSummary i18n).
// 1 manque réel corrigé :
//
// 1. SavedPromptController::duplicate() - le préfixe 'Copie de ' du nom du prompt dupliqué était
//    un littéral français concaténé, jamais passé par __(), absent de lang/en.json. Fixé en
//    __('Copie de :name', ['name' => $original->name]) + clé "Copie de :name" => "Copy of :name".
//
// 3 findings du round 80 REJETÉS (pas un manque, re-signalement d'une décision déjà prise et
// documentée au round 74) : $defaultPersonas/$defaultVerbs/$defaultAudiences (blade ~761-763)
// jamais enveloppés de __() ; conséquence sur promptSummary() (labels non traduits injectés) ;
// conjonction " et " codée en dur dans audienceText() (JS ~206-207). Les 3 tracent à la MÊME
// décision architecturale round 74 : ces valeurs sont injectées BRUTES dans le prompt réellement
// généré (get prompt()), lequel reste TOUJOURS en français par design, quel que soit le locale du
// site - les traduire casserait la grammaire du prompt généré (mélange FR/EN). Le round 79 a bien
// traduit la TRAME de promptSummary() (les libellés lus par l'humain), pas les valeurs
// personas/verbes/audiences elles-mêmes, qui restent volontairement françaises - cohérent avec le
// round 74, pas une régression. Erreur de méthodologie du superviseur (briefing round 80 n'a pas
// rappelé cette décision, contrairement aux rounds 77/78 sur les autres verrous), pas un bug réel.

it('has an English translation for the "Copie de :name" saved-prompt duplicate prefix (round 80)', function () {
    $en = json_decode(file_get_contents(lang_path('en.json')), true);

    expect($en)->toHaveKey('Copie de :name');
    expect($en['Copie de :name'])->toBe('Copy of :name');
});

it('duplicates a saved prompt with the EN-translated prefix when locale is en (round 80)', function () {
    $user = User::factory()->create();

    $prompt = SavedPrompt::create([
        'user_id' => $user->id,
        'name' => 'My great prompt',
        'prompt_text' => 'Prompt text',
        'params' => ['verb' => 'write'],
        'tags' => ['seo'],
        'is_public' => false,
        'is_favorite' => false,
    ]);

    $response = $this->actingAs($user)
        ->withSession(['locale' => 'en'])
        ->postJson("/api/prompts/{$prompt->public_id}/duplicate");

    $response->assertCreated();
    $response->assertJson([
        'name' => 'Copy of My great prompt',
    ]);
});

it('still duplicates with the French prefix when locale is fr, unaffected by the i18n fix (round 80)', function () {
    $user = User::factory()->create();

    $prompt = SavedPrompt::create([
        'user_id' => $user->id,
        'name' => 'Mon prompt',
        'prompt_text' => 'Texte',
        'params' => ['verb' => 'rédiger'],
        'tags' => ['seo'],
        'is_public' => false,
        'is_favorite' => false,
    ]);

    $response = $this->actingAs($user)
        ->withSession(['locale' => 'fr'])
        ->postJson("/api/prompts/{$prompt->public_id}/duplicate");

    $response->assertCreated();
    $response->assertJson([
        'name' => 'Copie de Mon prompt',
    ]);
});
