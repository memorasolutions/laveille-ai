<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Tools\Models\SavedPrompt;
use Modules\Tools\Models\Tool;
use Tests\TestCase;

uses(Tests\TestCase::class, RefreshDatabase::class);

// #1593a/#1593b/#1580 (2026-08-07) : couverture des 3 livrables incrémentaux du constructeur de
// prompts (champ « Contexte additionnel », variables réutilisables {{...}}, rétention locale
// invités). Style d'inspection de source (file_get_contents + assertSee/toContain), même
// convention que le reste de la suite constructeur-prompts (Round*AdversarialFixesTest.php) - la
// BD locale de test ne permet pas d'exécuter le JS Alpine réel côté serveur, donc on vérifie ici
// la PRÉSENCE des marqueurs clés du Blade ; le comportement RÉEL de la logique JS (génération du
// prompt, désérialisation, localStorage) est couvert par les tests tests/js/constructeur-prompts-
// contextinfo.test.cjs, -variables.test.cjs et -guesthistory.test.cjs (exécutent le vrai composant
// Alpine dans un mini-DOM simulé).

function makePromptBuilderTool(): void
{
    Tool::query()->updateOrInsert(
        ['slug' => 'constructeur-prompts'],
        [
            'name' => 'Constructeur de prompts',
            'description' => 'Test',
            'icon' => '✨',
            'is_active' => true,
            'is_under_construction' => false,
            'category' => 'productivite',
        ]
    );
}

// === #1593a : champ « Contexte additionnel » ===

it('renders the additional context field, distinct from the task field, with its help button', function () {
    makePromptBuilderTool();
    $user = User::factory()->create();

    $html = $this->actingAs($user)->get('/outils/constructeur-prompts')->assertOk()->getContent();

    expect($html)->toContain('id="cpContextField"');
    expect($html)->toContain('id="cpContextInfo"');
    expect($html)->toContain('x-model="contextInfo"');
    // Le champ tâche existant reste inchangé (jamais confondu avec le nouveau champ contexte).
    expect($html)->toContain('id="cpTaskObject"');
    expect($html)->toContain('x-model="taskObject"');
    // Bouton d'aide DRY réutilisé (composant x-tools::help-btn existant), même pattern que les
    // autres champs (persona, cadreStrict...).
    expect($html)->toContain('showHelp.contextInfo');
    expect($html)->toContain('helps.contextInfo');
});

it('exposes the contextInfo help text via window.promptBuilderConfig.helps, mentioning {{variable}}', function () {
    makePromptBuilderTool();
    $user = User::factory()->create();

    $html = $this->actingAs($user)->get('/outils/constructeur-prompts')->assertOk()->getContent();

    expect($html)->toContain('contextInfo');
    // Les deux textes d'aide (taskObject ET contextInfo) mentionnent la syntaxe {{sujet}} -
    // c'est le canal d'apprentissage de la fonctionnalité "variables réutilisables" (#1593b).
    expect(substr_count($html, '{{sujet}}'))->toBeGreaterThanOrEqual(1);
});

// === #1593b : variables réutilisables {{...}} ===

it('renders the "Remplis tes variables" fill-in panel, reactive on promptVariables', function () {
    makePromptBuilderTool();
    $user = User::factory()->create();

    $html = $this->actingAs($user)->get('/outils/constructeur-prompts')->assertOk()->getContent();

    expect($html)->toContain('x-show="promptVariables.length > 0"');
    expect($html)->toContain('x-for="v in promptVariables"');
    expect($html)->toContain('x-model="varValues[v]"');
    expect($html)->toContain('Remplis tes variables');
});

// === #1580 : rétention locale invités ===

it('renders the guest-only "on this device" history block, gated on !isAuthenticated', function () {
    makePromptBuilderTool();
    $user = User::factory()->create();

    $html = $this->actingAs($user)->get('/outils/constructeur-prompts')->assertOk()->getContent();

    expect($html)->toContain('x-if="!isAuthenticated && guestHistory.length > 0"');
    expect($html)->toContain('loadGuestHistoryEntry(gi)');
    expect($html)->toContain('deleteGuestHistoryEntry(gi)');
    expect($html)->toContain('clearGuestHistory()');
    // Mention de confidentialité explicite, une ligne, exigée par le plan.
    expect($html)->toContain('Conservés uniquement dans ton navigateur, jamais envoyés au serveur.');
});

it('uses a versioned localStorage key (cpGuestHistory_v1) distinct from the existing pb_history mechanism', function () {
    $src = file_get_contents(base_path('public/assets/tools/constructeur-prompts/constructeur-prompts-core.js'));

    expect($src)->toContain("_guestHistoryKey: 'cpGuestHistory_v1'");
    // Le mécanisme existant (pb_history, lié au bouton "Sauvegarder" qui exige un compte) n'est
    // JAMAIS touché par cette fonctionnalité - non-régression explicite demandée par le plan.
    expect($src)->toContain("localStorage.getItem('pb_history')");
    expect($src)->toContain('_recordGuestHistory');
    expect($src)->toContain('_loadGuestHistory');
});

// === Impact vérifié : sérialisation (wizardParams / "getState") et permalien/remix ===

it('includes contextInfo in the wizardParams serialization object used by save/edit/remix', function () {
    $src = file_get_contents(base_path('public/assets/tools/constructeur-prompts/constructeur-prompts-core.js'));

    // wizardParams est l'objet de sérialisation réellement envoyé à l'API (voir addToHistory(),
    // body: { params: this.wizardParams }) - contextInfo doit y figurer comme tous les autres
    // champs texte (examples, constraintCustom...), sinon il se perd à la sauvegarde.
    expect($src)->toMatch('/get wizardParams\(\)[\s\S]{0,3000}contextInfo: this\.contextInfo/');
});

it('lets a saved prompt carrying contextInfo survive the public remix-data round trip unchanged', function () {
    makePromptBuilderTool();
    $user = User::factory()->create();

    $prompt = SavedPrompt::create([
        'user_id' => $user->id,
        'name' => 'Prompt avec contexte',
        'prompt_text' => 'Rédige un plan marketing.',
        'params' => ['verb' => 'Rédige', 'taskObject' => 'un plan', 'contextInfo' => "On a déjà essayé une version plus formelle."],
        'is_public' => true,
    ]);

    // Le contrôleur public (PublicPromptController::remixData) passe `params` intégralement, sans
    // liste blanche de clés - contextInfo (nouveau champ) transite donc sans aucun changement
    // backend requis, exactement comme examples/constraintCustom avant lui.
    $response = $this->getJson('/p/'.$prompt->public_id.'/remix-data');

    $response->assertOk();
    $response->assertJsonPath('params.contextInfo', 'On a déjà essayé une version plus formelle.');
});

it('lets a saved prompt carrying contextInfo survive the owner edit (?edit=ID) API round trip unchanged', function () {
    makePromptBuilderTool();
    $user = User::factory()->create();

    $prompt = SavedPrompt::create([
        'user_id' => $user->id,
        'name' => 'Prompt propriétaire',
        'prompt_text' => 'Texte',
        'params' => ['verb' => 'Rédige', 'taskObject' => 'un plan', 'contextInfo' => 'Contexte du propriétaire.'],
        'is_public' => false,
    ]);

    $response = $this->actingAs($user)->getJson('/api/prompts/'.$prompt->public_id);

    $response->assertOk();
    $response->assertJsonPath('params.contextInfo', 'Contexte du propriétaire.');
});
