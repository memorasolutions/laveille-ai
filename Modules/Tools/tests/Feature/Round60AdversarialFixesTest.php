<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Tools\Models\Tool;
use Tests\TestCase;

uses(Tests\TestCase::class, RefreshDatabase::class);

// Round 60 (2026-07-27) : passe adversariale fraîche sur constructeur-prompts, 1 fix réel.
//
// La refonte v1.132.0 « objectif d'abord » (commit e07714fa) avait retiré le toggle radio
// preset/custom pour audienceType dans la section « Pour qui ? » de l'étape 2, alors que
// personaType et verbType ont chacun conservé le leur. Résultat : audienceType valait toujours
// 'preset' par défaut, et RIEN dans l'UI ne permettait plus de le faire passer à 'custom' pour un
// NOUVEAU prompt - le champ audienceCustom (toujours codé, toujours persisté côté JS/serveur)
// devenait définitivement inatteignable, sauf via ?edit=ID d'un ancien prompt déjà en mode
// custom. Une vraie régression fonctionnelle (pas juste un défaut UX), aucun test ne la
// couvrait. Fix : réintroduction du toggle radio audienceType, même pattern exact que
// personaType/verbType.

function makeRound60PromptTool(): void
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

it('renders a preset/custom radio toggle for audienceType, matching the personaType/verbType pattern (round 60)', function () {
    makeRound60PromptTool();
    $user = User::factory()->create();

    $html = $this->actingAs($user)->get('/outils/constructeur-prompts')->assertOk()->getContent();

    // Les 2 options radio doivent exister, comme pour personaType/verbType.
    expect($html)->toContain('name="audienceType" value="preset"');
    expect($html)->toContain('name="audienceType" value="custom"');
    expect($html)->toContain('x-model="audienceType"');

    // Les deux radios doivent apparaître AVANT les deux blocs conditionnels x-show qui en
    // dépendent (preset/custom), sinon le contrôle serait rendu hors contexte (ex. dans une
    // section sans rapport).
    $audienceBlockPos = strpos($html, 'id="cpAudienceBlock"');
    expect($audienceBlockPos)->not->toBeFalse();

    $presetRadioPos = strpos($html, 'name="audienceType" value="preset"', $audienceBlockPos);
    $customRadioPos = strpos($html, 'name="audienceType" value="custom"', $audienceBlockPos);
    $presetShowPos = strpos($html, "audienceType === 'preset'", $audienceBlockPos);
    $customShowPos = strpos($html, "audienceType === 'custom'", $audienceBlockPos);

    expect($presetRadioPos)->not->toBeFalse();
    expect($customRadioPos)->not->toBeFalse();
    expect($presetShowPos)->not->toBeFalse();
    expect($customShowPos)->not->toBeFalse();
    expect($presetRadioPos)->toBeLessThan($presetShowPos);
    expect($customRadioPos)->toBeLessThan($customShowPos);
});
