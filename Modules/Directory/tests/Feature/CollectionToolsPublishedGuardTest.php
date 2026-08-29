<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * #1985 (suite) - troisieme vecteur, jumeau de storeScreenshot()/storePricingReport() corriges le
 * meme jour : CollectionController::show() chargeait la relation tools d'une collection PUBLIQUE
 * (route /collections/{slug}, aucun middleware auth, cachee 10 min) sans Tool::published(). N'importe
 * quel utilisateur authentifie peut creer une collection publique et y attacher n'importe quel
 * tool_id existant (toggleTool() ne valide que exists:directory_tools,id, jamais le statut) : une
 * fiche brouillon/en attente/archivee etait donc rendue en entier - nom, description, capture,
 * prix - a TOUT visiteur anonyme, y compris dans le bloc JSON-LD ItemList indexable par les
 * moteurs/crawlers IA.
 *
 * Correctif : with('tools') devient with(['tools' => fn ($q) => $q->published()]), meme scope que
 * l'API JSON soeur deja correcte (PublicToolsController::collectionShow(), qui filtrait deja sa
 * relation eager-loadee avec 'status' => 'published'). withCount('tools') recoit le meme filtre
 * pour que le badge et le seuil noindex (< 3 outils, show.blade.php ligne 11) restent coherents
 * avec la liste reellement rendue - sinon un compte gonfle par des outils non publies aurait pu
 * desactiver a tort le noindex d'une collection presque vide.
 *
 * Six autres points chargeant une relation tools sans filtre de statut ont ete mesures le meme
 * jour (CollectionController::index()/myCollections()/toggleTool(), PublicToolsController::
 * collections() cote API, et le bloc "related collections" de PublicDirectoryController::show()) :
 * aucun ne rend un champ de fiche (nom, description, URL, capture) d'un outil non publie, seulement
 * un compte agrege - ce ne sont pas des vecteurs d'exposition de fiche et ils ne sont pas touches
 * ici. PublicDirectoryController::compare() et ToolComparisonService::loadTools() filtraient deja.
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Directory\Models\Tool;
use Modules\Directory\Models\ToolCollection;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    config(['app.locale' => 'fr_CA']);
});

function makeCollectionGuardTestTool(string $slug, string $status): Tool
{
    config(['app.locale' => 'fr_CA']);

    $tool = new Tool();
    $tool->setTranslation('name', 'fr_CA', 'Outil Collection '.$slug);
    $tool->setTranslation('slug', 'fr_CA', $slug);
    $tool->setTranslation('description', 'fr_CA', 'Description de test.');
    $tool->setTranslation('short_description', 'fr_CA', 'Résumé de test.');
    $tool->url = 'https://exemple-collection.test/'.$slug;
    $tool->pricing = 'free';
    $tool->status = $status;
    $tool->save();
    $tool->refresh();

    return $tool;
}

function makeCollectionGuardTestCollection(string $slug): ToolCollection
{
    $user = User::factory()->create();

    return ToolCollection::create([
        'user_id' => $user->id,
        'name' => 'Collection Garde '.$slug,
        'slug' => $slug,
        'description' => 'Collection de test.',
        'is_public' => true,
    ]);
}

test('une collection publique ne contenant qu\'un outil pending s\'affiche vide', function () {
    $collection = makeCollectionGuardTestCollection('collection-pending-seule');
    $tool = makeCollectionGuardTestTool('outil-pending-collection', 'pending');
    $collection->addTool($tool->id);

    $response = $this->get(route('collections.show', $collection->slug));

    $response->assertOk();
    $response->assertDontSee($tool->getTranslation('name', 'fr_CA'));
    $response->assertSee('Cette collection est vide');
});

test('une collection publique affiche l\'outil publie mais pas son voisin draft', function () {
    $collection = makeCollectionGuardTestCollection('collection-mixte-draft');
    $published = makeCollectionGuardTestTool('outil-publie-mixte-draft', 'published');
    $draft = makeCollectionGuardTestTool('outil-draft-mixte', 'draft');
    $collection->addTool($published->id);
    $collection->addTool($draft->id);

    $response = $this->get(route('collections.show', $collection->slug));

    $response->assertOk();
    $response->assertSee($published->getTranslation('name', 'fr_CA'));
    $response->assertDontSee($draft->getTranslation('name', 'fr_CA'));
});

test('une collection publique affiche l\'outil publie mais pas son voisin archived', function () {
    $collection = makeCollectionGuardTestCollection('collection-mixte-archived');
    $published = makeCollectionGuardTestTool('outil-publie-mixte-archived', 'published');
    $archived = makeCollectionGuardTestTool('outil-archived-mixte', 'archived');
    $collection->addTool($published->id);
    $collection->addTool($archived->id);

    $response = $this->get(route('collections.show', $collection->slug));

    $response->assertOk();
    $response->assertSee($published->getTranslation('name', 'fr_CA'));
    $response->assertDontSee($archived->getTranslation('name', 'fr_CA'));
});

test('l\'outil non publie n\'apparait pas non plus dans le JSON-LD ItemList indexable', function () {
    $collection = makeCollectionGuardTestCollection('collection-jsonld');
    $published = makeCollectionGuardTestTool('outil-publie-jsonld', 'published');
    $pending = makeCollectionGuardTestTool('outil-pending-jsonld', 'pending');
    $collection->addTool($published->id);
    $collection->addTool($pending->id);

    $response = $this->get(route('collections.show', $collection->slug));

    $response->assertOk();
    $response->assertSee('application/ld+json', false);
    $response->assertSee($published->getPublicUrl(), false);
    $response->assertDontSee($pending->getPublicUrl(), false);
});

test('withCount reste coherent avec les outils rendus : 2 publies + 3 non publies restent sous le seuil noindex', function () {
    $collection = makeCollectionGuardTestCollection('collection-noindex-coherent');
    $collection->addTool(makeCollectionGuardTestTool('outil-publie-noindex-1', 'published')->id);
    $collection->addTool(makeCollectionGuardTestTool('outil-publie-noindex-2', 'published')->id);
    $collection->addTool(makeCollectionGuardTestTool('outil-pending-noindex-1', 'pending')->id);
    $collection->addTool(makeCollectionGuardTestTool('outil-pending-noindex-2', 'pending')->id);
    $collection->addTool(makeCollectionGuardTestTool('outil-draft-noindex-1', 'draft')->id);

    $response = $this->get(route('collections.show', $collection->slug));

    // 2 outils publies < seuil de 3 : la meta noindex doit rester presente. Si withCount()
    // comptait encore les 3 non publies (5 au total >= 3), la meta noindex disparaitrait a tort -
    // preuve indirecte mais precise que withCount() applique bien published().
    $response->assertOk();
    $response->assertSee('name="robots" content="noindex,follow"', false);
});

test('une collection privee reste inatteignable par son slug, meme avec un outil publie', function () {
    $user = User::factory()->create();
    $collection = ToolCollection::create([
        'user_id' => $user->id,
        'name' => 'Collection Privee Garde',
        'slug' => 'collection-privee-garde',
        'description' => 'Collection de test.',
        'is_public' => false,
    ]);
    $tool = makeCollectionGuardTestTool('outil-publie-collection-privee', 'published');
    $collection->addTool($tool->id);

    $response = $this->get(route('collections.show', $collection->slug));

    $response->assertNotFound();
});
