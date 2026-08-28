<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Porte de modération à la soumission publique (2026-08-28) - avant ce correctif,
 * storeSubmission() publiait TOUJOURS la fiche en direct (status='published' en dur), sans
 * relecture, pour n'importe quel utilisateur connecté (incident constaté : 6 fiches d'un même
 * compte à valider en lot). Couvre : porte pending/published selon la permission moderate_tools
 * (même mécanisme que Modules/Directory/routes/web.php:89 et PublicDirectoryController.php:47,
 * 95, 190), message de retour distinct selon le cas, invisibilité publique réelle d'une fiche
 * pending (scope published(), show(), API publique v1), et non-régression de l'attachement aux
 * collections utilisateur.
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Directory\Models\Tool;
use Modules\Directory\Models\ToolCollection;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Modules\RolesPermissions\Database\Seeders\RolesAndPermissionsSeeder::class);
    $this->seed(\Modules\Directory\Database\Seeders\DirectoryModeratorRoleSeeder::class);
    config(['app.locale' => 'fr_CA']);

    // Même polyfill que Modules/Directory/tests/Feature/PublicFocalCropperTest.php:30-44 (et
    // AffiliateLinkTest/ToolSpecTableTest/DirectoryViewCounterTest/ThinContentNoindexTest) :
    // sqlite :memory: (phpunit.xml) n'a pas la fonction MySQL FIELD() utilisée par
    // PublicDirectoryController::show() pour trier les ressources - limitation pré-existante,
    // indépendante de ce chantier. Nécessaire ici car un moderateur publie directement (test
    // 'la fiche est publiée directement') et sa fiche passe par show() en 200.
    $pdo = DB::connection()->getPdo();
    if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite') {
        $pdo->sqliteCreateFunction('FIELD', function (...$args) {
            $needle = array_shift($args);
            foreach ($args as $i => $value) {
                if ($needle === $value) {
                    return $i + 1;
                }
            }

            return 0;
        });
    }
});

test('un utilisateur ordinaire soumet un outil : la fiche part en attente et reste invisible du public', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson(route('directory.submit'), [
        'url' => 'https://exemple-soumission-pending.test',
        'name' => 'Outil Soumission Pending',
        'pricing' => 'free',
    ]);

    $response->assertOk();
    $response->assertJson(['success' => true]);

    $tool = Tool::where('url', 'https://exemple-soumission-pending.test')->firstOrFail();
    expect($tool->status)->toBe('pending');
    expect((int) $tool->submitted_by)->toBe($user->id);

    // Invisible sur sa propre fiche publique (scope published() dans show() -> 404, jamais 200)
    $slug = $tool->getTranslation('slug', 'fr_CA');
    $this->get(route('directory.show', $slug))->assertNotFound();

    // Invisible dans l'API publique en lecture seule (Api/PublicToolsController::tools())
    $api = $this->getJson(route('api.api.public.tools', ['q' => 'Outil Soumission Pending']));
    $api->assertOk();
    expect($api->json('data'))->toBeEmpty();
});

test('un modérateur (permission moderate_tools) soumet un outil : la fiche est publiée directement', function () {
    $user = User::factory()->create();
    $user->assignRole('directory_moderator');

    $response = $this->actingAs($user)->postJson(route('directory.submit'), [
        'url' => 'https://exemple-soumission-published.test',
        'name' => 'Outil Soumission Published',
        'pricing' => 'free',
    ]);

    $response->assertOk();
    $response->assertJson(['success' => true]);

    $tool = Tool::where('url', 'https://exemple-soumission-published.test')->firstOrFail();
    expect($tool->status)->toBe('published');

    // Visible immédiatement sur sa fiche publique
    $slug = $tool->getTranslation('slug', 'fr_CA');
    $this->get(route('directory.show', $slug))->assertOk();

    // Et dans l'API publique
    $api = $this->getJson(route('api.api.public.tools', ['q' => 'Outil Soumission Published']));
    $api->assertOk();
    expect($api->json('data'))->not->toBeEmpty();
});

test('le message de retour diffère selon que la fiche parte en attente ou soit publiée', function () {
    $ordinaryUser = User::factory()->create();
    $moderator = User::factory()->create();
    $moderator->assignRole('directory_moderator');

    $pendingResponse = $this->actingAs($ordinaryUser)->postJson(route('directory.submit'), [
        'url' => 'https://exemple-message-pending.test',
        'name' => 'Outil Message Pending',
        'pricing' => 'free',
    ]);

    $publishedResponse = $this->actingAs($moderator)->postJson(route('directory.submit'), [
        'url' => 'https://exemple-message-published.test',
        'name' => 'Outil Message Published',
        'pricing' => 'free',
    ]);

    $pendingMessage = $pendingResponse->json('message');
    $publishedMessage = $publishedResponse->json('message');

    expect($pendingMessage)->not->toBeEmpty();
    expect($publishedMessage)->not->toBeEmpty();
    expect($pendingMessage)->not->toBe($publishedMessage);

    // Le message d'une fiche en attente ne doit JAMAIS prétendre qu'elle est déjà en ligne.
    expect($pendingMessage)->not->toContain('a été ajouté au répertoire');
    expect(Tool::where('url', 'https://exemple-message-pending.test')->firstOrFail()->status)->toBe('pending');
    expect(Tool::where('url', 'https://exemple-message-published.test')->firstOrFail()->status)->toBe('published');
});

test('attachement à une collection : fonctionne toujours pour une fiche partie en attente', function () {
    $user = User::factory()->create();
    $collection = ToolCollection::create([
        'user_id' => $user->id,
        'name' => 'Ma collection de test',
        'is_public' => false,
    ]);

    $response = $this->actingAs($user)->postJson(route('directory.submit'), [
        'url' => 'https://exemple-soumission-collection.test',
        'name' => 'Outil Soumission Collection',
        'pricing' => 'free',
        'collection_ids' => [$collection->id],
    ]);

    $response->assertOk();
    $response->assertJson(['success' => true]);

    $tool = Tool::where('url', 'https://exemple-soumission-collection.test')->firstOrFail();

    // La fiche est bien en attente (précondition du test) ET l'attachement a quand même eu lieu.
    expect($tool->status)->toBe('pending');
    expect($collection->tools()->where('directory_tools.id', $tool->id)->exists())->toBeTrue();
});
