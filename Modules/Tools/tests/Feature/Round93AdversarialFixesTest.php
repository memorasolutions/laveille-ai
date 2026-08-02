<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Tools\Models\SavedPrompt;
use Modules\Tools\Models\Tool;

uses(Tests\TestCase::class, RefreshDatabase::class);

// Round 93 (2026-07-27) : passe adversariale fraîche après le lot round 92 (8 boutons .ct-btn +
// anonBtn + chips in-carte). 3 manques réels corrigés :
//
// 1. Modules/Tools/resources/views/public/tools/constructeur-prompts.blade.php:696-700 - la
//    modale d'aide (#promptHelpModal) n'avait ni id sur son <h4 class="modal-title"> ni
//    aria-labelledby sur le <div class="modal">, contrairement au même pattern déjà en place sur
//    2 autres modales d'aide du même module (code-qr.blade.php, liens-google.blade.php) - le nom
//    accessible de la boîte de dialogue (WCAG 4.1.2) n'était jamais associé à son titre visible.
//    Fixé : ajout de id="promptHelpModalLabel" au h4 + aria-labelledby="promptHelpModalLabel" au
//    div.modal, cohérent avec les 2 autres fichiers.
// 2. Modules/Tools/app/Http/Controllers/ToolPreferenceController.php::sanitizeCustomCards() -
//    validait le FORMAT de l'id de chaque carte personnalisée mais jamais son unicité au sein du
//    même tableau. Un appel API direct (hors JS client, qui génère normalement des id
//    quasi-uniques) pouvait persister 2 cartes de même id ; toute action de
//    suppression/déplacement/masquage sur ce id (findIndex() côté JS) n'agissait alors que sur la
//    1re occurrence, rendant la 2e carte fantôme définitivement inatteignable via l'UI. Fixé :
//    dictionnaire $seenIds - régénère un id aléatoire si déjà vu dans le même lot.
// 3. Modules/Tools/app/Models/SavedPrompt.php::scopeSearch() - construisait la clause LIKE sans
//    échapper les caractères jokers SQL % et _. Une recherche contenant littéralement l'un de ces
//    caractères (ex. "réduction de 20%", "nom_variable") donnait des résultats faux/imprévisibles
//    au lieu de chercher le caractère littéral. Fixé : échappement + clause ESCAPE explicite
//    (portable MySQL + SQLite).

// Étape 9 (2026-08-02, réécriture complète) : les 2 tests qui verrouillaient l'accessibilité de
// la modale d'aide #promptHelpModal ont été retirés - cette modale n'existe plus (écran unique,
// plan section 4). Les 2 tests ci-dessous restent en revanche pleinement pertinents :
// sanitizeCustomCards() reste un vrai comportement backend du contrôleur ToolPreferenceController
// (custom_cards persiste en base pour compatibilité, plan section 5 : "tool_preferences reste en
// base pour compatibilité" - toujours atteignable via l'API même si la nouvelle page n'écrit plus
// custom_cards), et scopeSearch() est une règle de sécurité applicative indépendante du markup.

it('deduplicates custom card ids instead of persisting silent duplicates (round 93)', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/tool-preferences/constructeur-prompts', [
        'key' => 'custom_cards',
        'value' => [
            ['id' => 'ma_carte', 'title' => 'Carte A', 'icon' => '⭐', 'query_template' => 'texte A'],
            ['id' => 'ma_carte', 'title' => 'Carte B', 'icon' => '🔥', 'query_template' => 'texte B'],
        ],
    ]);

    $response->assertOk();

    $cards = $response->json('preferences.custom_cards');

    expect($cards)->toHaveCount(2);
    $ids = array_column($cards, 'id');
    expect($ids)->toEqual(array_unique($ids));
});

it('finds prompts containing literal % or _ characters instead of treating them as SQL wildcards (round 93)', function () {
    $user = User::factory()->create();

    // Données discriminantes : sans échappement, % et _ agissent comme des jokers SQL et
    // matchent AUSSI les variantes "n'importe quel caractère" - un faux positif qui ne serait
    // PAS détecté par un simple test "le texte contenant le caractère littéral est bien trouvé"
    // (les deux comportements le trouvent). Le vrai test est l'ABSENCE du faux positif.
    SavedPrompt::create([
        'user_id' => $user->id,
        'name' => 'sale%offer',
        'prompt_text' => 'Contenu test A',
    ]);
    SavedPrompt::create([
        'user_id' => $user->id,
        'name' => 'sale20offer',
        'prompt_text' => 'Contenu test B',
    ]);
    SavedPrompt::create([
        'user_id' => $user->id,
        'name' => 'Sans rapport',
        'prompt_text' => 'test_one',
    ]);
    SavedPrompt::create([
        'user_id' => $user->id,
        'name' => 'Sans rapport non plus',
        'prompt_text' => 'testXone',
    ]);

    // "%" littéral ne doit PAS agir comme joker : "sale%offer" seulement, pas "sale20offer".
    $matchesPercent = SavedPrompt::forUser($user->id)->search('sale%offer')->pluck('name')->all();
    expect($matchesPercent)->toBe(['sale%offer']);

    // "_" littéral ne doit PAS agir comme joker : "test_one" seulement, pas "testXone".
    $matchesUnderscore = SavedPrompt::forUser($user->id)->search('test_one')->pluck('prompt_text')->all();
    expect($matchesUnderscore)->toBe(['test_one']);
});
