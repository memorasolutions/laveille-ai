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
// rendu - jamais par exécution. À l'opposé, les 28 tests tests/js/*.cjs chargent et exécutent
// réellement le JS du wizard (new Function(src) + mocks fetch/DOM).
//
// Écart de garantie : un futur changement peut rendre la logique INERTE tout en laissant les
// sous-chaînes testées en place, et les 20 tests resteraient verts.
//
// POURQUOI CE TEST-CI ET PAS LA SOLUTION COMPLÈTE : la vraie correction est d'extraire ce script
// inline vers un fichier d'asset, comme le wizard, pour le rendre exécutable en banc d'essai. C'est
// un refactor architectural sur un fichier retouché pendant 20 rounds consécutifs : le faire à la
// veille d'une publication ferait courir un risque sans commune mesure avec le bénéfice immédiat.
// Il est donc planifié séparément (tâche #1416).
//
// Ce que ce test apporte DÈS MAINTENANT, et qu'aucune assertion de sous-chaîne ne peut donner :
// une vérification au niveau du MOTEUR JS. Le script réellement servi au navigateur est extrait de
// la page rendue (donc après résolution de toutes les expressions Blade) et soumis à `node --check`.
// Cela attrape la classe d'erreurs la plus coûteuse rencontrée pendant la campagne : au round 121,
// un commentaire Blade inséré dans un bloc @php a produit une ParseError et une page 500. Un test
// de sous-chaîne ne l'aurait jamais vu ; celui-ci échoue immédiatement.

it('serves a syntactically valid promptsLibrary script (round 132)', function () {
    $user = User::factory()->create();

    SavedPrompt::create([
        'user_id' => $user->id,
        'name' => 'Prompt intégrité script',
        'prompt_text' => 'Contenu de test',
        'tags' => ['integrite'],
    ]);

    $html = $this->actingAs($user)->get('/user/prompts')->assertOk()->getContent();

    // On isole le bloc réellement exécuté par le navigateur : de la déclaration de la fabrique
    // Alpine jusqu'à la fermeture du <script> qui la contient.
    $start = strpos($html, 'function promptsLibrary()');
    expect($start)->not->toBeFalse('La fabrique promptsLibrary() est absente de la page rendue.');

    $end = strpos($html, '</script>', $start);
    expect($end)->not->toBeFalse('Le bloc <script> de promptsLibrary() n\'est pas refermé.');

    $script = substr($html, $start, $end - $start);

    // Node est la seule autorité qui dise si ce script est réellement analysable. `node --check`
    // ne l'exécute pas : aucun effet de bord, aucun DOM requis.
    $tmp = tempnam(sys_get_temp_dir(), 'prompts_lib_').'.js';
    file_put_contents($tmp, $script);

    $output = [];
    $exitCode = 0;
    exec('node --check '.escapeshellarg($tmp).' 2>&1', $output, $exitCode);
    @unlink($tmp);

    expect($exitCode)->toBe(0, "Le script de /user/prompts n'est pas analysable par Node :\n".implode("\n", $output));
});

it('keeps the library methods reachable inside the returned object (round 132)', function () {
    $user = User::factory()->create();

    $html = $this->actingAs($user)->get('/user/prompts')->assertOk()->getContent();

    $start = strpos($html, 'function promptsLibrary()');
    $end = strpos($html, '</script>', $start);
    $script = substr($html, $start, $end - $start);

    // Garde structurelle : les méthodes doivent vivre DANS l'objet retourné par la fabrique. Une
    // sous-chaîne présente ailleurs dans le fichier (commentaire, code mort) ne suffit pas.
    $returnPos = strpos($script, 'return {');
    expect($returnPos)->not->toBeFalse('promptsLibrary() ne retourne pas d\'objet Alpine.');

    $body = substr($script, $returnPos);

    foreach (['saveTags', 'duplicatePrompt', 'deletePrompt', 'toggleFavorite', '_restoreCardFocus'] as $method) {
        expect($body)->toContain($method);
    }
});
