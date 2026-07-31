<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Tools\Models\SavedPrompt;

uses(Tests\TestCase::class, RefreshDatabase::class);

// Round 115 (2026-07-27) : passe adversariale fraîche après le lot round 114. 1 manque réel
// corrigé - le correctif du round 113 était NEUTRALISÉ par l'état replié de l'accordéon :
//
// Le panneau « Mon profil » (/user/prompts) démarre REPLIÉ dès qu'un profil existe déjà
// (index.blade.php : x-data="{ open: {{ empty($promptProfile) ? 'true' : 'false' }} }") - c'est
// exactement le cas où le scan au chargement (round 113) a du contenu à analyser. showBanner()
// insérait alors la bannière role="alert" via insertBefore dans un conteneur x-show="open" à
// display:none : invisible à l'oeil ET absente de l'arbre d'accessibilité (un role="alert" sous
// display:none n'est jamais annoncé par un lecteur d'écran). Aucun indicateur sur le bouton du
// toggle non plus. Le round 113 s'exécutait donc mécaniquement (le scan avait bien lieu) sans
// jamais produire l'effet promis (l'utilisateur voit l'avertissement), sauf s'il rouvrait
// l'accordéon par hasard pour une autre raison.
//
// Fixé : showBanner() émet un CustomEvent 'profile-pii-detected' sur window, et l'accordéon
// l'écoute (@profile-pii-detected.window="open = true") pour se déplier automatiquement.

it('dispatches a PII-detected event so the collapsed profile panel can reveal the banner (round 115)', function () {
    $js = file_get_contents(public_path('assets/tools/constructeur-prompts/profile-anon-guard.js'));

    expect($js)->toContain("window.dispatchEvent(new CustomEvent('profile-pii-detected'));");
    // L'émission doit être DANS showBanner() (donc après l'insertion), pas ailleurs.
    $posInsert = strpos($js, 'insertBefore(banner, fieldElement)');
    $posEvent = strpos($js, "new CustomEvent('profile-pii-detected')");
    expect($posInsert)->toBeLessThan($posEvent);
});

it('makes the profile accordion listen to the PII event to auto-expand (round 115)', function () {
    $blade = file_get_contents(base_path('Modules/Tools/resources/views/user/prompts/index.blade.php'));

    expect($blade)->toContain('@profile-pii-detected.window="open = true"');
    // La garde d'origine (replié si un profil existe déjà) doit rester intacte.
    expect($blade)->toContain("x-data=\"{ open: {{ empty(\$promptProfile) ? 'true' : 'false' }} }\"");
});

it('renders /user/prompts correctly after the round 115 fix (real page, no regression)', function () {
    $user = User::factory()->create();

    SavedPrompt::create([
        'user_id' => $user->id,
        'name' => 'Prompt test round 115',
        'prompt_text' => 'Contenu de test',
        'tags' => ['test-round-115'],
    ]);

    $this->actingAs($user)->get('/user/prompts')->assertOk();
});
