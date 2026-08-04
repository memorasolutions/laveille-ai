<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Tools\Models\SavedPrompt;

uses(Tests\TestCase::class, RefreshDatabase::class);

// Round 132 (2026-07-30) : renforcement du filet de sécurité de /user/prompts.
//
// CONSTAT à l'origine : la vingtaine de tests Round*AdversarialFixesTest qui protègent le script
// Alpine de cette page (promptsLibrary : saveTags, duplicatePrompt, deletePrompt, toggleFavorite,
// _reloadWithoutPage, _restoreCardFocus...) valident tous par FILTRAGE DE CHAÎNES sur le HTML
// rendu - jamais par exécution. À l'opposé, les tests tests/js/*.cjs chargent et exécutent
// réellement le JS du wizard (new Function(src) + mocks fetch/DOM).
//
// Écart de guarantee originel : un futur changement peut rendre la logique INERTE tout en laissant
// les sous-chaînes testées en place, et les 20 tests resteraient verts.
//
// TÂCHE #1416 (2026-08-02) : le script inline de promptsLibrary() a été extrait vers
// public/assets/tools/user-prompts/user-prompts-core.js (Alpine.data(), pattern déjà établi pour
// constructeur-prompts-core.js) - c'est exactement l'extraction que ce test annonçait comme "la
// vraie correction", alors planifiée séparément pour ne pas risquer un refactor architectural sur
// un fichier retouché pendant 20 rounds consécutifs à la veille d'une publication. Ce fichier est
// donc mis à jour pour vérifier la NOUVELLE architecture : le fichier externe (au lieu du HTML
// rendu, qui ne contient plus la fabrique en clair) est analysé par `node --check`, et la page ne
// doit plus jamais embarquer promptsLibrary() inline.

it('serves a syntactically valid promptsLibrary script from the extracted asset (round 132 -> tâche #1416)', function () {
    $path = public_path('assets/tools/user-prompts/user-prompts-core.js');
    expect(file_exists($path))->toBeTrue('Le fichier extrait public/assets/tools/user-prompts/user-prompts-core.js est absent.');

    // Node est la seule autorité qui dise si ce script est réellement analysable. `node --check`
    // ne l'exécute pas : aucun effet de bord, aucun DOM requis.
    $output = [];
    $exitCode = 0;
    exec('node --check '.escapeshellarg($path).' 2>&1', $output, $exitCode);

    expect($exitCode)->toBe(0, "Le script de /user/prompts n'est pas analysable par Node :\n".implode("\n", $output));
});

it('keeps the library methods reachable inside the returned object (round 132 -> tâche #1416)', function () {
    $js = file_get_contents(public_path('assets/tools/user-prompts/user-prompts-core.js'));

    // Garde structurelle : les méthodes doivent vivre DANS l'objet retourné par la fabrique Alpine.
    // Une sous-chaîne présente ailleurs dans le fichier (commentaire, code mort) ne suffit pas.
    $factoryPos = strpos($js, "Alpine.data('promptsLibrary', function (config) {");
    expect($factoryPos)->not->toBeFalse('La fabrique Alpine.data(\'promptsLibrary\', ...) est absente du fichier extrait.');

    $returnPos = strpos($js, 'return {', $factoryPos);
    expect($returnPos)->not->toBeFalse('promptsLibrary() ne retourne pas d\'objet Alpine.');

    $body = substr($js, $returnPos);

    foreach (['saveTags', 'duplicatePrompt', 'deletePrompt', 'toggleFavorite', '_restoreCardFocus'] as $method) {
        expect($body)->toContain($method);
    }
});

it('registers promptsLibrary on alpine:init instead of a bare global function (tâche #1416, matches constructeur-prompts-core.js pattern)', function () {
    $js = file_get_contents(public_path('assets/tools/user-prompts/user-prompts-core.js'));

    expect($js)->toContain("document.addEventListener('alpine:init', function () {");
    expect($js)->not->toContain('function promptsLibrary()');
});

it('no longer embeds promptsLibrary() inline in the rendered page (tâche #1416, must load as an external asset)', function () {
    $user = User::factory()->create();

    SavedPrompt::create([
        'user_id' => $user->id,
        'name' => 'Prompt intégrité script',
        'prompt_text' => 'Contenu de test',
        'tags' => ['integrite'],
    ]);

    $html = $this->actingAs($user)->get('/user/prompts')->assertOk()->getContent();

    expect($html)->not->toContain('function promptsLibrary()');
    expect($html)->not->toContain("Alpine.data('promptsLibrary'");
    expect($html)->toContain('assets/tools/user-prompts/user-prompts-core.js');
    expect($html)->toContain('x-data="promptsLibrary(');
});

it('serves the extracted script with the standard semver cache-busting query string (tâche #1416)', function () {
    $user = User::factory()->create();

    $html = $this->actingAs($user)->get('/user/prompts')->assertOk()->getContent();

    $expected = 'assets/tools/user-prompts/user-prompts-core.js?v='.config('version.semver');
    expect($html)->toContain($expected);
});
