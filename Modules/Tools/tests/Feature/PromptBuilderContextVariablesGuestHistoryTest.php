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

it('exposes the contextInfo help text via window.promptBuilderConfig.helps, mentioning the "En faire un espace à remplir" gesture', function () {
    makePromptBuilderTool();
    $user = User::factory()->create();

    $html = $this->actingAs($user)->get('/outils/constructeur-prompts')->assertOk()->getContent();

    expect($html)->toContain('contextInfo');
    // Espaces à remplir (tâches 1660-1665, 2026-08-07) : les 2 textes d'aide (taskObject ET
    // contextInfo) mentionnaient auparavant la syntaxe {{sujet}} (#1593b) - remplacée par le geste
    // de sélection, conforme à la RÈGLE D'OR de cette fonctionnalité (jamais de syntaxe visible).
    // Les {{...}} restent fonctionnels mais ne sont plus mis de l'avant dans l'aide.
    expect(substr_count($html, 'En faire un espace à remplir'))->toBeGreaterThanOrEqual(2);
    expect($html)->not->toContain('{{sujet}}');
});

// === #1593b : variables réutilisables {{...}} ===

it('renders the "Remplis tes espaces" fill-in panel (ex-"Remplis tes variables", extended tâches 1660-1665), reactive on fillableSpaces and promptVariables', function () {
    makePromptBuilderTool();
    $user = User::factory()->create();

    $html = $this->actingAs($user)->get('/outils/constructeur-prompts')->assertOk()->getContent();

    // Le bloc a été RENOMMÉ (tâches 1660-1665) : il liste désormais les espaces à remplir
    // (fillableSpaces) AVANT les variables {{...}} historiques, jamais l'inverse - mécanique
    // {{}} intacte (x-for/x-model inchangés).
    expect($html)->toContain('x-show="fillableSpaces.length > 0 || promptVariables.length > 0"');
    expect($html)->toContain('x-for="(sp, spIdx) in fillableSpaces"');
    expect($html)->toContain('x-for="v in promptVariables"');
    expect($html)->toContain('x-model="varValues[v]"');
    expect($html)->toContain('Remplis tes espaces');
    expect($html)->not->toContain('Remplis tes variables');
});

// === Espaces à remplir (tâches 1660-1665, panel multi-IA 5 rounds, 2026-08-07) ===

it('renders the space creation gesture (selection bubble + "+" button) on both cpTaskObject and cpContextInfo', function () {
    makePromptBuilderTool();
    $user = User::factory()->create();

    $html = $this->actingAs($user)->get('/outils/constructeur-prompts')->assertOk()->getContent();

    // Geste A (sélection) : bulle inline sous chaque champ, filtrée par fieldId.
    expect($html)->toContain("handleSpaceFieldSelect(\$event)");
    expect($html)->toContain("createSpaceFromSelection()");
    expect($html)->toContain("spaceBubble.fieldId === 'cpTaskObject'");
    expect($html)->toContain("spaceBubble.fieldId === 'cpContextInfo'");
    expect($html)->toContain('En faire un espace à remplir');
});

it('renders the "Tu pourras changer" band of space pills below the context field, tracking every space entry', function () {
    makePromptBuilderTool();
    $user = User::factory()->create();

    $html = $this->actingAs($user)->get('/outils/constructeur-prompts')->assertOk()->getContent();

    expect($html)->toContain("Tes espaces à remplir - chaque pastille est un bout de texte de ta demande :");
    expect($html)->toContain('x-for="(sp, spIdx) in spaces"');
    expect($html)->toContain('removeSpace(spIdx)');
    expect($html)->toContain('startRenameSpace(spIdx)');
    expect($html)->toContain('commitPendingSpaceRename(spIdx)');
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
    //
    // ACTION : délimiter le getter, au lieu de mesurer une distance depuis son en-tête.
    // RAISON (2026-09-04, ticket #2249) : la forme précédente était
    // /get wizardParams\(\)[\s\S]{0,3000}contextInfo: this\.contextInfo/ - un quantificateur
    // PCRE SANS le flag /u, qui compte donc des OCTETS et non des caractères (chaque accent
    // français en vaut deux). Ajouter un simple commentaire dans le getter poussait contextInfo
    // au-delà des 3000 octets et faisait rougir ce test, alors que le champ testé était intact.
    // Le correctif du 2026-09-04 avait dû déplacer un commentaire APRÈS le `return` pour le
    // contourner : c'est le code qui se tordait pour satisfaire la mesure. On vérifie désormais
    // l'intention réelle - contextInfo figure dans le corps du getter - sans aucune borne
    // arbitraire.
    // L'ancre porte l'accolade ouvrante : « get wizardParams() { ». Sans elle, on attrape aussi
    // la mention entre accents graves qui figure dans le commentaire ci-dessous, dans le JS.
    $debutGetter = strpos($src, 'get wizardParams() {');
    expect($debutGetter)->not->toBeFalse();

    // On isole LA LIGNE du `return` - et rien d'autre. Mesuré le 2026-09-04 : une première
    // version de ce correctif lisait tout le CORPS du getter, commentaires compris ; comme un
    // commentaire y cite littéralement `contextInfo: this.contextInfo`, le test restait VERT
    // même après suppression du vrai champ. Un test qui lit un commentaire ne teste rien.
    $posReturn = strpos($src, 'return {', $debutGetter);
    expect($posReturn)->not->toBeFalse();

    $finLigne = strpos($src, "\n", $posReturn);
    $ligneReturn = substr($src, $posReturn, $finLigne - $posReturn);

    expect($ligneReturn)->toContain('contextInfo: this.contextInfo');
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
